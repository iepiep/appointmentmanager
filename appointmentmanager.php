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
    
        return parent::install() &&
            $this->registerHook('displayLeftColumn') &&
            $this->registerHook('actionFrontControllerSetMedia') &&
            $this->registerHook('displayRightColumn') &&
            Configuration::updateValue('APPOINTMENTMANAGER_NAME', 'Appointment Manager');
    }

    public function uninstall()
    {
        return (
            parent::uninstall()
            && Configuration::deleteByName('APPOINTMENTMANAGER_NAME')
            && Configuration::deleteByName('APPOINTMENTMANAGER_GOOGLE_API_KEY')
            && Configuration::deleteByName('APPOINTMENTMANAGER_APPOINTMENT_LENGTH')
            && Configuration::deleteByName('APPOINTMENTMANAGER_LUNCH_BREAK_LENGTH')
        );
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

        $this->context->controller->registerJavascript(
            'appointmentmanager-javascript',
            'modules/' . $this->name . '/views/js/appointmentmanager.js',
            [
                'position' => 'bottom',
                'priority' => 1000,
            ]
        );
    }

    public function getContent()
    {
        $route = $this->get('router')->generate('appointment_manager_config');
        Tools::redirectAdmin($route);
    }
  }
