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
    protected function installDB()
    {
        $sql_file = dirname(__FILE__).'/sql/install.sql';
        if (!file_exists($sql_file)) {
            return false;
        }
        // Utiliser Tools::file_get_contents() pour lire le fichier SQL
        $sql_content = Tools::file_get_contents($sql_file);
        $sql_content = str_replace('Prefix_', _DB_PREFIX_, $sql_content);
        // Séparer les requêtes à chaque point-virgule
        $sql_queries = array_filter(array_map('trim', explode(';', $sql_content)));
        foreach ($sql_queries as $query) {
            if (!empty($query)) {
                if (!Db::getInstance()->execute($query)) {
                    return false;
                }
            }
        }
        return true;
    }
    protected function uninstallDB()
    {
        $sql = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'appointment_manager`';
        return Db::getInstance()->execute($sql);
    }
    protected function installTabs()
    {
        $mainTab = new Tab();
        $mainTab->active = 1;
        $mainTab->class_name = 'AdminAppointmentManager';
        $mainTab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $mainTab->name[$lang['id_lang']] = 'AppointmentManager';
        }
        // Placer le module sous le parent des modules Symfony :
        $mainTab->id_parent = Tab::getIdFromClassName('AdminParentModulesSf');
        $mainTab->module = $this->name;
        if (!$mainTab->add()) {
            return false;
        }
        $configTab = new Tab();
        $configTab->active = 1;
        $configTab->class_name = 'AdminAppointmentManagerConfig';
        $configTab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $configTab->name[$lang['id_lang']] = 'Config';
        }
        $configTab->id_parent = $mainTab->id;
        $configTab->module = $this->name;
        if (!$configTab->add()) {
            return false;
        }
        $customerListTab = new Tab();
        $customerListTab->active = 1;
        $customerListTab->class_name = 'AdminAppointmentManagerCustomerList';
        $customerListTab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $customerListTab->name[$lang['id_lang']] = 'CustomerList';
        }
        $customerListTab->id_parent = $mainTab->id;
        $customerListTab->module = $this->name;
        if (!$customerListTab->add()) {
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
        $output = '';
        if (Tools::isSubmit('submitAppointmentManagerReset')) {
            if (Tools::getValue('confirm_reset') == '1') {
                $this->resetModule();
                $output .= $this->displayConfirmation($this->l('Test data removed successfully.'));
            } else {
                $output .= $this->displayError($this->l('Reset cancelled.'));
            }
        }
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminAppointmentManagerConfig'));
    }
    public function hookDisplayHome($params)
    {
        $this->context->smarty->assign(array(
            'appointment_message' => 'Prenez rendez-vous pour votre diagnostic immobilier',
            'appointment_link' => $this->context->link->getModuleLink($this->name, 'appointmentmoduleform')
        ));
        return $this->display(__FILE__, 'views/templates/front/appointment_block.tpl');
    }
    public function hookDisplayBanner($params)
    {
        return $this->hookDisplayHome($params);
    }
}
