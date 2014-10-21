<?php

namespace Rover\Services;
	
use SphericalGeometry;

class RoverService
{
	public function getRoverPosition(array $routes, array $times, \DateTime $started)
	{
        $interval = $started->diff(new \DateTime("now"));

        $seconds = $interval->s;

        $startRoute = null;
        $j = 0;
        $thetime = 0;
        foreach($times as $i => $time) {
            $seconds -= $time;

            if ($seconds < 0) {
                $startRoute = $routes[$i];
                $j = $i;
                $thetime = $time;
                break;
            }
        }
        if ($seconds > 0) {
            return array_pop($routes);
        }
        $endRoute = $routes[$j + 1];

        $startRoute = new \LatLng($startRoute['lat'], $startRoute['lng']);
        $endRoute = new \LatLng($endRoute['lat'], $endRoute['lng']);

        $length = SphericalGeometry::computeLength(array($startRoute, $endRoute));

        $fraction = $length / $thetime;
        $moved = $fraction * ($thetime + $seconds);
        $heading = SphericalGeometry::computeHeading($startRoute, $endRoute);

        $position = SphericalGeometry::computeOffset($startRoute, $moved, $heading);

		return array(
            'lat' => $position->getLat(),
            'lng' => $position->getLng()
        );
	}

}
