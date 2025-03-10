<?php
/**
 * @author Roberto Minini <r.minini@solution61.fr>
 * @copyright 2025 Roberto Minini
 * @license MIT
 *
 * This file is part of the AppointmentManager Module
 * License: MIT License
 */

 namespace AppointmentManager\Controller\Front;

 if (!defined('_PS_VERSION_')) {
    exit;
}

use ModuleFrontController;
use Symfony\Component\HttpFoundation\Request;

class AppointmentFormModuleFrontController extends ModuleFrontController
{
    public function indexAction(Request $request)
    {
        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $required = array('lastname', 'firstname', 'address', 'postal_code', 'city', 'rdv_option_1', 'rdv_option_2', 'GDPR');
            $errors = array();
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $errors[] = 'Field '.$field.' is required.';
                }
            }
            if (empty($data['phone']) && empty($data['email'])) {
                $errors[] = 'At least one of phone or email must be provided.';
            }
            if (!empty($errors)) {
                $this->context->smarty->assign('errors', $errors);
            } else {
                $sql = 'INSERT INTO `'._DB_PREFIX_.'appointment_manager` 
                    (`lastname`,`firstname`,`address`,`postal_code`,`city`,`phone`,`email`,`rdv_option_1`,`rdv_option_2`,`GDPR`,`visited`,`ishome`,`istest`)
                    VALUES (
                        "'.pSQL($data['lastname']).'",
                        "'.pSQL($data['firstname']).'",
                        "'.pSQL($data['address']).'",
                        "'.pSQL($data['postal_code']).'",
                        "'.pSQL($data['city']).'",
                        "'.pSQL($data['phone']).'",
                        "'.pSQL($data['email']).'",
                        "'.pSQL($data['rdv_option_1']).'",
                        "'.pSQL($data['rdv_option_2']).'",
                        NOW(),
                        0,
                        0,
                        0
                    )';
                \Db::getInstance()->execute($sql);
                $this->context->smarty->assign('success', 'Appointment submitted successfully.');
            }
        }
        $dates = array();
        $today = new \DateTime();
        $interval = new \DateInterval('P1D');
        $end = clone $today;
        $end->modify('+14 days');
        while ($today <= $end) {
            if (!in_array($today->format('N'), array(6, 7))) {
                $dates[] = $today->format('l d/m/y');
                $dates[] = $today->format('l d/m/y').' APRES-MIDI';
            }
            $today->add($interval);
        }
        return $this->render('@Modules/appointmentmanager/views/templates/front/appointment_form.html.twig', ['dates' => $dates]);
    }
}
