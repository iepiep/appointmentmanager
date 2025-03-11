<?php
/**
 * @author Roberto Minini <r.minini@solution61.fr>
 * @copyright 2025 Roberto Minini
 * @license MIT
 *
 * This file is part of the AppointmentManager Module
 * License: MIT License
 */

namespace AppointmentManager\Service;

if (!defined('_PS_VERSION_')) {
    exit;
}

// Corrected interface
use Psr\Log\LoggerInterface;

class ItineraryGenerator
{
    protected array $home;
    protected array $appointments;
    protected array $config;


    public function __construct(
        private LoggerInterface  $logger // Use the correct interface
    ){}


    public function generateItinerary(array $home, array $appointments, array $config): array
    {
        $this->home = $home;
        $this->appointments = $appointments;
        $this->config = $config;

        $points = [];

        // Geocode home address (only once)
        $homeCoords = $this->geocodeAddress($this->home['address'] . ', ' . $this->home['postal_code'] . ' ' . $this->home['city']);
        if (!$homeCoords) {
            $this->logger->error('Failed to geocode home address: ' . $this->home['address'], ['module' => 'AppointmentManager', 'object_type' => 'ItineraryGenerator']); // Use Symfony logger
            return []; // Return empty array on failure
        }

        foreach ($this->appointments as $appt) {
            // Geocode each appointment address
            $coords = $this->geocodeAddress($appt['address'] . ', ' . $appt['postal_code'] . ' ' . $appt['city']);
            if ($coords) {
                $points[] = [
                    'id' => $appt['id_appointment_manager'],
                    'name' => $appt['firstname'] . ' ' . $appt['lastname'],
                    'address' => $appt['address'],
                    'postal_code' => $appt['postal_code'],
                    'city' => $appt['city'],
                    'lat' => $coords['lat'],
                    'lng' => $coords['lng'],
                ];
            } else {
                 $this->logger->warning('Failed to geocode address: ' . $appt['address'] . ' for appointment ID: ' . $appt['id_appointment_manager'], ['module' => 'AppointmentManager', 'object_type' => 'ItineraryGenerator']); // Use Symfony logger, include ID
                // Don't add to points if geocoding fails
            }
        }

        if (empty($points)) {
            $this->logger->warning('No valid appointments found after geocoding.', ['module' => 'AppointmentManager', 'object_type' => 'ItineraryGenerator']);  // Use Symfony logger
            return []; // No valid appointments
        }

        $start = [
            'id' => 0,
            'name' => 'HomePlace',
            'address' => $this->home['address'],
            'postal_code' => $this->home['postal_code'],
            'city' => $this->home['city'],
            'lat' => $homeCoords['lat'],
            'lng' => $homeCoords['lng'],
        ];


        $route = $this->tspNearestNeighbor($start, $points);
        $route = $this->twoOptOptimization($route);
        $itinerary = $this->calculateTimetable($route);

        return $itinerary;
    }

    protected function tspNearestNeighbor(array $start, array $points): array
    {
        // ... (rest of your tspNearestNeighbor method remains the same) ...
          $route = [];
        $current = $start;
        $remaining = $points;

        while (!empty($remaining)) {
            $nearest = null;
            $minDist = PHP_FLOAT_MAX; // Initialize with a very large number
            $index = null;

            foreach ($remaining as $i => $point) {
                $dist = $this->distance($current, $point);
                if ($dist < $minDist) { // Simplified comparison
                    $minDist = $dist;
                    $nearest = $point;
                    $index = $i;
                }
            }

            // Add the nearest point to the route and remove it from the remaining points.
            if ($nearest !== null) { // Check for null before proceeding
              $route[] = $nearest;
              $current = $nearest;
              array_splice($remaining, $index, 1);
            } else {
                // Handle the case where $nearest is null (should not happen in a typical TSP)
                $this->logger->error('Nearest neighbor not found.', ['module' => 'AppointmentManager', 'object_type' => 'ItineraryGenerator']);
                break; // Exit the loop to prevent infinite looping.
            }

        }

        return $route;
    }

    protected function twoOptOptimization(array $route): array
    {
        // ... (rest of your twoOptOptimization method remains the same) ...
        $improved = true;
        $size = count($route);

        while ($improved) {
            $improved = false;
            for ($i = 0; $i < $size - 1; $i++) {
                for ($j = $i + 1; $j < $size; $j++) {
                    $newRoute = $route;
                    // Reverse the segment between i and j
                    $newRoute = array_merge(
                        array_slice($newRoute, 0, $i),
                        array_reverse(array_slice($newRoute, $i, $j - $i + 1)),
                        array_slice($newRoute, $j + 1)
                    );

                    // Compare distances and update the route if the new route is shorter.
                    if ($this->routeDistance($newRoute) < $this->routeDistance($route)) {
                        $route = $newRoute;
                        $improved = true;
                    }
                }
            }
        }
        return $route;
    }

    protected function distance(array $a, array $b): float
    {
        // ... (Haversine formula remains the same) ...
         // Haversine formula (accurate for geographic coordinates)
        $earthRadius = 6371; // Radius of the earth in km

        $latFrom = deg2rad($a['lat']);
        $lonFrom = deg2rad($a['lng']);
        $latTo = deg2rad($b['lat']);
        $lonTo = deg2rad($b['lng']);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        return $angle * $earthRadius;
    }

    protected function routeDistance(array $route): float
    {
        // ... (routeDistance calculation, including home, remains the same) ...
        $dist = 0;
        $homeCoords = $this->geocodeAddress($this->home['address'] . ', ' . $this->home['postal_code'] . ' ' . $this->home['city']);

        // Handle geocoding failure
        if (!$homeCoords) {
           $this->logger->error('Failed to geocode home address for route distance calculation: ' . $this->home['address'], ['module' => 'AppointmentManager', 'object_type' => 'ItineraryGenerator']);
            return PHP_FLOAT_MAX; // Return a very large distance
        }
        $prev = ['lat' => $homeCoords['lat'], 'lng' => $homeCoords['lng']]; // Start from home

        foreach ($route as $point) {
            $dist += $this->distance($prev, $point);
            $prev = $point;
        }
        $dist += $this->distance($prev, ['lat' => $homeCoords['lat'], 'lng' => $homeCoords['lng']]); // Return to home

        return $dist;
    }
    protected function calculateTimetable(array $route): array
    {
        $startTime = \DateTime::createFromFormat('H:i', $this->config['start_time']);
        $appointmentLength = (int)$this->config['appointment_length'];
        $breakLength = (int)$this->config['break_length'];

        $morning = [];
        $afternoon = [];
        $currentTime = clone $startTime;

        // Get home coordinates
        $homeCoords = $this->geocodeAddress($this->home['address'] . ', ' . $this->home['postal_code'] . ' ' . $this->home['city']);
        if (!$homeCoords) {
             $this->logger->error('Failed to geocode home address in calculateTimetable: ' . $this->home['address'], ['module' => 'AppointmentManager', 'object_type' => 'ItineraryGenerator']);
            return [];
        }

        $prevPoint = ['lat' => $homeCoords['lat'], 'lng' => $homeCoords['lng']]; // Start from home

        foreach ($route as $point) {
            // Travel Time using Google Maps Distance Matrix API
            $travelTime = $this->getTravelTime($prevPoint, $point);
            if($travelTime === null) {
                // If travel time cannot be calculated, skip this appointment.  It's
                // better to skip one than to have the whole itinerary fail.
                $this->logger->error('Failed to get travel time. Skipping appointment.', ['module' => 'AppointmentManager', 'object_type' => 'ItineraryGenerator']);
                continue;  // Skip to the next appointment
            }

            // Add travel time
            $currentTime->modify('+' . $travelTime . ' minutes');

            // Check if it's morning or afternoon and add to the appropriate array
            if ($currentTime < (clone $startTime)->setTime(12, 0)) {
                $morning[] = [
                    'time' => $currentTime->format('H:i'),
                    'name' => $point['name'],
                    'address' => $point['address'],
                    'postal_city' => $point['postal_code'] . ' ' . $point['city'],
                ];
            } else {
                $afternoon[] = [
                    'time' => $currentTime->format('H:i'),
                    'name' => $point['name'],
                    'address' => $point['address'],
                    'postal_city' => $point['postal_code'] . ' ' . $point['city'],
                ];
            }

            // Add appointment length
            $currentTime->modify('+' . $appointmentLength . ' minutes');


            if ($currentTime->format('H:i') >= '12:00' && $currentTime->format('H:i') < '13:00') {

                $currentTime->setTime(13, 0, 0); // Correct time to 13:00:00
                // Add breakLength *after* setting the time to 13:00:00
                $currentTime->modify('+' . $breakLength . ' minutes');
            }

            $prevPoint = $point;
        }

        return [
            'date' => new \DateTime(),
            'morning' => $morning,
            'afternoon' => $afternoon,
        ];
    }


    protected function geocodeAddress(string $address): ?array
    {
       // ... (geocodeAddress method with cURL and error handling) ...
        $apiKey = $this->config['google_api_key'];
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($address) . '&key=' . $apiKey;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);


        if (curl_errno($ch)) {
            $this->logger->error('cURL error in geocodeAddress: ' . curl_error($ch), ['module' => 'AppointmentManager', 'object_type' => 'ItineraryGenerator']); // Use Symfony logger
            curl_close($ch);
            return null;
        }
        curl_close($ch);

        $data = json_decode($response, true);

        if ($data && $data['status'] == 'OK' && isset($data['results'][0]['geometry']['location'])) {
            return [
                'lat' => $data['results'][0]['geometry']['location']['lat'],
                'lng' => $data['results'][0]['geometry']['location']['lng'],
            ];
        } else {
             $this->logger->error("Geocoding error for address: $address - Response: " . print_r($data, true), ['module' => 'AppointmentManager', 'object_type' => 'ItineraryGenerator']); // Use Symfony logger and include response
            return null;
        }
    }

    protected function getTravelTime(array $origin, array $destination): ?int
    {
        // ... (getTravelTime method with cURL and error handling, return null on failure) ...
        $apiKey = $this->config['google_api_key'];
        $originStr = $origin['lat'] . ',' . $origin['lng'];
        $destinationStr = $destination['lat'] . ',' . $destination['lng'];

        $url = 'https://maps.googleapis.com/maps/api/distancematrix/json?origins=' . urlencode($originStr) . '&destinations=' . urlencode($destinationStr) . '&key=' . $apiKey . '&units=metric&mode=driving'; // Use driving mode and metric units

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
           $this->logger->error('cURL error in getTravelTime: ' . curl_error($ch), ['module' => 'AppointmentManager', 'object_type' => 'ItineraryGenerator']); // Use Symfony logger
            curl_close($ch);
            return null; // Return null on error.
        }

        curl_close($ch);
        $data = json_decode($response, true);


        if ($data && $data['status'] == 'OK' && $data['rows'][0]['elements'][0]['status'] == 'OK') {
            $durationInSeconds = $data['rows'][0]['elements'][0]['duration']['value'];
            return (int)round($durationInSeconds / 60); // Convert seconds to minutes
        } else {
             $this->logger->error("Distance Matrix API error - Response: " . print_r($data, true), ['module' => 'AppointmentManager', 'object_type' => 'ItineraryGenerator']); // Log full response
            return null; // Return null on API error
        }
    }
}
