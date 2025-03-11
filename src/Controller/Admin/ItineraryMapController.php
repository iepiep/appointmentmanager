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
use Symfony\Component\DependencyInjection\ContainerInterface;
use PrestaShopLogger; // Import PrestaShopLogger

class ItineraryMapController extends FrameworkBundleAdminController
{
    public function indexAction(Request $request)
    {
        $appointmentIds = $request->get('appointments', []);
        if (empty($appointmentIds)) {
            $this->addFlash('error', 'No appointments selected.'); // Use Symfony flash messages
            return $this->redirectToRoute('admin_appointmentmanager_customerlist');
        }

        // Sanitize input: Ensure IDs are integers
        $ids = implode(',', array_map('intval', $appointmentIds));

        // Get appointment data
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'appointment_manager` WHERE id_appointment_manager IN (' . $ids . ')';
        $appointments = \Db::getInstance()->executeS($sql);

        if (!$appointments) {
            $this->addFlash('error', $this->trans('No appointments found with the given IDs.', [], 'Modules.Appointmentmanager.Admin'));
            return $this->redirectToRoute('admin_appointmentmanager_customerlist');
        }

        // Get home address (assuming ishome=1 is your home address marker)
        $homeSql = 'SELECT * FROM `' . _DB_PREFIX_ . 'appointment_manager` WHERE ishome=1 LIMIT 1';
        $homePlace = \Db::getInstance()->getRow($homeSql);

        if (!$homePlace) {
            $this->addFlash('error', $this->trans('Home address not found. Please set a home address.', [], 'Modules.Appointmentmanager.Admin')); // Clearer error message
            return $this->redirectToRoute('admin_appointmentmanager_customerlist'); // Or perhaps redirect to a config page
        }
        // Get configuration values
        $config = [
            'start_time' => \Configuration::get('APPOINTMENTMANAGER_START_TIME'),
            'appointment_length' => \Configuration::get('APPOINTMENTMANAGER_APPOINTMENT_LENGTH'),
            'break_length' => \Configuration::get('APPOINTMENTMANAGER_BREAK_LENGTH'),
            'google_api_key' => \Configuration::get('APPOINTMENTMANAGER_GOOGLE_API_KEY'),
        ];

          // Check for Google API key
        if (empty($config['google_api_key'])) {
            $this->addFlash('error', $this->trans('Google API Key is not set. Please configure it in the module settings.', [], 'Modules.Appointmentmanager.Admin'));
             return $this->redirectToRoute('admin_appointmentmanager_config'); // Redirect to the config page
        }

        // Generate the itinerary
        $itineraryGenerator = $this->get('appointmentmanager.itinerary_generator');
        $itinerary = $itineraryGenerator->generateItinerary();

          // Check if itinerary generation was successful
        if (empty($itinerary)) {
            $this->addFlash('error', $this->trans('Failed to generate itinerary.  Check error logs for details.', [], 'Modules.Appointmentmanager.Admin'));
            return $this->redirectToRoute('admin_appointmentmanager_customerlist'); // Redirect back to the list
        }
        // Render the itinerary map view
        return $this->render('@Modules/appointmentmanager/views/templates/admin/itinerary_map.html.twig', [
            'itinerary' => $itinerary,
            'google_api_key' => $config['google_api_key'],
        ]);
    }
}
