<?php
/**
 * CustomerListController
 *
 * Author: Roberto Minini (iepiep74@gmail.com)
 * License: MIT License
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

namespace AppointmentManager\Controller\Admin;

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