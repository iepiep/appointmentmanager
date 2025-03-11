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

class CustomerListController extends FrameworkBundleAdminController
{
    public function indexAction(Request $request)
    {
        $sql = 'SELECT * FROM `'._DB_PREFIX_.'appointment_manager`';
        $appointments = \Db::getInstance()->executeS($sql);
        return $this->render('@Modules/appointmentmanager/views/templates/admin/customer_list.html.twig', ['appointments' => $appointments]);
    }
}
