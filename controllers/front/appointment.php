<?php
// FILE: modules/appointmentmanager/controllers/front/appointment.php

if (!defined('_PS_VERSION_')) {
    exit;
}

class AppointmentManagerAppointmentModuleFrontController extends ModuleFrontController
{
    // Optional properties for controlling access, SSL etc.
    // public $auth = false; // Set to true if login is required
    public $ssl = true;   // Recommended for forms
    // public $php_self = 'module-appointmentmanager-appointment'; // Optional: For canonical URLs

    public function setMedia()
    {
        // Always call parent::setMedia() first to include default assets
        parent::setMedia();

        // Register our custom JavaScript file
        // Path is relative to the module's root directory
        $this->registerJavascript(
            'module-appointmentmanager-form-js', // Unique ID for this script
            'modules/' . $this->module->name . '/views/js/appointment-form.js', // Path to the JS file
            ['priority' => 200, 'position' => 'bottom', 'server' => 'local'] // Options: low priority, load at bottom
        );
       
        $this->registerStylesheet(
            'module-appointmentmanager-form-css',
            'modules/'.$this->module->name.'/views/css/appointment-form.css',
             ['media' => 'all', 'priority' => 150, 'server' => 'local']
         );
    }

    public function initContent()
    {
        parent::initContent(); // Initialize context, CSS, JS

        // Process form submission (Manual Handling)
        if (Tools::isSubmit('submitAppointment')) {
            $this->processFormSubmission();
        }

        // Prepare data for the template
        $this->assignTemplateVariables();

        // Set the template file (expects .tpl)
        $this->setTemplate('module:appointmentmanager/views/templates/front/appointment_form.tpl');
    }

    /**
     * Handles the form submission manually.
     */
    protected function processFormSubmission()
    {
        $errors = [];
        $submittedData = []; // Store submitted values for redisplay

        // Retrieve values using Tools::getValue()
        $submittedData['lastname'] = Tools::getValue('lastname');
        $submittedData['firstname'] = Tools::getValue('firstname');
        $submittedData['address'] = Tools::getValue('address');
        $submittedData['postal_code'] = Tools::getValue('postal_code');
        $submittedData['city'] = Tools::getValue('city');
        $submittedData['phone'] = Tools::getValue('phone');
        $submittedData['email'] = Tools::getValue('email');
        $submittedData['rdv_option_1'] = Tools::getValue('rdv_option_1');
        $submittedData['rdv_option_2'] = Tools::getValue('rdv_option_2');
        $gdprAccepted = Tools::isSubmit('GDPR'); // Checkbox

        // Basic Validation (Add more as needed)
        if (empty($submittedData['lastname'])) {
            $errors[] = $this->module->l('Last name is required.', 'appointment'); // Use module translation
        }
        if (empty($submittedData['firstname'])) {
            $errors[] = $this->module->l('First name is required.', 'appointment');
        }
        if (empty($submittedData['address'])) {
            $errors[] = $this->module->l('Address is required.', 'appointment');
        }
        if (empty($submittedData['postal_code'])) {
            $errors[] = $this->module->l('Postal code is required.', 'appointment');
        }
         if (empty($submittedData['city'])) {
            $errors[] = $this->module->l('City is required.', 'appointment');
        }
        if (empty($submittedData['phone']) && empty($submittedData['email'])) {
             $errors[] = $this->module->l('You must provide at least a phone number or an email address.', 'appointment');
        } elseif (!empty($submittedData['email']) && !Validate::isEmail($submittedData['email'])) {
             $errors[] = $this->module->l('The email address is invalid.', 'appointment');
        }
        if (empty($submittedData['rdv_option_1'])) {
            $errors[] = $this->module->l('Preferred time slot is required.', 'appointment');
        }
         if (empty($submittedData['rdv_option_2'])) {
            $errors[] = $this->module->l('Alternative time slot is required.', 'appointment');
        }
        if (!$gdprAccepted) {
             $errors[] = $this->module->l('You must accept the privacy policy.', 'appointment');
        }

        // If no errors, try to save
        if (empty($errors)) {
            try {
                $success = Db::getInstance()->insert('appointment_manager', [
                    'lastname' => pSQL($submittedData['lastname']),
                    'firstname' => pSQL($submittedData['firstname']),
                    'address' => pSQL($submittedData['address']),
                    'postal_code' => pSQL($submittedData['postal_code']),
                    'city' => pSQL($submittedData['city']),
                    'phone' => pSQL($submittedData['phone'] ?? ''),
                    'email' => pSQL($submittedData['email'] ?? ''),
                    'rdv_option_1' => pSQL($submittedData['rdv_option_1']),
                    'rdv_option_2' => pSQL($submittedData['rdv_option_2']),
                    'GDPR' => date('Y-m-d H:i:s'), // Use current date/time
                    // Defaults for visited, ishome, istest handled by DB schema
                ]);

                if ($success) {
                    // Redirect to a success page or homepage with a success message
                    // Using Tools::redirect() with parameters for flash messages is complex here.
                    // Easiest is often redirecting to homepage.
                    Tools::redirect($this->context->link->getPageLink('index', true) . '?appointment_success=1');
                    exit;
                } else {
                    $errors[] = $this->module->l('An error occurred while saving your request. Please try again.', 'appointment');
                }

            } catch (PrestaShopDatabaseException $e) {
                PrestaShopLogger::addLog('AppointmentManager DB Error (Legacy): ' . $e->getMessage(), 3);
                $errors[] = $this->module->l('A database error occurred. Please try again later.', 'appointment');
            } catch (Exception $e) {
                 PrestaShopLogger::addLog('AppointmentManager Generic Error (Legacy): ' . $e->getMessage(), 3);
                 $errors[] = $this->module->l('An unexpected error occurred.', 'appointment');
            }
        }

        // If there are errors OR saving failed, re-assign errors and submitted data
        $this->context->smarty->assign([
            'appointment_errors' => $errors,
            'submitted_data' => $submittedData, // Pass back submitted data to refill form
        ]);
    }

    /**
     * Assigns variables needed for the template.
     */
    protected function assignTemplateVariables()
    {
        // Generate date choices for the dropdowns
        $dates = [];
        $today = new \DateTime();
        $today->setTime(0, 0);
        for ($i = 0; $i < 15; $i++) {
            $date = clone $today;
            $date->modify("+$i days");
            if ($date->format('N') >= 6) { // Skip Sat/Sun example
                // continue;
            }
            $formattedDate = $date->format('d/m/Y');
            // Key and Value are the same for simple display
            $dates[$formattedDate . ' Matin'] = $formattedDate . ' Matin';
            $dates[$formattedDate . ' Après-midi'] = $formattedDate . ' Après-midi';
        }

        // Get privacy policy link (adjust CMS ID if needed)
        $privacyLink = $this->context->link->getCMSLink(3, null, true, $this->context->language->id);

        $this->context->smarty->assign([
            'action_url' => $this->context->link->getModuleLink('appointmentmanager', 'appointment', [], true), // Form action URL
            'available_dates' => $dates,
            'privacy_policy_url' => $privacyLink,
            'appointment_success' => Tools::getValue('appointment_success') == 1, // Check for success flag in URL
            // 'appointment_errors' and 'submitted_data' are assigned in processFormSubmission if needed
        ]);
    }
}