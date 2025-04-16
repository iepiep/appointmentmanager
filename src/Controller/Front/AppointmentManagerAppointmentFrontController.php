<?php
// FILE: src/Controller/Front/AppointmentManagerAppointmentFrontController.php
/**
* @author Roberto Minini <r.minini@solution61.fr>
* @copyright 2025 Roberto Minini
* @license MIT
*
* This file is part of the AppointmentManager project.
* This source file is subject to the MIT license that is bundled
* with this source code in the file LICENSE.
*/

declare(strict_types=1);

// ** CORRECT NAMESPACE **
namespace PrestaShop\Module\AppointmentManager\Controller\Front;

// ** USE CORRECT BASE CONTROLLER & OTHER SYMFONY COMPONENTS **
use PrestaShopBundle\Controller\FrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PrestaShop\Module\AppointmentManager\Form\AppointmentManagerAppointmentFormType;
use Db;
use PrestaShopDatabaseException; // Import exception class
use DateTime; // Import DateTime class

if (!defined('_PS_VERSION_')) {
    exit;
}

// ** EXTEND FrontController **
class AppointmentManagerAppointmentFrontController extends FrontController
{
    // ** CHANGE METHOD SIGNATURE TO MATCH ROUTE DEFINITION **
    public function index(Request $request): Response
    {
        // No need for parent::initContent() when extending FrontController

        $form = $this->createForm(AppointmentManagerAppointmentFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Simple validation: Ensure at least phone or email is provided
            if (empty($data['phone']) && empty($data['email'])) {
                $this->addFlash('danger', $this->trans('You must provide at least a phone number or an email address.', [], 'Modules.Appointmentmanager.Shop'));
            } else {
                try {
                    // Use pSQL for security, and get current datetime for GDPR
                    $success = Db::getInstance()->insert('appointment_manager', [
                        'lastname' => pSQL($data['lastname']),
                        'firstname' => pSQL($data['firstname']),
                        'address' => pSQL($data['address']),
                        'postal_code' => pSQL($data['postal_code']),
                        'city' => pSQL($data['city']),
                        'phone' => pSQL($data['phone'] ?? ''), // Handle potential null
                        'email' => pSQL($data['email'] ?? ''), // Handle potential null
                        'rdv_option_1' => pSQL($data['rdv_option_1']),
                        'rdv_option_2' => pSQL($data['rdv_option_2']),
                        'GDPR' => (new DateTime())->format('Y-m-d H:i:s'), // Use current time
                        // Defaults for visited, ishome, istest are handled by DB schema
                    ]);

                    if ($success) {
                        $this->addFlash('success', $this->trans('Your appointment request has been successfully registered.', [], 'Modules.Appointmentmanager.Shop'));
                        // ** REDIRECT USING SYMFONY ROUTER **
                        return $this->redirectToRoute('index'); // Redirect to homepage route

                    } else {
                        $this->addFlash('danger', $this->trans('An error occurred while saving your request.', [], 'Modules.Appointmentmanager.Shop'));
                    }

                } catch (PrestaShopDatabaseException $e) {
                    // Log the detailed error for debugging
                    // PrestaShopLogger::addLog('AppointmentManager DB Error: ' . $e->getMessage(), 3);
                    $this->addFlash('danger', $this->trans('An error occurred while saving your request. Please try again later.', [], 'Modules.Appointmentmanager.Shop'));
                }
            }
            // If validation failed or DB error occurred, re-render the form with errors/flashes
        }

        // ** RENDER TWIG TEMPLATE USING SYMFONY RENDER METHOD **
        return $this->render('@Modules/appointmentmanager/views/templates/front/appointment_form.html.twig', [
            'appointmentForm' => $form->createView(),
            'layout' => $this->getLayout(), // Pass layout variable
            'page' => $this->getPage(),     // Pass page variable for template context
        ]);
    }

    // ** Helper to get the page object - needed for FrontController **
    private function getPage()
    {
        // You might need to adjust this based on your specific theme context needs
        // This provides basic page variables.
        $page = parent::makePage(); // Use parent helper if available, otherwise create manually
        $page['title'] = $this->trans('Make an Appointment', [], 'Modules.Appointmentmanager.Shop');
        $page['meta']['title'] = $page['title'];
        $page['body_classes']['page-appointment-form'] = true; // Add a custom body class
        return $page;
    }

    // ** Optional: Define breadcrumbs if needed **
    protected function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();
        $breadcrumb['links'][] = $this->addMyAccountToBreadcrumb(); // Example
        $breadcrumb['links'][] = [
            'title' => $this->trans('Make an Appointment', [], 'Modules.Appointmentmanager.Shop'),
            'url' => $this->generateUrl('appointment_manager_form'), // Use route name
        ];
        return $breadcrumb;
    }
}