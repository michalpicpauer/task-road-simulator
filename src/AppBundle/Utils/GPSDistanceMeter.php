<?php

namespace AppBundle\Utils;

use AppBundle\Entity\City;

/**
 * This class compute the distance between two GPS points
 *
 * @author Michal Picpauer <michalpicpauer@gmail.com>
 */
class GPSDistanceMeter
{

    /**
     * Constant EARTH_RADIUS is in meters
     */
    CONST EARTH_RADIUS = 6371000;
    /**
     * @var float
     */
    private $latitude;

    /**
     * @var float
     */
    private $longitude;

    public function __construct($latitude, $longitude)
    {
        $this->latitude = doubleval($latitude);
        $this->longitude = doubleval($longitude);
    }

    /**
     * Calculates the great-circle distance between the point and city, with
     * the Haversine formula.
     *
     * @param City $city
     *
     * @return float Distance between points in [m] (same as const EARTH_RADIUS)
     */
    public function distanceToCityHaversine(City $city)
    {
        // convert from degrees to radians
        $latFrom = deg2rad($this->latitude);
        $lonFrom = deg2rad($this->longitude);
        $latTo = deg2rad($city->getLatitude());
        $lonTo = deg2rad($city->getLongitude());

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
                cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $angle * self::EARTH_RADIUS;
    }


    /**
     * Calculates the great-circle distance between the point and city, with
     * the Vincenty formula.
     *
     * @param City $city
     *
     * @return float Distance between points in [m] (same as const EARTH_RADIUS)
     */
    public function distanceToCityVincenty(City $city)
    {
        // convert from degrees to radians
        $latFrom = deg2rad($this->latitude);
        $lonFrom = deg2rad($this->longitude);
        $latTo = deg2rad($city->getLatitude());
        $lonTo = deg2rad($city->getLongitude());

        $lonDelta = $lonTo - $lonFrom;
        $a = pow(cos($latTo) * sin($lonDelta), 2) +
            pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
        $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

        $angle = atan2(sqrt($a), $b);

        return $angle * self::EARTH_RADIUS;
    }
}
