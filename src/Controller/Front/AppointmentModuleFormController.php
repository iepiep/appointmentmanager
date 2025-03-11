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
use PrestaShop\PrestaShop\Core\Module\WidgetInterface; // For rendering widgets if needed
use Symfony\Component\HttpFoundation\Request;
use Tools;
use Validate;
use Db;
use PrestaShopDatabaseException;
use PrestaShopLogger;

class AppointmentModuleFormController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();  // Call parent's initContent
        $this->processForm();
        $this->renderForm();
    }
    protected function processForm()
    {
        if (Tools::isSubmit('submitAppointmentForm')) {  // Use a named submit button

            $lastname = Tools::getValue('lastname');
            $firstname = Tools::getValue('firstname');
            $address = Tools::getValue('address');
            $postal_code = Tools::getValue('postal_code');
            $city = Tools::getValue('city');
            $phone = Tools::getValue('phone');
            $email = Tools::getValue('email');
            $rdv_option_1 = Tools::getValue('rdv_option_1');
            $rdv_option_2 = Tools::getValue('rdv_option_2');
            $gdpr = Tools::getValue('GDPR');

            // Basic Validation (add more as needed)
            $errors = [];

            if (empty($lastname)) {
                $errors[] = $this->module->trans('Last name is required.', [], 'Modules.Appointmentmanager.Front');
            }
            if (empty($firstname)) {
                $errors[] = $this->module->trans('First name is required.', [], 'Modules.Appointmentmanager.Front');
            }
            // ... other required field checks ...

            if (empty($phone) && empty($email)) {
                $errors[] = $this->module->trans('At least one of phone or email must be provided.', [], 'Modules.Appointmentmanager.Front');
            }
            if (!empty($email) && !Validate::isEmail($email)) {
                $errors[] = $this->module->trans('Invalid email address.', [], 'Modules.Appointmentmanager.Front');
            }
            if (!Validate::isAddress($address)) {
                $errors[] = $this->module->trans('Invalid address.', [], 'Modules.Appointmentmanager.Front');
            }
            // ... other validations (postal code, city, etc.) ...
            if (!$gdpr) {
                $errors[] = $this->module->trans('You must accept the data processing terms.', [], 'Modules.Appointmentmanager.Front');
            }

            if (!empty($errors)) {
                $this->context->smarty->assign('errors', $errors);
                return; // Stop processing if there are errors
            }
            // Data is valid, insert into database (using prepared statement)
            try {
                $success = Db::getInstance()->insert('appointment_manager', [
                    'lastname'     => pSQL($lastname),
                    'firstname'    => pSQL($firstname),
                    'address'      => pSQL($address),
                    'postal_code'  => pSQL($postal_code),
                    'city'         => pSQL($city),
                    'phone'        => pSQL($phone),
                    'email'        => pSQL($email),
                    'rdv_option_1' => pSQL($rdv_option_1),  // Store full datetime
                    'rdv_option_2' => pSQL($rdv_option_2),  // Store full datetime
                    'GDPR'         => date('Y-m-d H:i:s'), // Now that we have validated, *now* we record the datetime
                    'visited'      => 0,
                    'ishome'       => 0,
                    'istest'       => 0,
                ]);

                if ($success) {
                    $this->context->smarty->assign('success', $this->module->trans('Appointment submitted successfully.', [], 'Modules.Appointmentmanager.Front'));
                    // Optionally, send a confirmation email here
                } else {
                    $this->context->smarty->assign('errors', [$this->module->trans('An error occurred while saving the appointment.', [], 'Modules.Appointmentmanager.Front')]); // Display a generic error
                }

            } catch (PrestaShopDatabaseException $e) {
                // Handle database errors (log, display error message, etc.)
                $this->context->smarty->assign('errors', [$this->module->trans('A database error occurred.', [], 'Modules.Appointmentmanager.Front')]); // Generic error message for the user
                PrestaShopLogger::addLog('Appointment form submission error: ' . $e->getMessage(), 3); // Log the full error

            }
        }
    }

    protected function renderForm()
    {
        // Generate available dates (example, adjust as needed)
        $dates = [];
        $today = new \DateTime();
        $interval = new \DateInterval('P1D');
        $end = (new \DateTime())->modify('+14 days');

        $timeSlots = [
            '09:00', '10:00', '11:00',  // Morning slots
            '14:00', '15:00', '16:00',  // Afternoon slots
        ];

        while ($today <= $end) {
            if (!in_array($today->format('N'), [6, 7])) { // Exclude weekends
                foreach ($timeSlots as $timeSlot) {
                    $dateTime = clone $today;
                    list($hours, $minutes) = explode(':', $timeSlot);
                    $dateTime->setTime($hours, $minutes);
                    $dates[] = $dateTime->format('Y-m-d H:i:s'); // Store as full datetime string
                }
            }
            $today->add($interval);
        }
        $this->context->smarty->assign([
            'dates' => $dates,
        ]);

        $this->setTemplate('module:appointmentmanager/views/templates/front/appointment_form.html.twig');
    }
}
