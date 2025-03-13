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

use PrestaShop\PrestaShop\Adapter\SymfonyContainer;

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

        if (!Configuration::get('APPOINTMENTMANAGER_NAME')) {
            $this->warning = $this->trans('No name provided', [], 'Modules.Appointmentmanager.Admin');
        }

        $mainTabNames = [];
        $subTabNames = [];
        foreach (Language::getLanguages(true) as $lang) {
            $mainTabNames[$lang['locale']] = $this->trans('Appointment Manager', array(), 'Modules.Appointmentmanager.Admin', $lang['locale']);
            $subTabConfigNames[$lang['locale']] = $this->trans('Configuration', array(), 'Modules.Appointmentmanager.Admin', $lang['locale']);
        }
        $this->tabs = [
            [
                'route_name' => 'appointment_manager_appointment_list',
                'class_name' => 'AppointmentManager',
                'visible' => true,
                'name' => $mainTabNames,
                'parent_class_name' => 'CONFIGURE',
                'wording' => 'Appointment Manager',
                'wording_domain' => 'Modules.AppointmentManager.Admin'
            ],
            [
                'route_name' => 'appointment_manager_config',
                'class_name' => 'AppointmentManagerConfigurationController',
                'visible' => true,
                'name' => $subTabConfigNames,
                'parent_class_name' => 'AppointmentManager',
                'wording' => 'Configuration',
                'wording_domain' => 'Modules.AppointmentManager.Admin'
            ],
            
        ];
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        return parent::install()
            && $this->registerHook('displayLeftColumn')
            && $this->registerHook('actionFrontControllerSetMedia')
            && $this->registerHook('displayRightColumn')
            && Configuration::updateValue('APPOINTMENTMANAGER_NAME', 'Appointment Manager')
            && $this->installSql();
    }

    public function uninstall()
    {
        return parent::uninstall()
            && Configuration::deleteByName('APPOINTMENTMANAGER_NAME')
            && Configuration::deleteByName('APPOINTMENTMANAGER_GOOGLE_API_KEY')
            && Configuration::deleteByName('APPOINTMENTMANAGER_APPOINTMENT_LENGTH')
            && Configuration::deleteByName('APPOINTMENTMANAGER_LUNCH_BREAK_LENGTH')
            && $this->uninstallSql();
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

    public function hookDisplayLeftColumn($params)
    {
        $this->context->smarty->assign([
            'module_name' => Configuration::get('APPOINTMENTMANAGER_NAME'),
            'module_link' => $this->context->link->getModuleLink('appointmentmanager', 'display'),
            'module_message' => $this->l('This is a simple text message')
        ]);

        return $this->display(__FILE__, 'appointmentmanager.tpl');
    }

    public function hookDisplayRightColumn($params)
    {
        return $this->hookDisplayLeftColumn($params);
    }

    public function hookActionFrontControllerSetMedia()
    {
        $this->context->controller->registerStylesheet(
            'appointmentmanager-style',
            'modules/' . $this->name . '/views/css/appointmentmanager.css',
            [
                'media' => 'all',
                'priority' => 1000,
            ]
        );
    }

    public function isUsingNewTranslationSystem()
    {
        return true;
    }

    public function getContent()
    {
        $route = $this->get('router')->generate('appointment_manager_config');
        Tools::redirectAdmin($route);
    }
}
