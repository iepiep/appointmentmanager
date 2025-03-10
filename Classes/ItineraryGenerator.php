<?php
/**
 * @author Roberto Minini <r.minini@solution61.fr>
 * @copyright 2025 Roberto Minini
 * @license MIT
 *
 * This file is part of the AppointmentManager Module
 * License: MIT License
 */

namespace AppointmentManager\Classes;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ItineraryGenerator
{
    protected $home;
    protected $appointments;
    protected $config;

    public function __construct($home, $appointments, $config)
    {
        $this->home = $home;
        $this->appointments = $appointments;
        $this->config = $config;
    }
    public function generateItinerary()
    {
        $points = array();
        foreach ($this->appointments as $appt) {
            $points[] = array(
                'id'         => $appt['id_appointment_manager'],
                'name'       => $appt['firstname'].' '.$appt['lastname'],
                'address'    => $appt['address'],
                'postal_code'=> $appt['postal_code'],
                'city'       => $appt['city'],
                'lat'        => 48.8566,
                'lng'        => 2.3522
            );
        }
        $start = array(
            'id'         => 0,
            'name'       => 'HomePlace',
            'address'    => $this->home['address'],
            'postal_code'=> $this->home['postal_code'],
            'city'       => $this->home['city'],
            'lat'        => 48.8566,
            'lng'        => 2.3522
        );
        $route = $this->tspNearestNeighbor($start, $points);
        $route = $this->twoOptOptimization($route);
        $itinerary = $this->calculateTimetable($route);
        return $itinerary;
    }
    protected function tspNearestNeighbor($start, $points)
    {
        $route = array();
        $current = $start;
        $remaining = $points;
        while (!empty($remaining)) {
            $nearest = null;
            $minDist = null;
            $index = null;
            foreach ($remaining as $i => $point) {
                $dist = $this->distance($current, $point);
                if (is_null($minDist) || $dist < $minDist) {
                    $minDist = $dist;
                    $nearest = $point;
                    $index = $i;
                }
            }
            $route[] = $nearest;
            $current = $nearest;
            array_splice($remaining, $index, 1);
        }
        return $route;
    }
    protected function twoOptOptimization($route)
    {
        $improved = true;
        $size = count($route);
        while ($improved) {
            $improved = false;
            for ($i = 0; $i < $size - 1; $i++) {
                for ($j = $i + 1; $j < $size; $j++) {
                    $newRoute = $route;
                    $newRoute = array_merge(
                        array_slice($newRoute, 0, $i),
                        array_reverse(array_slice($newRoute, $i, $j - $i + 1)),
                        array_slice($newRoute, $j + 1)
                    );
                    if ($this->routeDistance($newRoute) < $this->routeDistance($route)) {
                        $route = $newRoute;
                        $improved = true;
                    }
                }
            }
        }
        return $route;
    }
    protected function distance($a, $b)
    {
        return sqrt(pow($a['lat'] - $b['lat'], 2) + pow($a['lng'] - $b['lng'], 2));
    }
    protected function routeDistance($route)
    {
        $dist = 0;
        $prev = array('lat' => 48.8566, 'lng' => 2.3522);
        foreach ($route as $point) {
            $dist += $this->distance($prev, $point);
            $prev = $point;
        }
        $dist += $this->distance($prev, array('lat' => 48.8566, 'lng' => 2.3522));
        return $dist;
    }
    protected function calculateTimetable($route)
    {
        $startTime = \DateTime::createFromFormat('H:i', $this->config['start_time']);
        $appointmentLength = (int)$this->config['appointment_length'];
        $breakLength = (int)$this->config['break_length'];
        $morning = array();
        $afternoon = array();
        $currentTime = clone $startTime;
        foreach ($route as $point) {
            if ($currentTime < (clone $startTime)->setTime(12, 0)) {
                $morning[] = array(
                    'time'        => $currentTime->format('H:i'),
                    'name'        => $point['name'],
                    'address'     => $point['address'],
                    'postal_city' => $point['postal_code'].' '.$point['city']
                );
            } else {
                $afternoon[] = array(
                    'time'        => $currentTime->format('H:i'),
                    'name'        => $point['name'],
                    'address'     => $point['address'],
                    'postal_city' => $point['postal_code'].' '.$point['city']
                );
            }
            $currentTime->modify('+'.$appointmentLength.' minutes');
            if ($currentTime->format('H:i') == '13:00') {
                $currentTime->modify('+'.$breakLength.' minutes');
            }
        }
        return array(
            'date'      => new \DateTime(),
            'morning'   => $morning,
            'afternoon' => $afternoon
        );
    }
}
