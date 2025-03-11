<?php

/**
 * @author Roberto Minini <r.minini@solution61.fr>
 * @copyright 2025 Roberto Minini
 * @license MIT
 *
 * This file is part of the AppointmentManager Module
 * License: MIT License
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

use PrestaShop\PrestaShop\Adapter\SymfonyContainer;

class AppointmentManager extends Module
{
    public function __construct()
    {
        $this->name = 'appointmentmanager';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'Roberto Minini';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('Appointment Manager');
        $this->description = $this->l('Module to manage appointments.');
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => '8.99.99'];
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }
    public function install()
    {
        if (!parent::install() ||
            $this->registerServices() ||
            !$this->installDB() ||
            !$this->installTabs() ||
            !$this->registerHook('displayHome') ||
            !$this->registerHook('displayBanner')
        ) {
            return false;
        }
        Configuration::updateValue('APPOINTMENTMANAGER_GOOGLE_API_KEY', '');
        Configuration::updateValue('APPOINTMENTMANAGER_START_TIME', '08:30');
        Configuration::updateValue('APPOINTMENTMANAGER_APPOINTMENT_LENGTH', 120);
        Configuration::updateValue('APPOINTMENTMANAGER_BREAK_LENGTH', 60);
        return true;
    }
    public function uninstall()
    {
        if (!parent::uninstall() ||
            !$this->uninstallDB() ||
            !$this->uninstallTabs()
        ) {
            return false;
        }
        Configuration::deleteByName('APPOINTMENTMANAGER_GOOGLE_API_KEY');
        Configuration::deleteByName('APPOINTMENTMANAGER_START_TIME');
        Configuration::deleteByName('APPOINTMENTMANAGER_APPOINTMENT_LENGTH');
        Configuration::deleteByName('APPOINTMENTMANAGER_BREAK_LENGTH');
        return true;
    }
    private function registerServices()
    {
        $container = new ContainerBuilder();
        $loader = new \Symfony\Component\DependencyInjection\Loader\YamlFileLoader($container, new \Symfony\Component\Config\FileLocator(__DIR__ . '/config'));
        $loader->load('services.yml');

        $container->compile();
    }
    protected function installDB()
    {
        $sql_file = dirname(__FILE__).'/sql/install.sql';
        if (!file_exists($sql_file)) {
            return false;
        }

        $sql_content = Tools::file_get_contents($sql_file);
        $sql_content = str_replace('Prefix_', _DB_PREFIX_, $sql_content);
        $sql_queries = array_filter(array_map('trim', explode(';', $sql_content)));

        Db::getInstance()->execute('START TRANSACTION'); // Begin transaction

        try {
            foreach ($sql_queries as $query) {
                if (!empty($query)) {
                    if (!Db::getInstance()->execute($query)) {
                        throw new PrestaShopDatabaseException('Error executing query: ' . $query); // More informative error
                    }
                }
            }
            Db::getInstance()->execute('COMMIT'); // Commit transaction
            return true;

        } catch (PrestaShopDatabaseException $e) {
            Db::getInstance()->execute('ROLLBACK'); // Rollback in case of error
            $this->context->controller->errors[] = $e->getMessage(); // Display error to user
            PrestaShopLogger::addLog($e->getMessage(), 3, null, 'AppointmentManager', $this->id, true); // Log detailed error
            return false;
        }
    }
    protected function uninstallDB()
    {
        $sql = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'appointment_manager`';
        return Db::getInstance()->execute($sql);
    }
    protected function installTabs()
    {
        // Onglet principal
        $mainTab = new Tab();
        $mainTab->active = 1;
        $mainTab->class_name = 'AdminAppointmentManager';
        $mainTab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $mainTab->name[$lang['id_lang']] = $this->trans('Appointment Manager', [], 'Modules.Appointmentmanager.Admin'); // Use translations
        }
        $mainTab->id_parent = -1; // No parent, directly in the menu.  Or 0 if you *do* want it under a main menu item
        $mainTab->module = $this->name;
        $mainTab->icon = 'local_shipping';
        //$mainTab->route_name = ''; // If you *do* want a route for the main tab, define it here and in route.yml
        if (!$mainTab->add()) {
            return false;
        }

        // Onglet Config
        $configTab = new Tab();
        $configTab->active = 1;
        $configTab->class_name = 'AdminAppointmentManagerConfig';
        $configTab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $configTab->name[$lang['id_lang']] = $this->trans('Config', [], 'Modules.Appointmentmanager.Admin');
        }
        $configTab->id_parent = $mainTab->id; // Correct parent ID
        $configTab->module = $this->name;
        $configTab->icon = 'settings';
        $configTab->route_name = 'admin_appointmentmanager_config';  // Correct route name
        if (!$configTab->add()) {
            return false;
        }

        // Onglet CustomerList
        $customerListTab = new Tab();
        $customerListTab->active = 1;
        $customerListTab->class_name = 'AdminAppointmentManagerCustomerList';
        $customerListTab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $customerListTab->name[$lang['id_lang']] = $this->trans('Customer List', [], 'Modules.Appointmentmanager.Admin');
        }
        $customerListTab->id_parent = $mainTab->id;
        $customerListTab->module = $this->name;
        $customerListTab->icon = 'description';
        $customerListTab->route_name = 'admin_appointmentmanager_customerlist'; // Correct route name
        if (!$customerListTab->add()) {
            return false;
        }
        // Onglet ItineraryMap
        $itineraryMapTab = new Tab();
        $itineraryMapTab->active = 1;
        $itineraryMapTab->class_name = 'AdminAppointmentManagerItineraryMap';
        $itineraryMapTab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $itineraryMapTab->name[$lang['id_lang']] = $this->trans('Itinerary Map', [], 'Modules.Appointmentmanager.Admin');
        }
        $itineraryMapTab->id_parent = $mainTab->id;
        $itineraryMapTab->module = $this->name;
        $itineraryMapTab->icon = 'map';  // Choose an appropriate icon
        $itineraryMapTab->route_name = 'admin_appointmentmanager_itinerarymap'; // Define the route
        if (!$itineraryMapTab->add()) {
            return false;
        }
        return true;
    }
    protected function uninstallTabs()
    {
        $tabs = array('AdminAppointmentManager', 'AdminAppointmentManagerConfig', 'AdminAppointmentManagerCustomerList');
        foreach ($tabs as $class_name) {
            $id_tab = (int)Tab::getIdFromClassName($class_name);
            if ($id_tab) {
                $tab = new Tab($id_tab);
                if (!$tab->delete()) {
                    return false;
                }
            }
        }
        return true;
    }
    public function resetModule()
    {
        return Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'appointment_manager` WHERE istest=1');
    }
    public function getContent()
    {

        if (Tools::isSubmit('submitAppointmentManagerReset')) {
            if (Tools::getValue('confirm_reset') == '1') {
                if ($this->resetModule()) {
                    $this->context->controller->confirmations[] = $this->trans('Test data removed successfully.', [], 'Modules.Appointmentmanager.Admin');
                } else {
                    $this->context->controller->errors[] = $this->trans('An error occurred while removing test data.', [], 'Modules.Appointmentmanager.Admin');
                }
            } else {
                $this->context->controller->warnings[] = $this->trans('Reset cancelled.', [], 'Modules.Appointmentmanager.Admin'); // Use warnings for non-critical messages
            }
        }
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminAppointmentManagerConfig'));
    }
    private function _displayAppointmentBlock($params)
    {
        $this->context->smarty->assign(array(
          'appointment_message' => $this->trans('Prenez rendez-vous pour votre diagnostic immobilier', [], 'Modules.Appointmentmanager.Front'),
          'appointment_link' => $this->context->link->getModuleLink($this->name, 'appointmentmoduleform')
      ));
        return $this->fetch('module:'.$this->name.'/views/templates/front/appointment_block.tpl');
    }
    public function hookDisplayHome($params)
    {
        return $this->_displayAppointmentBlock($params);
    }

    public function hookDisplayBanner($params)
    {
        return $this->_displayAppointmentBlock($params);
    }
}
