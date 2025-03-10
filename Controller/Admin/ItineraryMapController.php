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
use AppointmentManager\Classes\ItineraryGenerator;

class ItineraryMapController extends FrameworkBundleAdminController
{
    public function indexAction(Request $request)
    {
        $appointmentIds = $request->get('appointments', array());
        if (empty($appointmentIds)) {
            $this->addFlash('error', 'No appointments selected.');
            return $this->redirectToRoute('admin_appointmentmanager_customerlist');
        }
        $ids = implode(',', array_map('intval', $appointmentIds));
        $sql = 'SELECT * FROM `'._DB_PREFIX_.'appointment_manager` WHERE id_appointment_manager IN ('.$ids.')';
        $appointments = \Db::getInstance()->executeS($sql);
        $homeSql = 'SELECT * FROM `'._DB_PREFIX_.'appointment_manager` WHERE ishome=1 LIMIT 1';
        $homePlace = \Db::getInstance()->getRow($homeSql);
        $config = array(
            'start_time'         => \Configuration::get('APPOINTMENTMANAGER_START_TIME'),
            'appointment_length' => \Configuration::get('APPOINTMENTMANAGER_APPOINTMENT_LENGTH'),
            'break_length'       => \Configuration::get('APPOINTMENTMANAGER_BREAK_LENGTH'),
            'google_api_key'     => \Configuration::get('APPOINTMENTMANAGER_GOOGLE_API_KEY')
        );
        $generator = new ItineraryGenerator($homePlace, $appointments, $config);
        $itinerary = $generator->generateItinerary();
        return $this->render('@Modules/appointmentmanager/views/templates/admin/itinerary_map.html.twig', [
            'itinerary'     => $itinerary,
            'google_api_key'=> $config['google_api_key']
        ]);
    }
}
