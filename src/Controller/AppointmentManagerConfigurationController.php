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

declare(strict_types=1);

namespace PrestaShop\Module\AppointmentManager\Controller;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AppointmentManagerConfigurationController extends FrameworkBundleAdminController
{
    public function index(Request $request): Response
    {
        $appointmentmanagerFormDataHandler = $this->get('prestashop.module.appointmentmanager.form.appointmentmanager_data_handler');

        $appointmentmanagerForm = $appointmentmanagerFormDataHandler->getForm();
        $appointmentmanagerForm->handleRequest($request);

        if ($appointmentmanagerForm->isSubmitted() && $appointmentmanagerForm->isValid()) {
            /** You can return array of errors in form handler and they can be displayed to user with flashErrors */
            $errors = $appointmentmanagerFormDataHandler->save($appointmentmanagerForm->getData());

            if (empty($errors)) {
                $this->addFlash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));

                return $this->redirectToRoute('appointment_manager_config');
            }

            $this->flashErrors($errors);
        }

        return $this->render('@Modules/appointmentmanager/views/templates/admin/form.html.twig', [
            'AppointmentManagerConfig' => $appointmentmanagerForm->createView()
        ]);
    }
}
