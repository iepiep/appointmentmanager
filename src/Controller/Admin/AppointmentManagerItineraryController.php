<?php
/**
 * @author Roberto Minini <r.minini@solution61.fr>
 * @copyright 2025 Roberto Minini
 * @license MIT
 *
 * This file is part of the Appointment Manager project.
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PrestaShop\Module\AppointmentManager\Controller\Admin;

use PrestaShop\Module\AppointmentManager\Service\ItineraryService;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Configuration;

if (!defined('_PS_VERSION_')) {
    exit;
}

class AppointmentManagerItineraryController extends FrameworkBundleAdminController
{
    private $itineraryService;

    public function __construct(ItineraryService $itineraryService)
    {
        $this->itineraryService = $itineraryService;
    }

    public function index(Request $request): Response
    {
        $selectedIds = $request->request->get('appointments', []);

        if (empty($selectedIds) || !is_array($selectedIds)) {
            $this->addFlash('error', $this->trans('No appointments selected.', 'Modules.Appointmentmanager.Admin'));

            return $this->redirectToRoute('appointment_manager_appointment_list'); // Redirect back to the RDV list
        }

        $googleApiKey = Configuration::get('APPOINTMENTMANAGER_GOOGLE_API_KEY');

        try {
            $itineraryData = $this->itineraryService->calculateItinerary($selectedIds, $googleApiKey);
        } catch (\Exception $e) {
            $this->addFlash('error', $this->trans('Error calculating itinerary: %error%', ['%error%' => $e->getMessage()], 'Modules.Appointmentmanager.Admin'));

            return $this->redirectToRoute('appointment_manager_appointment_list');
        }

        return $this->render('@Modules/appointmentmanager/views/templates/admin/itinerary.html.twig', [
            'optimized_route' => $itineraryData['optimized_route'],
            'itinerary_schedule' => $itineraryData['itinerary_schedule'],
            'google_maps_api_key' => $googleApiKey,
        ]);
    }
}
