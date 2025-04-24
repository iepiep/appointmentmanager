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

        // Note: This $tabNames variable seems unused later in the constructor.
        // Consider removing if it's truly not needed.
        $tabNames = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tabNames[$lang['locale']] = $this->trans('Link List', [], 'Modules.Linklist.Admin', $lang['locale']);
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
                'wording_domain' => 'Modules.Appointmentmanager.Admin',
            ],
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
            $trimmedQuery = trim($query);
            if (!empty($trimmedQuery)) {
                try {
                    if (!Db::getInstance()->execute($trimmedQuery)) {
                        PrestaShopLogger::addLog('SQL Install Error: Failed executing query.', 3);
                        return false;
                    }
                } catch (Exception $e) {
                    PrestaShopLogger::addLog('SQL Install Exception: ' . $e->getMessage(), 3);
                    return false;
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

    /**
     * Displays the appointment invitation block.
     * Generates a link to the legacy front controller.
     */
    public function hookDisplayHome($params): string // Added return type hint
    {
        // Generate the link, default to '#' if generation fails or returns index page link
        $link = $this->context->link;
        $generatedUrl = $link->getModuleLink($this->name, 'appointment', [], true);
        $appointmentLink = ($generatedUrl && $generatedUrl !== $link->getPageLink('index')) ? $generatedUrl : '#';

        // Assign link to Smarty
        $this->context->smarty->assign('appointment_link', $appointmentLink);

        // Directly display the template
        return (string) $this->display(__FILE__, 'views/templates/hook/appointment_invite.tpl');
    }

    public function getContent()
    {
        // Use service retrieval recommended in PS 8+
        $router = $this->get('router');
        if (null === $router) {
            // Handle error: router service not found (log, throw exception, or fallback)
            // Corrected indentation below
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminDashboard')); // Fallback example
            return;
        }
        $route = $router->generate('appointment_manager_config');
        Tools::redirectAdmin($route);
        // exit; // Consider uncommenting if Tools::redirectAdmin doesn't exit reliably
    }
}
