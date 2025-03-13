<?php
/**
 * @author Roberto Minini <r.minini@solution61.fr>
 * @copyright 2025 Roberto Minini
 * @license MIT
 *
 * This file is part of the dimrdv project.
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

 namespace PrestaShop\Module\AppointmentManager\Controller\Admin;

use PrestaShop\Module\AppointmentManager\Service\ItineraryService;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

if (!defined('_PS_VERSION_')) {
    exit;
}

class AppointmentManagerItineraryController extends FrameworkBundleAdminController
{
    private $itineraryService;
    private $googleApiKey;

    public function __construct(ItineraryService $itineraryService, string $googleApiKey)
    {
        $this->itineraryService = $itineraryService;
        $this->googleApiKey = $googleApiKey;
    }

    public function index(Request $request): Response
    {
        $selectedIds = $request->request->get('selected', []);

        if (empty($selectedIds) || !is_array($selectedIds)) {
            $this->addFlash('error', $this->trans('No appointments selected.', 'Modules.Dimrdv.Admin'));

            return $this->redirectToRoute('admin_dimrdv_gestionrdv_index'); // Redirect back to the RDV list
        }

        try {
            $itineraryData = $this->itineraryService->calculateItinerary($selectedIds, $this->googleApiKey);
        } catch (\Exception $e) {
            $this->addFlash('error', $this->trans('Error calculating itinerary: %error%', ['%error%' => $e->getMessage()], 'Modules.Dimrdv.Admin'));

            return $this->redirectToRoute('admin_dimrdv_gestionrdv_index');
        }

        return $this->render('@Modules/dimrdv/views/templates/admin/itinerary.html.twig', [
            'optimized_route' => $itineraryData['optimized_route'],
            'itinerary_schedule' => $itineraryData['itinerary_schedule'],
            'google_maps_api_key' => $this->googleApiKey,
        ]);
    }
}
