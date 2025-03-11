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

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

class AppointmentManager extends Module implements WidgetInterface
{
    public function __construct()
    {
        $this->name = 'appointmentmanager';
        $this->tab = 'administration'; // Onglet dans lequel le module sera listé (peut être changé)
        $this->version = '1.0.0';
        $this->author = 'Roberto Minini';
        $this->need_instance = 0; // Pas besoin d'instance pour ce module simple
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
        $this->bootstrap = true; // Utilisation de Bootstrap de PrestaShop

        parent::__construct();

        $this->displayName = $this->l('Appointment Manager', 'Modules.Appointmentmanager.Admin');
        $this->description = $this->l('Manage appointments easily.', 'Modules.Appointmentmanager.Admin');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?', 'Modules.Appointmentmanager.Admin');

        if (!Configuration::get('APPOINTMENTMANAGER_NAME')) { // Exemple, peut être supprimé si pas de config
            $this->warning = $this->l('No name provided', 'Modules.Appointmentmanager.Admin');
        }

        $this->author_address = 'iepiep74@gmail.com'; // Pour la licence, visible dans le BO
        $this->module_key = 'your_module_key'; // Clef du module pour le PrestaShop Addons (si distribution)
    }

    /**
     * @return bool
     */
    public function install(): bool
    {
        if (parent::install() &&
            $this->installTab()
        ) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function uninstall(): bool
    {
        if (parent::uninstall() &&
            $this->uninstallTab()
        ) {
            return true;
        }

        return false;
    }

    /**
     * Installation des tabs dans le menu admin
     *
     * @return bool
     */
    public function installTab(): bool
    {
        $tabParent = new Tab();
        $tabParent->active = 1;
        $tabParent->class_name = 'AppointmentManager'; // Class name, sans suffixe 'Controller'
        $tabParent->name = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tabParent->name[$lang['id_lang']] = $this->l('Appointment Manager', 'Modules.Appointmentmanager.Admin'); // Nom du tab principal
        }
        $tabParent->id_parent = 0; // Tab principal
        $tabParent->module = $this->name;
        if (!$tabParent->add()) {
            return false;
        }

        $tabConfig = new Tab();
        $tabConfig->active = 1;
        $tabConfig->class_name = 'AdminAppointmentManagerConfig'; // Correspond au nom du controller
        $tabConfig->name = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tabConfig->name[$lang['id_lang']] = $this->l('Config', 'Modules.Appointmentmanager.Admin'); // Nom du tab config
        }
        $tabConfig->id_parent = (int)Tab::getIdFromClassName('AppointmentManager'); // Sous-tab de 'AppointmentManager'
        $tabConfig->module = $this->name;
        if (!$tabConfig->add()) {
            return false;
        }

        $tabCustomerList = new Tab();
        $tabCustomerList->active = 1;
        $tabCustomerList->class_name = 'AdminAppointmentManagerCustomerList'; // Correspond au nom du controller
        $tabCustomerList->name = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tabCustomerList->name[$lang['id_lang']] = $this->l('CustomerList', 'Modules.Appointmentmanager.Admin'); // Nom du tab CustomerList
        }
        $tabCustomerList->id_parent = (int)Tab::getIdFromClassName('AppointmentManager'); // Sous-tab de 'AppointmentManager'
        $tabCustomerList->module = $this->name;
        if (!$tabCustomerList->add()) {
            return false;
        }

        return true;
    }

    /**
     * Suppression des tabs lors de la désinstallation
     *
     * @return bool
     */
    public function uninstallTab(): bool
    {
        $tabId = (int)Tab::getIdFromClassName('AdminAppointmentManagerConfig');
        if ($tabId) {
            $tab = new Tab($tabId);
            if (!$tab->delete()) {
                return false;
            }
        }

        $tabId = (int)Tab::getIdFromClassName('AdminAppointmentManagerCustomerList');
        if ($tabId) {
            $tab = new Tab($tabId);
            if (!$tab->delete()) {
                return false;
            }
        }

        $tabIdParent = (int)Tab::getIdFromClassName('AppointmentManager');
        if ($tabIdParent) {
            $tabParent = new Tab($tabIdParent);
            if (!$tabParent->delete()) {
                return false;
            }
        }

        return true;
    }

    /**
     *  Fonction getContent (ancienne méthode, ici on utilise les controllers Symfony)
     *  Elle n'est plus nécessaire pour les pages de configuration gérées par Symfony.
     *  On pourrait la supprimer si on ne souhaite pas l'utiliser du tout.
     *  Elle pourrait être utile pour afficher une configuration rapide si besoin, hors Symfony.
     *
     * @return string
     */
    /*
    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submit'.$this->name)) {
            $configValue = strval(Tools::getValue('APPOINTMENTMANAGER_NAME'));
            if (!Validate::isGenericName($configValue)) {
                $output = $this->displayError($this->l('Invalid Configuration value', 'Modules.Appointmentmanager.Admin'));
            } else {
                Configuration::updateValue('APPOINTMENTMANAGER_NAME', $configValue);
                $output = $this->displayConfirmation($this->l('Settings updated', 'Modules.Appointmentmanager.Admin'));
            }
        }

        return $output.$this->displayForm();
    }


    public function displayForm()
    {
        // Init Fields form array
        $fieldsForm[0]['form'] = [
            'legend' => [
                'title' => $this->l('Settings', 'Modules.Appointmentmanager.Admin'),
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Configuration value', 'Modules.Appointmentmanager.Admin'),
                    'name' => 'APPOINTMENTMANAGER_NAME',
                    'size' => 20,
                    'required' => true,
                ],
            ],
            'submit' => [
                'title' => $this->l('Save', 'Modules.Appointmentmanager.Admin'),
                'class' => 'btn btn-default pull-right',
                'name' => 'submit'.$this->name,
            ],
        ];

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // Language
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = (int)Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = [
            'save' => [
                'desc' => $this->l('Save', 'Modules.Appointmentmanager.Admin'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                '&token='.Tools::getAdminTokenLite('AdminModules'),
            ],
            'back' => [
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list', 'Modules.Appointmentmanager.Admin'),
            ],
        ];

        // Load current value
        $helper->fields_value['APPOINTMENTMANAGER_NAME'] = Configuration::get('APPOINTMENTMANAGER_NAME');

        return $helper->generateForm($fieldsForm);
    }
    */

    public function renderWidget($hookName, array $params): string
    {
        // Exemple de widget, non utilisé ici pour les pages admin, mais pour le front office
        // return $this->display(__FILE__, 'widget.tpl'); // Si on avait un template widget.tpl
        return ''; // Module admin, pas de widget front dans cet exemple
    }

    public function getWidgetVariables($hookName, array $params): array
    {
        // Variables pour le widget, non utilisé ici
        return [];
    }

    public function getTabClassNamePrefix(): string
    {
        return 'AppointmentManager'; // Préfixe pour les class_name des tabs (important pour PS 8+)
    }
}
