<?php
// FILE: modules/appointmentmanager/controllers/front/appointment.php

if (!defined('_PS_VERSION_')) {
    exit;
}

// We might need the container to get the router for forwarding
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;

/**
 * This is a traditional ModuleFrontController.
 * Its only purpose is to redirect to the modern Symfony route.
 */
class AppointmentManagerAppointmentModuleFrontController extends ModuleFrontController
{
    /**
     * Redirects to the Symfony route defined in routes.yml
     */
    public function init() // Use init() for redirects before content generation
    {
        parent::init(); // Important to initialize context, etc.

        $modernRouteName = 'appointment_manager_form'; // The name of your route in routes.yml
        $redirectUrl = null;

        // Try getting the router via the container
        $container = SymfonyContainer::getInstance();
        if ($container) {
            try {
                $router = $container->get('router');
                if ($router) {
                    // Generate the URL for the nice /rendez-vous route
                    $redirectUrl = $router->generate($modernRouteName);
                    PrestaShopLogger::addLog('AppointmentManager (Legacy Forwarder): Forwarding to modern route: ' . $redirectUrl, 1);
                } else {
                    PrestaShopLogger::addLog('AppointmentManager (Legacy Forwarder): Router service was NULL.', 3);
                }
            } catch (\Exception $e) {
                 PrestaShopLogger::addLog('AppointmentManager (Legacy Forwarder): Exception getting router/generating route. Msg: ' . $e->getMessage(), 3);
            }
        } else {
             PrestaShopLogger::addLog('AppointmentManager (Legacy Forwarder): Symfony container was NULL.', 3);
        }

        // If URL generation failed, fall back to a basic URL or homepage
        if (!$redirectUrl) {
            // Fallback: Redirect to homepage if we couldn't generate the specific route
            $redirectUrl = $this->context->link->getPageLink('index', true);
            PrestaShopLogger::addLog('AppointmentManager (Legacy Forwarder): Falling back to homepage redirect.', 2);
        }

        // Perform the redirect
        Tools::redirect($redirectUrl);
        exit; // Essential to stop further processing
    }

    // No initContent or postProcess needed as we redirect in init()
}