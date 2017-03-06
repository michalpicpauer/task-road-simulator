<?php

namespace AppBundle\Command;

use AppBundle\Entity\City;
use AppBundle\Utils\GPSDistanceMeter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use MichalPicpauer\GPXLoader\GPXLoader;
use MichalPicpauer\GPXLoader\Point;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;

/**
 * A command console that simulates road travel.
 *
 * To use this command, open a terminal window, enter into your project
 * directory and execute the following:
 *
 *     $ php bin/console task:simulate
 *
 * To output detailed information, increase the command verbosity:
 *
 *     $ php bin/console task:simulate -vv
 *
 * @author Michal Picpauer <michalpicpauer@gmail.com>
 */
class SimulateCommand extends ContainerAwareCommand
{
    const MAX_ATTEMPTS = 5;
    const APPROACH_DISTANCE = 5.00;

    /**
     * @var ObjectManager
     */
    private $entityManager;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('task:simulate')
            ->setDescription('Runs the simulation')
            ->setHelp($this->getCommandHelp())
            ->addArgument('file', InputArgument::REQUIRED, 'Path to gpx file with track data')
            ->addArgument('sleep', InputArgument::OPTIONAL, 'Sets sleep time for iteration over points in ms')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->entityManager = $this->getContainer()->get('doctrine')->getManager();
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (null !== $input->getArgument('file')) {
            return;
        }

        $output->writeln([
            '',
            'Now I\'ll ask you for the value of the missing command argument.',
            '',
        ]);

        $console = $this->getHelper('question');

        // Ask for the username if it's not defined
        $filename = $input->getArgument('file');
        if (null === $filename) {
            $question = new Question(' > <info>Filename</info>: ');
            $question->setValidator([$this, 'fileValidator']);
            $question->setMaxAttempts(self::MAX_ATTEMPTS);

            $filename = $console->ask($input, $output, $question);
            $input->setArgument('file', $filename);
        } else {
            $output->writeln(' > <info>Filename</info>: '.$filename);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startTime = microtime(true);
        $output->writeln('');
        $output->writeln('<comment>[INFO] Simulation was started<comment>');

        // init library and load file with track
        $track = new GPXLoader($input->getArgument('file'));

        $output->writeln('');
        $output->writeln(sprintf('<info>Track name: %s</info>', $track->getTrackName()));


        // load all cities from database
        $cities = $this->entityManager->getRepository(City::class)->findAll();
        $approachedCities = new ArrayCollection();

        /** @var Point $point */
        foreach ($track as $point) {
            $output->writeln('');
            $output->writeln(sprintf('<info>Current point lat, lng: %.5f, %.5f</info>', $point->getLatitude(), $point->getLongitude()));
            $mask = "| %-30.30s | %13.3f | x |";
            $output->writeln(sprintf('| %-30.30s | %13s | x |', 'City name', 'Distance [km]'));

            $distanceMeter = new GPSDistanceMeter($point->getLatitude(), $point->getLongitude());
            $approachingCities = array();

            /** @var City $city */
            foreach ($cities as $city) {
                // computed distance is in meters and we want it in km
                $distance = $distanceMeter->distanceToCityHaversine($city) / 1000;

                $output->writeln(sprintf($mask, $city->getName(), $distance));
                // if distance is same or smaller than APPROACH_DISTANCE then add city name to array
                if ($distance <= self::APPROACH_DISTANCE) {
                    $approachingCities[] = $city->getName();
                }
            }

            // inform about approach
            foreach ($approachingCities as $approachingCity) {
                // notification can be fired only once for each city
                if (!$approachedCities->contains($approachingCity)) {
                    $output->writeln(sprintf('<info>City %s is approaching</info>', $approachingCity));
                    $approachedCities->add($approachingCity);
                }
            }

            // sets sleep time if is set
            if (null !== $input->getArgument('sleep')) {
                usleep($input->getArgument('sleep')*1000);
            }
        }

        $output->writeln('');
        $output->writeln('<comment>[OK] Simulation was successfully finished</comment>');

        if ($output->isVerbose()) {
            $finishTime = microtime(true);
            $elapsedTime = $finishTime - $startTime;

            $output->writeln(sprintf('<comment>[INFO] Elapsed time: %.2f ms</comment>', $elapsedTime * 1000));
        }
    }

    /**
     * @internal
     */
    public function fileValidator($file)
    {
        if (empty($file)) {
            throw new \Exception('The file path can not be empty.');
        }

        $fs = new Filesystem();

        if (false === $fs->exists($file)) {
            throw new \Exception('The file does not exist.');
        }

        return $file;
    }

    private function getCommandHelp()
    {
        return <<<'HELP'
The <info>%command.name%</info> runs simulation which go through each point on the selected road 
and calculate distances to each city.
The text output will contain lines with city names and distances from the current point on the road.

Simulation will also detect an approach to the city in case it’s in radius of 5 kilometers and add text line which 
informs which city is approaching. The notification has to be fired only once for each city.

  <info>php %command.full_name%</info> <comment>filepath sleeptime</comment>

Sleep time is optional and in milliseconds. 

If you omit required argument, the command will ask you to
provide the missing value:

  # command will ask you for filepath
  <info>php %command.full_name%</info>

HELP;
    }
}
