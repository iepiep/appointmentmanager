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

use PrestaShopLogger;

class ItineraryGenerator
{
    protected array $home;
    protected array $appointments;
    protected array $config;

    public function __construct(array $home, array $appointments, array $config)
    {
        $this->home = $home;
        $this->appointments = $appointments;
        $this->config = $config;
    }

    public function generateItinerary(): array
    {
        $points = [];

        // Geocode home address (only once)
        $homeCoords = $this->geocodeAddress($this->home['address'] . ', ' . $this->home['postal_code'] . ' ' . $this->home['city']);
        if (!$homeCoords) {
            PrestaShopLogger::addLog('Failed to geocode home address: ' . $this->home['address'], 3, null, 'ItineraryGenerator', 0, true); // Log error
            return []; // Return an empty array, indicating failure
        }

        foreach ($this->appointments as $appt) {
            // Geocode *each* appointment address
            $coords = $this->geocodeAddress($appt['address'] . ', ' . $appt['postal_code'] . ' ' . $appt['city']);
            if ($coords) {
                $points[] = [
                    'id' => $appt['id_appointment_manager'],
                    'name' => $appt['firstname'] . ' ' . $appt['lastname'],
                    'address' => $appt['address'],
                    'postal_code' => $appt['postal_code'],
                    'city' => $appt['city'],
                    'lat' => $coords['lat'],  // Use coordinates from geocoding
                    'lng' => $coords['lng'],  // Use coordinates from geocoding
                ];
            } else {
                PrestaShopLogger::addLog('Failed to geocode address: ' . $appt['address'], 2, null, 'ItineraryGenerator', $appt['id_appointment_manager'], true); // Log with appointment ID
                // Optionally, you could add a flag to indicate that this appointment
                // should be excluded from the itinerary, or use a default location.
            }
        }

        if (empty($points)) { // Check that we have valid appointments
            PrestaShopLogger::addLog('No valid appointments found after geocoding.', 2, null, 'ItineraryGenerator', 0, true);
            return []; // No appointments, so return an empty array
        }

        $start = [
            'id' => 0,
            'name' => 'HomePlace',
            'address' => $this->home['address'],
            'postal_code' => $this->home['postal_code'],
            'city' => $this->home['city'],
            'lat' => $homeCoords['lat'],  // Use geocoded home coordinates
            'lng' => $homeCoords['lng'],  // Use geocoded home coordinates
        ];

        $route = $this->tspNearestNeighbor($start, $points);
        $route = $this->twoOptOptimization($route);
        $itinerary = $this->calculateTimetable($route); // Calculate the timetable using travel times
        return $itinerary;
    }

    protected function tspNearestNeighbor(array $start, array $points): array
    {
        $route = [];
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

    protected function twoOptOptimization(array $route): array
    {
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
        $dist = 0;
        $homeCoords = $this->geocodeAddress($this->home['address'] . ', ' . $this->home['postal_code'] . ' ' . $this->home['city']);

        // Handle geocoding failure (e.g., return a large distance, log an error)
        if (!$homeCoords) {
            PrestaShopLogger::addLog('Failed to geocode home address for route distance calculation: ' . $this->home['address'], 3);
            return PHP_FLOAT_MAX; // Return a very large distance to make this route unlikely to be chosen
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
        $appointmentLength = (int) $this->config['appointment_length'];
        $breakLength = (int) $this->config['break_length'];

        $morning = [];
        $afternoon = [];
        $currentTime = clone $startTime;

        // Get home coordinates for starting point
        $homeCoords = $this->geocodeAddress($this->home['address'] . ', ' . $this->home['postal_code'] . ' ' . $this->home['city']);
        if (!$homeCoords) {
            PrestaShopLogger::addLog('Failed to geocode home address in calculateTimetable: ' . $this->home['address'], 3);
            return []; // or some other appropriate error handling.
        }

        $prevPoint = ['lat' => $homeCoords['lat'], 'lng' => $homeCoords['lng']]; // Start from home.

        foreach ($route as $point) {
            // Calculate Travel Time using Google Maps Distance Matrix API
            $travelTime = $this->getTravelTime($prevPoint, $point);

            // Add travel time to the current time
            $currentTime->modify('+' . $travelTime . ' minutes');

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

            $currentTime->modify('+' . $appointmentLength . ' minutes');

            //  Check for lunch break and adjust.
            if ($currentTime->format('H:i') >= '12:00' && $currentTime->format('H:i') < '13:00') {
                $currentTime->setTime(13, 0); // Correct time to 13.
                $currentTime->modify('+' . $breakLength . ' minutes');
            }
            $prevPoint = $point; // Update the previous point for the next iteration
        }

        return [
            'date' => new \DateTime(),
            'morning' => $morning,
            'afternoon' => $afternoon,
        ];
    }

    protected function geocodeAddress(string $address): ?array
    {
        $apiKey = $this->config['google_api_key'];
        // URL encode the address for the API request
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($address) . '&key=' . $apiKey;

        // Use cURL to make the API request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //  Not recommended for production - better to configure CA properly
        $response = curl_exec($ch);

        // Check for cURL errors
        if (curl_errno($ch)) {
            PrestaShopLogger::addLog('cURL error in geocodeAddress: ' . curl_error($ch), 3);
            curl_close($ch);
            return null;
        }
        curl_close($ch);

        $data = json_decode($response, true);

        // Check API response status
        if ($data && $data['status'] == 'OK' && isset($data['results'][0]['geometry']['location'])) {
            return [
                'lat' => $data['results'][0]['geometry']['location']['lat'],
                'lng' => $data['results'][0]['geometry']['location']['lng'],
            ];
        } else {
            // Log detailed error information, including the full API response
            PrestaShopLogger::addLog("Geocoding error for address: $address - Response: " . print_r($data, true), 3);
            return null;
        }
    }

    protected function getTravelTime(array $origin, array $destination): int
    {
        $apiKey = $this->config['google_api_key'];
        $originStr = $origin['lat'] . ',' . $origin['lng'];
        $destinationStr = $destination['lat'] . ',' . $destination['lng'];

        // Build the URL for the Distance Matrix API request
        $url = 'https://maps.googleapis.com/maps/api/distancematrix/json?origins=' . urlencode($originStr) . '&destinations=' . urlencode($destinationStr) . '&key=' . $apiKey . '&units=metric&mode=driving'; // Use driving mode and metric units

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // Not recommended in production
        $response = curl_exec($ch);

        // Check for cURL errors
        if (curl_errno($ch)) {
            PrestaShopLogger::addLog('cURL error in getTravelTime: ' . curl_error($ch), 3);
            curl_close($ch);
            return 15; // Return a default/fallback value (or throw an exception)
        }

        curl_close($ch);
        $data = json_decode($response, true);

        // Check API response status and extract travel time
        if ($data && $data['status'] == 'OK' && $data['rows'][0]['elements'][0]['status'] == 'OK') {
            $durationInSeconds = $data['rows'][0]['elements'][0]['duration']['value'];
            return (int) round($durationInSeconds / 60); // Convert seconds to minutes
        } else {
            PrestaShopLogger::addLog("Distance Matrix API error - Response: " . print_r($data, true), 3);
            return 15; // Return a default/fallback value (or handle differently)
        }
    }
}
