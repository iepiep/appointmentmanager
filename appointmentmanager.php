<?php
/**
* @author Roberto Minini <r.minini@solution61.fr>
* @copyright 2025 Roberto Minini
* @license MIT
*
* This file is part of the AppointmentManager Module
* License: MIT License
*/

declare(strict_types=1);

use PrestaShop\PrestaShop\Adapter\ServiceLocator;

if (!defined('_PS_VERSION_')) {
    exit;
}

class AppointmentManager extends Module
{
    public function __construct()
    {
        $this->name = 'appointmentmanager';
        $this->tab = 'shipping_logistics';
        $this->version = '0.9.0';
        $this->author = 'Roberto Minini';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Appointment Manager', [], 'Modules.Appointmentmanager.Admin');
        $this->description = $this->trans(
            'Manage appointment and create best itinerary',
            [],
            'Modules.Appointmentmanager.Admin'
        );

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Appointmentmanager.Admin');

        if (!Configuration::get('APPOINTMENTMANAGER_GOOGLE_API_KEY')) {
            $this->warning = $this->trans('No API key provided', [], 'Modules.Appointmentmanager.Admin');
        }

        $tabNames = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tabNames[$lang['locale']] = $this->trans('Link List', array(), 'Modules.Linklist.Admin', $lang['locale']);
        }

        $listTabNames = [];
        foreach (Language::getLanguages(true) as $lang) {
            $listTabNames[$lang['id_lang']] = $this->trans('Appointment Manager', [], 'Modules.Appointmentmanager.Admin', $lang['locale']);
        }
        $this->tabs = [
            [
                'route_name' => 'appointment_manager_appointment_list',
                'class_name' => 'AppointmentManagerMainTab',
                'visible' => true,
                'name' => $listTabNames,
                'icon' => 'directions_car',
                'parent_class_name' => 'IMPROVE',
                'wording' => 'List',
                'wording_domain' => 'Modules.Appointmentmanager.Admin'
            ]
        ];
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        return parent::install()
            && $this->installSql()
            && $this->registerHook('displayHome');
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallSql()
            && Configuration::deleteByName('APPOINTMENTMANAGER_GOOGLE_API_KEY')
            && Configuration::deleteByName('APPOINTMENTMANAGER_APPOINTMENT_LENGTH')
            && Configuration::deleteByName('APPOINTMENTMANAGER_LUNCH_BREAK_LENGTH');
    }

    private function installSql(): bool
    {

        $sql_file = _PS_MODULE_DIR_ . $this->name . '/sql/install.sql';

        if (!file_exists($sql_file)) {
            return false;
        }

        $sql_content = file_get_contents($sql_file);
        $sql_content = str_replace('PREFIX_', _DB_PREFIX_, $sql_content);
        $queries = preg_split("/;\s*[\r\n]+/", $sql_content);

        foreach ($queries as $query) {
            if (!empty(trim($query))) {
                try {
                    if (!Db::getInstance()->execute($query)) {
                        return false;
                    }
                } catch (Exception $e) {
                    PrestaShopLogger::addLog('SQL Error: ' . $e->getMessage(), 3);
                }
            }
        }

        return true;
    }

    private function uninstallSql(): bool
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'appointment_manager`';

        try {
            return Db::getInstance()->execute($sql);
        } catch (Exception $e) {
            PrestaShopLogger::addLog('SQL Uninstall Error: ' . $e->getMessage(), 3);

            return false;
        }
    }


// FILE: modules/appointmentmanager/appointmentmanager.php

// ... (use statements, class definition, __construct, install, uninstall, SQL methods etc.) ...

/**
 * Displays the appointment invitation block.
 * Generates a link to the legacy front controller.
 *
 * @param array $params Hook parameters
 * @return string|false Hook HTML output or false on error
 */
public function hookDisplayHome($params)
{
    // Log the start of the hook execution
    PrestaShopLogger::addLog('AppointmentManager: hookDisplayHome started.', 1);

    // Set a default URL in case link generation fails
    $appointmentFormUrl = '#';

    try {
        // Define the name of the legacy controller file (without .php)
        // This corresponds to 'modules/appointmentmanager/controllers/front/appointment.php'
        $legacyControllerName = 'appointment';

        PrestaShopLogger::addLog('AppointmentManager: Attempting legacy link generation for controller: ' . $legacyControllerName, 1);

        // Get the PrestaShop Link object from the context
        $link = $this->context->link;

        // Check if the Link object is available
        if ($link) {
            // Generate the URL using the legacy getModuleLink method
            $generatedUrl = $link->getModuleLink(
                $this->name,           // Module name ('appointmentmanager')
                $legacyControllerName, // Legacy controller name ('appointment')
                [],                    // Parameters (none needed for this link)
                true                   // Enable SSL if active
            );

            // Validate the generated URL (ensure it's not false, empty, or just the homepage link)
            if ($generatedUrl && $generatedUrl !== $link->getPageLink('index')) {
                $appointmentFormUrl = $generatedUrl;
                PrestaShopLogger::addLog('AppointmentManager: Legacy link generation successful. URL: ' . $appointmentFormUrl, 1);
            } else {
                PrestaShopLogger::addLog('AppointmentManager: Legacy link generation failed or returned index URL. Controller name used: ' . $legacyControllerName, 3);
                $appointmentFormUrl = '#error-legacy-link-failed'; // URL indicating an error
            }
        } else {
             // Log an error if the context Link object wasn't found
             PrestaShopLogger::addLog('AppointmentManager: Context link object is not available in hookDisplayHome.', 3);
             $appointmentFormUrl = '#error-context-link-missing'; // URL indicating an error
        }

    } catch (\Exception $e) {
        // Log any unexpected exceptions during link generation
        PrestaShopLogger::addLog('AppointmentManager: Exception during legacy link generation in hookDisplayHome. Message: ' . $e->getMessage(), 3);
        $appointmentFormUrl = '#error-legacy-link-exception'; // URL indicating an error
    }

    // Assign the generated URL (or error indicator) to the Smarty variable 'appointment_link'
    // This makes it available inside the .tpl file as {$appointment_link}
    PrestaShopLogger::addLog('AppointmentManager: Assigning URL to Smarty: ' . $appointmentFormUrl, 1);
    $this->context->smarty->assign([
        'appointment_link' => $appointmentFormUrl
    ]);

    // Define the path to the Smarty template file for this hook
    $templateFile = 'views/templates/hook/appointment_invite.tpl';
    $output = ''; // Initialize the output string

    try {
        PrestaShopLogger::addLog('AppointmentManager: Attempting to display template: ' . $templateFile, 1);
        // Render the Smarty template using the assigned variables
        $output = $this->display(__FILE__, $templateFile);
        PrestaShopLogger::addLog('AppointmentManager: Template display successful for ' . $templateFile, 1);
    } catch (\Exception $e) {
         // Log any errors during the template rendering process
         PrestaShopLogger::addLog('AppointmentManager: Exception during template display in hookDisplayHome. Template: ' . $templateFile . '. Message: ' . $e->getMessage(), 3);
    }

    // Log the end of the hook execution
    PrestaShopLogger::addLog('AppointmentManager: hookDisplayHome finished.', 1);

    // Return the generated HTML (or an empty string/error message if rendering failed)
    return $output;
}

// ... (getContent method and rest of the AppointmentManager class) ...

    public function getContent()
    {
        $route = $this->get('router')->generate('appointment_manager_config');
        Tools::redirectAdmin($route);
    }
}