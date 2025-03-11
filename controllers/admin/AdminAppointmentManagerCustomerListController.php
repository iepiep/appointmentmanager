<?php
/**
 * @author Roberto Minini <r.minini@solution61.fr>
 * @copyright 2025 Roberto Minini
 * @license MIT
 *
 * This file is part of the AppointmentManager Module
 * License: MIT License
 */

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminAppointmentManagerCustomerListController extends FrameworkBundleAdminController
{
    /**
     * @Route("/modules/appointmentmanager/customerlist", name="appointment_manager_customer_list")
     */
    public function indexAction(): Response
    {
        return $this->render('@Modules/appointmentmanager/views/templates/admin/customer_list.html.twig', [
            'module_dir' => $this->container->get('prestashop.module.path.provider')->getModulePath('appointmentmanager'),
            'text_customer_list' => $this->trans('This is the customer list page for Appointment Manager.', 'Modules.Appointmentmanager.Admin'),
        ]);
    }
}