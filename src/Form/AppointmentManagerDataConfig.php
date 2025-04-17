<?php
/**
 * @author Roberto Minini <r.minini@solution61.fr>
 * @copyright 2025 Roberto Minini
 * @license MIT
 *
 * This file is part of the AppointmentManager project.
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace PrestaShop\Module\AppointmentManager\Form;

use PrestaShop\PrestaShop\Core\Configuration\DataConfigurationInterface;
use PrestaShop\PrestaShop\Core\ConfigurationInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Configuration is used to save data to configuration table and retrieve from it.
 */
final class AppointmentManagerDataConfig implements DataConfigurationInterface
{
    public const APPOINTMENTMANAGER_GOOGLE_API_KEY = 'APPOINTMENTMANAGER_GOOGLE_API_KEY';
    public const APPOINTMENTMANAGER_APPOINTMENT_LENGTH = 'APPOINTMENTMANAGER_APPOINTMENT_LENGTH';
    public const APPOINTMENTMANAGER_LUNCH_BREAK_LENGTH = 'APPOINTMENTMANAGER_LUNCH_BREAK_LENGTH';

    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getConfiguration(): array
    {
        $return = [];

        $return['google_api_key'] = $this->configuration->get(static::APPOINTMENTMANAGER_GOOGLE_API_KEY);
        $return['appointment_length'] = $this->configuration->get(static::APPOINTMENTMANAGER_APPOINTMENT_LENGTH);
        $return['lunch_break_length'] = $this->configuration->get(static::APPOINTMENTMANAGER_LUNCH_BREAK_LENGTH);

        return $return;
    }

    public function updateConfiguration(array $configuration): array
    {
        $errors = [];

        if ($this->validateConfiguration($configuration)) {
            if (strlen($configuration['google_api_key']) <= 40) {
                $this->configuration->set(static::APPOINTMENTMANAGER_GOOGLE_API_KEY, $configuration['google_api_key']);
            } else {
                $errors[] = 'appointmentmanager.configuration.google_api_key_too_long';
            }

            $appointmentLength = (int) $configuration['appointment_length'];
            if ($appointmentLength < 30 || $appointmentLength > 240) {
                $errors[] = 'appointmentmanager.configuration.appointment_length_invalid_range';
            } else {
                $this->configuration->set(static::APPOINTMENTMANAGER_APPOINTMENT_LENGTH, $configuration['appointment_length']);
            }

            $lunchBreakLength = (int) $configuration['lunch_break_length'];
            if ($lunchBreakLength < 0 || $lunchBreakLength > 90) {
                $errors[] = 'appointmentmanager.configuration.lunch_break_length_invalid_range';
            } else {
                $this->configuration->set(static::APPOINTMENTMANAGER_LUNCH_BREAK_LENGTH, $configuration['lunch_break_length']);
            }
        }

        /* Errors are returned here. */
        return $errors;
    }

    /**
     * Ensure the parameters passed are valid.
     *
     * @return bool Returns true if no exception are thrown
     */
    public function validateConfiguration(array $configuration): bool
    {
        return isset($configuration['google_api_key'])
            && isset($configuration['appointment_length'])
            && isset($configuration['lunch_break_length']);
    }
}
