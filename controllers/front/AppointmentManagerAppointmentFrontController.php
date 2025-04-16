<?php
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

use ModuleFrontController;

if (!defined('_PS_VERSION_')) {
    exit;
}

use ModuleFrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PrestaShop\Module\AppointmentManager\Form\AppointmentManagerAppointmentFormType;
use Db;

class AppointmentManagerAppointmentFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $request = $this->getRequest();
        $form = $this->createForm(AppointmentManagerAppointmentFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            if (empty($data['phone']) && empty($data['email'])) {
                $this->addFlash('danger', $this->trans('You must provide at least a phone number or an email address.', 'Modules.Appointmentmanager.Shop'));
            } else {
                try {
                    $success = Db::getInstance()->insert('appointment_manager', [
                        'lastname' => pSQL($data['lastname']),
                        'firstname' => pSQL($data['firstname']),
                        'address' => pSQL($data['address']),
                        'postal_code' => pSQL($data['postal_code']),
                        'city' => pSQL($data['city']),
                        'phone' => pSQL($data['phone']),
                        'email' => pSQL($data['email']),
                        'rdv_option_1' => pSQL($data['rdv_option_1']),
                        'rdv_option_2' => pSQL($data['rdv_option_2']),
                        'GDPR' => (new \DateTime())->format('Y-m-d H:i:s'),
                    ]);

                    if ($success) {
                        $this->addFlash('success', $this->trans('Your appointment request has been successfully registered.', 'Modules.Appointmentmanager.Shop'));
                        return $this->redirect($this->get('router')->generate('homepage'));

                    } else {
                        $this->addFlash('danger', $this->trans('An error occurred while saving your request.', 'Modules.Appointmentmanager.Shop'));
                    }

                } catch (\PrestaShopDatabaseException $e) {
                    $this->addFlash('danger', $this->trans('An error occurred while saving your request.', 'Modules.Appointmentmanager.Shop').' '.$e->getMessage());
                }
            }
        }

        $this->context->smarty->assign([
            'appointmentForm' => $form->createView(),
        ]);

        $this->setTemplate('module:appointmentmanager/views/templates/front/appointment_form.html.twig');
    }
}
