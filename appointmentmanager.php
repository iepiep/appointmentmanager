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
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        return parent::install()
            && $this->installTabs()
            && $this->installSql()
            && $this->registerHook('displayLeftColumn')
            && $this->registerHook('actionFrontControllerSetMedia')
            && $this->registerHook('displayRightColumn')
            && Configuration::updateValue('APPOINTMENTMANAGER_NAME', 'Appointment Manager');
    }

    public function uninstall()
    {
        return parent::uninstall()
            && $this->uninstallTabs()
            && $this->uninstallSql()
            && Configuration::deleteByName('APPOINTMENTMANAGER_NAME')
            && Configuration::deleteByName('APPOINTMENTMANAGER_GOOGLE_API_KEY')
            && Configuration::deleteByName('APPOINTMENTMANAGER_APPOINTMENT_LENGTH')
            && Configuration::deleteByName('APPOINTMENTMANAGER_LUNCH_BREAK_LENGTH');
    }

    public function installTabs(): bool
    {
        $mainTabNames = [];
        $subTabConfigNames = [];
        foreach (Language::getLanguages(true) as $lang) {
            $mainTabNames[$lang['id_lang']] = $this->trans('Appointment Manager', [], 'Modules.Appointmentmanager.Admin', $lang['locale']);
            $subTabConfigNames[$lang['id_lang']] = $this->trans('Configuration', [], 'Modules.Appointmentmanager.Admin', $lang['locale']);
        }

        // Install Main Tab
        $mainTabId = (int) Tab::getIdFromClassName('AppointmentManagerMainTab');
        if (!$mainTabId) {
            $mainTabId = null;
        }

        $mainTab = new Tab($mainTabId);
        $mainTab->active = 1;
        $mainTab->class_name = 'AppointmentManagerMainTab';
        $mainTab->route_name = 'appointment_manager_appointment_list';
        $mainTab->name = $mainTabNames;
        $mainTab->icon = 'directions';
        $mainTab->id_parent = (int) Tab::getIdFromClassName('CONFIGURE');
        $mainTab->module = $this->name;

        if (!$mainTab->save()) {
            return false;
        }

        // Install Sub Tab (Configuration)
        $subTabId = (int) Tab::getIdFromClassName('AppointmentManagerConfigSubTab');
        if (!$subTabId) {
            $subTabId = null;
        }

        $subTab = new Tab($subTabId);
        $subTab->active = 1;
        $subTab->class_name = 'AppointmentManagerConfigSubTab';
        $subTab->route_name = 'appointment_manager_config';
        $subTab->name = $subTabConfigNames;
        $subTab->id_parent = $mainTab->id;
        $subTab->module = $this->name;

        if (!$subTab->save()) {
            return false;
        }

        return true;
    }

    public function uninstallTabs(): bool
    {
        $mainTabId = (int) Tab::getIdFromClassName('AppointmentManagerMainTab');
        if ($mainTabId) {
            $mainTab = new Tab($mainTabId);
            if (!$mainTab->delete()) {
                return false;
            }
        }

        $subTabId = (int) Tab::getIdFromClassName('AppointmentManagerConfigSubTab');
        if ($subTabId) {
            $subTab = new Tab($subTabId);
            if (!$subTab->delete()) {
                return false;
            }
        }

        return true;
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
