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

namespace PrestaShop\Module\AppointmentManager\Controller\Front;

use PrestaShop\Module\AppointmentManager\Form\AppointmentManagerAppointmentFormType;
use Doctrine\DBAL\Connection;
use PrestaShopBundle\Controller\Front\FrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AppointmentManagerAppointmentFrontController extends FrontController
{
    public function index(Request $request, Connection $connection): Response
    {
        $form = $this->createForm(AppointmentType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            if (empty($data['phone']) && empty($data['email'])) {
                $this->addFlash('danger', 'Vous devez renseigner au moins un téléphone ou un email.');
                return $this->redirectToRoute('appointment_form');
            }

            $connection->insert('appointment_manager', [
                'lastname' => $data['lastname'],
                'firstname' => $data['firstname'],
                'address' => $data['address'],
                'postal_code' => $data['postal_code'],
                'city' => $data['city'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'rdv_option_1' => $data['rdv_option_1'],
                'rdv_option_2' => $data['rdv_option_2'],
                'GDPR' => (new \DateTime())->format('Y-m-d H:i:s'),
            ]);

            $this->addFlash('success', 'Votre demande de rendez-vous a bien été enregistrée.');
            return $this->redirectToRoute('admin_appointment_front_form');
        }

        return $this->render('@Modules/appointmentmanager/views/templates/front/appointment_form.html.twig', [
            'appointmentForm' => $form->createView(),
        ]);
    }
}
