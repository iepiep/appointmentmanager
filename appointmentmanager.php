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

    public function hookDisplayHome($params)
    {
        $appointmentFormUrl = '#'; // Default/fallback URL

        // **** GET CONTAINER EXPLICITLY ****
        $container = SymfonyContainer::getInstance();

        if ($container) {
            try {
                // **** GET ROUTER FROM CONTAINER ****
                $router = $container->get('router');
                if ($router) {
                     // Generate the URL using the route name defined in routes.yml
                    $appointmentFormUrl = $router->generate('appointment_manager_form');
                } else {
                    // Log if router service itself is null, though get() should throw if not found
                     PrestaShopLogger::addLog('AppointmentManager: Router service resolved to null in hookDisplayHome.', 2);
                }
            } catch (\Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException $e) {
                // Log the error specifically if the service isn't found
                PrestaShopLogger::addLog('AppointmentManager: Router service not found in hookDisplayHome. ' . $e->getMessage(), 2); // Log as warning
                 // You could fallback to legacy link generation here if needed, but it's less ideal
                // $appointmentFormUrl = $this->context->link->getModuleLink($this->name, 'AppointmentManagerAppointmentFront', [], true);
            } catch (\Exception $e) {
                // Catch any other errors during route generation
                PrestaShopLogger::addLog('AppointmentManager: Error generating route in hookDisplayHome. ' . $e->getMessage(), 2); // Log as warning
            }
        } else {
             // Log if container itself isn't available
             PrestaShopLogger::addLog('AppointmentManager: Symfony container not available in hookDisplayHome.', 2);
        }

        $this->context->smarty->assign([
            'appointment_link' => $appointmentFormUrl // Assign the generated or fallback URL
        ]);

        // Make sure the template path is correct relative to the module root
        return $this->display(__FILE__, 'views/templates/hook/appointment_invite.tpl');
    }

    public function getContent()
    {
        $route = $this->get('router')->generate('appointment_manager_config');
        Tools::redirectAdmin($route);
    }
}