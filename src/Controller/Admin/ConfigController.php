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
use AppointmentManager\Form\ConfigType;

class ConfigController extends FrameworkBundleAdminController
{
    public function indexAction(Request $request)
    {
        die('ConfigController indexAction reached'); // ADDED FOR DEBUGGING

        $data = array(
            'google_api_key'      => \Configuration::get('APPOINTMENTMANAGER_GOOGLE_API_KEY'),
            'start_time'          => \Configuration::get('APPOINTMENTMANAGER_START_TIME'),
            'appointment_length'  => \Configuration::get('APPOINTMENTMANAGER_APPOINTMENT_LENGTH'),
            'break_length'        => \Configuration::get('APPOINTMENTMANAGER_BREAK_LENGTH')
        );
        $form = $this->createForm(ConfigType::class, $data);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $configData = $form->getData();
            \Configuration::updateValue('APPOINTMENTMANAGER_GOOGLE_API_KEY', $configData['google_api_key']);
            \Configuration::updateValue('APPOINTMENTMANAGER_START_TIME', $configData['start_time']);
            \Configuration::updateValue('APPOINTMENTMANAGER_APPOINTMENT_LENGTH', $configData['appointment_length']);
            \Configuration::updateValue('APPOINTMENTMANAGER_BREAK_LENGTH', $configData['break_length']);
            $this->addFlash('success', 'Configuration updated successfully.');
            return $this->redirectToRoute('admin_appointmentmanager_config');
        }
        return $this->render('@Modules/appointmentmanager/views/templates/admin/config.html.twig', ['form' => $form->createView()]);
    }
}
