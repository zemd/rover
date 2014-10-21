<?php

namespace Rover\Services;
	
use LatLng;
use SphericalGeometry;

class RoverService
{
    protected $curIntervalTime;

    protected $curTimeSum;

    protected $curIntervalPoints = array();

    protected function getCurDiff(\DateTime $from)
    {
        return strtotime("now") - strtotime($from->format("Y-m-d H:i:s"));
    }

    /**
     * Searching route's interval
     *
     * @param array $routes
     * @param array $times
     * @param int $seconds
     * @return array
     */
    protected function findRoutes(array $routes, array $times, $seconds)
    {
        $startRoute = null;
        $endRoute = null;

        for($i=0; $i<count($times); $i += 1) {
            $seconds -= $times[$i];

            if ($seconds <= 0) {
                $this->curIntervalTime = $times[$i];
                $startRoute = $routes[$i];
                $endRoute = $routes[$i + 1];
                break;
            }
        }
        if ($seconds > 0) {
            return array();
        }
        $this->curTimeSum = $seconds;
        return array(new LatLng($startRoute['lat'], $startRoute['lng']), new LatLng($endRoute['lat'], $endRoute['lng']));
    }

    /**
     * @param array $routes
     * @param array $times
     * @param \DateTime $started
     * @return array
     */
	public function getRoverPosition(array $routes, array $times, \DateTime $started)
	{
        $seconds = $this->getCurDiff($started);
        $this->curIntervalPoints = $this->findRoutes($routes, $times, $seconds);

        if (count($this->curIntervalPoints) == 0) {
            return $routes[count($routes) - 1];
        }

        list($startRoute, $endRoute) = $this->curIntervalPoints;

        $traversed = $this->getTraversedLength($this->curTimeSum, $this->curIntervalTime, $this->curIntervalPoints);

        $heading = SphericalGeometry::computeHeading($startRoute, $endRoute);
        $position = SphericalGeometry::computeOffset($startRoute, $traversed, $heading);

		return array(
            'lat' => $position->getLat(),
            'lng' => $position->getLng()
        );
	}

    protected function getTraversedLength($seconds, $intervalTime, $points)
    {
        $intervalLength = SphericalGeometry::computeLength($points);
        $fraction = $intervalLength / $intervalTime;
        return $fraction * ($intervalTime + $seconds);
    }

}
