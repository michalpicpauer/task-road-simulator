#Task road simulator
=========

A Symfony project created on March 6, 2017, 6:27 pm.

This project runs simulation which go through each point on the selected road and calculate distances to each city.
The text output contain lines with city names and distances from the current point on the road.

Simulation also detect an approach to the city in case it’s in radius of 5 kilometers and add text line which 
informs which city is approaching. The notification is fired only once for each city.

### Instalation

##### Install composer data
```
composer install
```

### Update

##### Update composer data
```
composer update
```

### Command

##### Usage:
```
php bin/console task:simulate <file> [<sleep>]
```
##### Arguments:

  - file - Path to gpx file with track data
  - sleep - Sets sleep time for iteration over points [ms]
  
  If you omit required argument, the command will ask you to
  provide the missing value:

  - command will ask you for filepath
      ```
      php bin/console task:simulate
      ```

