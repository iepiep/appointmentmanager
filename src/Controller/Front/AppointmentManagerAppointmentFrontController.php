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

use PrestaShopBundle\Controller\Front\FrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PrestaShop\Module\AppointmentManager\Form\AppointmentManagerAppointmentFormType;

class AppointmentManagerAppointmentFrontController extends FrontController
{
    public function index(Request $request): Response
    {
        // Créer le formulaire à l'aide du FormType
        $form = $this->createForm(AppointmentManagerAppointmentFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Vérification : au moins un champ (phone ou email) doit être rempli
            if (empty($data['phone']) && empty($data['email'])) {
                $this->addFlash('danger', 'Vous devez renseigner au moins un téléphone ou un email.');
            } else {
                // Insertion en base de données
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
            }
            $this->addFlash('success', 'Votre demande de rendez-vous a bien été enregistrée.');
            // Redirection (par exemple, rediriger vers la même page ou vers une page de confirmation)
            return $this->redirectToRoute('appointment_front_form');
        }

        // Rendre le formulaire via un template Twig
        return $this->render('@Modules/appointmentmanager/views/templates/front/appointment_form.html.twig', [
            'appointmentForm' => $form->createView(),
        ]);
    }
}
