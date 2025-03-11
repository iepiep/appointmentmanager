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
        if (!parent::install() ||
            !$this->installDB() ||
            !$this->installTabs() ||
            !$this->registerHook('displayHome') ||
            !$this->registerHook('displayBanner')
        ) {
            return false;
        }
        // Good practice to use trans() for default values, even if they are unlikely to change
        Configuration::updateValue('APPOINTMENTMANAGER_GOOGLE_API_KEY', '');
        Configuration::updateValue('APPOINTMENTMANAGER_START_TIME', '08:30');
        Configuration::updateValue('APPOINTMENTMANAGER_APPOINTMENT_LENGTH', 120);
        Configuration::updateValue('APPOINTMENTMANAGER_BREAK_LENGTH', 60);
        Configuration::updateValue('APPOINTMENTMANAGER_HOME_ADDRESS', ''); // Home address
        Configuration::updateValue('APPOINTMENTMANAGER_HOME_POSTAL_CODE', ''); // Home postal code
        Configuration::updateValue('APPOINTMENTMANAGER_HOME_CITY', ''); // Home city
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
            return false;
        }

        $sql_content = Tools::file_get_contents($sql_file);
        $sql_content = str_replace('Prefix_', _DB_PREFIX_, $sql_content);
        $sql_queries = array_filter(array_map('trim', explode(';', $sql_content)));

        Db::getInstance()->execute('START TRANSACTION'); // Begin transaction

        try {
            foreach ($sql_queries as $query) {
                if (trim($query) != '') {
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
        $result = Db::getInstance()->execute($sql);
        if (!$result) {
            // Ajouter un log ou un message d'erreur pour aider au débogage
            PrestaShopLogger::addLog('Erreur lors de la suppression de la table appointment_manager: ' . Db::getInstance()->getMsgError(), 3);
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
        $mainTab->id_parent = -1; // Top-level menu
        $mainTab->icon = 'local_shipping';
        $mainTab->name = [];
        foreach (Language::getLanguages(true) as $lang) {
            $mainTab->name[$lang['id_lang']] = $this->trans('Appointment Manager', [], 'Modules.Appointmentmanager.Admin');
        }
        if (!$mainTab->add()) {
            return false;
        }

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
            return false;
        }

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
            return false;
        }

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
