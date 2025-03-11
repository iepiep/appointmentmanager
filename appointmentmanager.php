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
        $this->displayName = $this->trans('Appointment Manager', [], 'Modules.Appointmentmanager.Admin');
        $this->description = $this->trans('Module to manage appointments.', [], 'Modules.Appointmentmanager.Admin');
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_ ];
        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Appointmentmanager.Admin');
    }
    public function install()
    {
        PrestaShopLogger::addLog('AppointmentManager: Starting install process...', 1, null, 'AppointmentManager'); // <--- LOG 1
    
        if (!parent::install()) { // <--- SUSPECT POINT: parent::install()
            PrestaShopLogger::addLog('AppointmentManager: parent::install() failed IMMEDIATELY.', 3, null, 'AppointmentManager'); // <--- LOG 2
            return false;
        }
        PrestaShopLogger::addLog('AppointmentManager: parent::install() successful.', 1, null, 'AppointmentManager'); // <--- LOG 3

        if (!$this->installDB()) {
            PrestaShopLogger::addLog('AppointmentManager: installDB() failed.', 3, null, 'AppointmentManager');
            return false;
        }
        PrestaShopLogger::addLog('AppointmentManager: installDB() successful.', 1, null, 'AppointmentManager');

        if (!$this->installTabs()) {
            PrestaShopLogger::addLog('AppointmentManager: installTabs() failed.', 3, null, 'AppointmentManager');
            return false;
        }
        PrestaShopLogger::addLog('AppointmentManager: installTabs() successful.', 1, null, 'AppointmentManager');


        if (!$this->registerHook('displayHome')) {
            PrestaShopLogger::addLog('AppointmentManager: registerHook(displayHome) failed.', 3, null, 'AppointmentManager');
            return false;
        }
        PrestaShopLogger::addLog('AppointmentManager: registerHook(displayHome) successful.', 1, null, 'AppointmentManager');


        if (!$this->registerHook('displayBanner')) {
            PrestaShopLogger::addLog('AppointmentManager: registerHook(displayBanner) failed.', 3, null, 'AppointmentManager');
            return false;
        }
        PrestaShopLogger::addLog('AppointmentManager: registerHook(displayBanner) successful.', 1, null, 'AppointmentManager');


        Configuration::updateValue('APPOINTMENTMANAGER_GOOGLE_API_KEY', '');
        Configuration::updateValue('APPOINTMENTMANAGER_START_TIME', '08:30');
        Configuration::updateValue('APPOINTMENTMANAGER_APPOINTMENT_LENGTH', 120);
        Configuration::updateValue('APPOINTMENTMANAGER_BREAK_LENGTH', 60);
        Configuration::updateValue('APPOINTMENTMANAGER_HOME_ADDRESS', '');
        Configuration::updateValue('APPOINTMENTMANAGER_HOME_POSTAL_CODE', '');
        Configuration::updateValue('APPOINTMENTMANAGER_HOME_CITY', '');

        PrestaShopLogger::addLog('AppointmentManager: Installation process completed.', 1, null, 'AppointmentManager');
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
        Configuration::deleteByName('APPOINTMENTMANAGER_HOME_ADDRESS');
        Configuration::deleteByName('APPOINTMENTMANAGER_HOME_POSTAL_CODE');
        Configuration::deleteByName('APPOINTMENTMANAGER_HOME_CITY');
        return true;
    }
    protected function installDB()
    {
        $sql_file = dirname(__FILE__).'/src/sql/install.sql';
        if (!file_exists($sql_file)) {
            $error_message = 'SQL installation file not found: ' . $sql_file;
            PrestaShopLogger::addLog('AppointmentManager: installDB() - ' . $error_message, 3, null, 'AppointmentManager');
            $this->context->controller->errors[] = $this->trans($error_message, [], 'Modules.Appointmentmanager.Admin');
            return false;
        }

        $sql_content = Tools::file_get_contents($sql_file);
        $sql_content = str_replace('Prefix_', _DB_PREFIX_, $sql_content);
        $sql_queries = array_filter(array_map('trim', explode(';', $sql_content)));

        Db::getInstance()->execute('START TRANSACTION');

        try {
            foreach ($sql_queries as $query) {
                if (trim($query) != '') {
                    if (!Db::getInstance()->execute($query)) {
                        $error_message = 'Error executing query: ' . $query . ' - Error Message: ' . Db::getInstance()->getMsgError();
                        PrestaShopLogger::addLog('AppointmentManager: installDB() - ' . $error_message, 3, null, 'AppointmentManager');
                        throw new PrestaShopDatabaseException($error_message);
                    }
                }
            }
            Db::getInstance()->execute('COMMIT');
            return true;

        } catch (PrestaShopDatabaseException $e) {
            Db::getInstance()->execute('ROLLBACK');
            PrestaShopLogger::addLog('AppointmentManager: installDB() - Database error: ' . $e->getMessage(), 3, null, 'AppointmentManager');
            $this->context->controller->errors[] = $e->getMessage();
            return false;
        }
    }
    protected function uninstallDB()
    {
        $sql = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'appointment_manager`';
        $result = Db::getInstance()->execute($sql);
        if (!$result) {
            PrestaShopLogger::addLog('AppointmentManager: uninstallDB() - Error dropping table appointment_manager: ' . Db::getInstance()->getMsgError(), 3);
        }
        return $result;
    }
    protected function installTabs()
    {
        // Main Tab
        $mainTab = new Tab();
        $mainTab->active = 1;
        $mainTab->class_name = 'AdminAppointmentManager';
        $mainTab->module = $this->name;
        $mainTab->id_parent = Tab::getIdFromClassName('AdminDashboard');
        $mainTab->icon = 'local_shipping';
        $mainTab->name = [];
        foreach (Language::getLanguages(true) as $lang) {
            $mainTab->name[$lang['id_lang']] = $this->trans('Appointment Manager', [], 'Modules.Appointmentmanager.Admin');
        }
        if (!$mainTab->add()) {
            $error_message_main_tab = 'Error adding Main Tab: ' . implode(', ', $mainTab->getErrors());
            PrestaShopLogger::addLog('AppointmentManager: installTabs() - ' . $error_message_main_tab, 3, null, 'AppointmentManager');
            return false;
        }
        PrestaShopLogger::addLog('AppointmentManager: installTabs() - Main Tab added successfully.', 1, null, 'AppointmentManager');


        // Config Tab
        $configTab = new Tab();
        $configTab->active = 1;
        $configTab->class_name = 'AdminAppointmentManagerConfig';
        $configTab->module = $this->name;
        $configTab->id_parent = (int)$mainTab->id; // Cast to int for safety
        $configTab->route_name = 'admin_appointmentmanager_config';
        $configTab->icon = 'settings';
        $configTab->name = [];
        foreach (Language::getLanguages(true) as $lang) {
            $configTab->name[$lang['id_lang']] = $this->trans('Config', [], 'Modules.Appointmentmanager.Admin');
        }
        if (!$configTab->add()) {
            $error_message_config_tab = 'Error adding Config Tab: ' . implode(', ', $configTab->getErrors());
            PrestaShopLogger::addLog('AppointmentManager: installTabs() - ' . $error_message_config_tab, 3, null, 'AppointmentManager');
            return false;
        }
        PrestaShopLogger::addLog('AppointmentManager: installTabs() - Config Tab added successfully.', 1, null, 'AppointmentManager');


        // Customer List Tab
        $customerListTab = new Tab();
        $customerListTab->active = 1;
        $customerListTab->class_name = 'AdminAppointmentManagerCustomerList';
        $customerListTab->module = $this->name;
        $customerListTab->id_parent = (int)$mainTab->id;
        $customerListTab->route_name = 'admin_appointmentmanager_customer_list';
        $customerListTab->icon = 'description';
        $customerListTab->name = [];
        foreach (Language::getLanguages(true) as $lang) {
            $customerListTab->name[$lang['id_lang']] = $this->trans('Customer List', [], 'Modules.Appointmentmanager.Admin');
        }
        if (!$customerListTab->add()) {
            $error_message_customer_list_tab = 'Error adding Customer List Tab: ' . implode(', ', $customerListTab->getErrors());
            PrestaShopLogger::addLog('AppointmentManager: installTabs() - ' . $error_message_customer_list_tab, 3, null, 'AppointmentManager');
            return false;
        }
        PrestaShopLogger::addLog('AppointmentManager: installTabs() - Customer List Tab added successfully.', 1, null, 'AppointmentManager');


        return true;
    }
    protected function uninstallTabs()
    {
        $tabs = array('AdminAppointmentManager', 'AdminAppointmentManagerConfig', 'AdminAppointmentManagerCustomerList', 'AdminAppointmentManagerItineraryMap');
        foreach ($tabs as $className) {
            $id_tab = (int)Tab::getIdFromClassName($className); // Cast to int
            if ($id_tab) {
                $tab = new Tab($id_tab);
                if (!Validate::isLoadedObject($tab) || !$tab->delete()) { // Validate loaded object before delete
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
            if (!$this->isTokenValid()) {  // Add token check
                $this->context->controller->errors[] = $this->trans('Invalid security token', [], 'Modules.Appointmentmanager.Admin');
                return '';
            }

            // ...existing code...
            if (Tools::getValue('confirm_reset') == '1') {
                if ($this->resetModule()) {
                    $this->context->controller->confirmations[] = $this->trans('Test data removed successfully.', [], 'Modules.Appointmentmanager.Admin');
                } else {
                    $this->context->controller->errors[] = $this->trans('An error occurred while removing test data.', [], 'Modules.Appointmentmanager.Admin');
                }
            } else {
                $this->context->controller->warnings[] = $this->trans('Reset cancelled.', [], 'Modules.Appointmentmanager.Admin');
            }
        }
        return '';
    }
    private function isTokenValid()
    {
        return Tools::getAdminTokenLite('AdminModules') === Tools::getValue('_token');
    }
    private function _displayAppointmentBlock($params)
    {
        $this->context->smarty->assign(array(
          'appointment_message' => $this->trans('Book an appointment for your property diagnosis', [], 'Modules.Appointmentmanager.Front'),
          'appointment_link' => $this->context->link->getModuleLink($this->name, 'appointmentmoduleform') // Corrected controller name
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
