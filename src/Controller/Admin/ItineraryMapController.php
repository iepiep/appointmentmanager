<?php
/**
 * @author Roberto Minini <r.minini@solution61.fr>
 * @copyright 2025 Roberto Minini
 * @license MIT
 *
 * This file is part of the AppointmentManager Module
 * License: MIT License
 */

namespace AppointmentManager\Controller\Admin;

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;
use AppointmentManager\Service\ItineraryGenerator;
use PrestaShopLogger;

class ItineraryMapController extends FrameworkBundleAdminController
{
    public function indexAction(Request $request)
    {
        $appointmentIds = $request->get('appointments', []);
        if (empty($appointmentIds)) {
            $this->addFlash('error', $this->trans('No appointments selected.', [], 'Modules.Appointmentmanager.Admin')); // Use translations
            return $this->redirectToRoute('admin_appointmentmanager_customerlist');
        }

        // Sanitize: Ensure IDs are integers
        $ids = implode(',', array_map('intval', $appointmentIds));

        // Get appointment data
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'appointment_manager` WHERE id_appointment_manager IN (' . $ids . ')';
        $appointments = \Db::getInstance()->executeS($sql);

        if (!$appointments) {
            $this->addFlash('error', $this->trans('No appointments found with the given IDs.', [], 'Modules.Appointmentmanager.Admin')); // Clearer error message
            return $this->redirectToRoute('admin_appointmentmanager_customerlist');
        }

        // Get configuration
        $config = [
            'start_time' => \Configuration::get('APPOINTMENTMANAGER_START_TIME'),
            'appointment_length' => \Configuration::get('APPOINTMENTMANAGER_APPOINTMENT_LENGTH'),
            'break_length' => \Configuration::get('APPOINTMENTMANAGER_BREAK_LENGTH'),
            'google_api_key' => \Configuration::get('APPOINTMENTMANAGER_GOOGLE_API_KEY'),
            'home_address'      => \Configuration::get('APPOINTMENTMANAGER_HOME_ADDRESS'),
            'home_postal_code'  => \Configuration::get('APPOINTMENTMANAGER_HOME_POSTAL_CODE'),
            'home_city'         => \Configuration::get('APPOINTMENTMANAGER_HOME_CITY'),
        ];
        // Check for Google API key
        if (empty($config['google_api_key'])) {
            $this->addFlash('error', $this->trans('Google API Key is not set. Please configure it in the module settings.', [], 'Modules.Appointmentmanager.Admin'));
            return $this->redirectToRoute('admin_appointmentmanager_config'); // Redirect to config
        }
        // Check for Home Address
        if (empty($config['home_address']) || empty($config['home_postal_code']) || empty($config['home_city'])) {
            $this->addFlash('error', $this->trans('Home address is not set. Please configure it in the module settings.', [], 'Modules.Appointmentmanager.Admin'));
            return $this->redirectToRoute('admin_appointmentmanager_config');
        }
        // Format home address for the ItineraryGenerator
        $homeAddress = [
            'address' => $config['home_address'],
            'postal_code' => $config['home_postal_code'],
            'city' => $config['home_city'],
        ];

        // Generate Itinerary
        $itineraryGenerator = $this->get('appointmentmanager.itinerary_generator');

        try {
            $itinerary = $itineraryGenerator->generateItinerary($homeAddress, $appointments, $config);
        } catch (\Exception $e) {
            $this->addFlash('error', $this->trans('An error occurred while generating the itinerary: %error%', ['%error%' => $e->getMessage()], 'Modules.Appointmentmanager.Admin'));
            PrestaShopLogger::addLog('Itinerary generation error: ' . $e->getMessage(), 3); // Log detailed error
            return $this->redirectToRoute('admin_appointmentmanager_customerlist');
        }

        if (empty($itinerary)) {
            $this->addFlash('error', $this->trans('Failed to generate itinerary.  Check error logs for details.', [], 'Modules.Appointmentmanager.Admin'));
            return $this->redirectToRoute('admin_appointmentmanager_customerlist'); // Redirect back to the list
        }

        // Render
        return $this->render('@Modules/appointmentmanager/views/templates/admin/itinerary_map.html.twig', [
            'itinerary' => $itinerary,
            'google_api_key' => $config['google_api_key'],
        ]);
    }
}
