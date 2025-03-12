<?php
/**
 * @author Roberto Minini <r.minini@solution61.fr>
 * @copyright 2025 Roberto Minini
 * @license MIT
 *
 * This file is part of the dimrdv project.
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace PrestaShop\Module\AppointmentManager\Form;

use PrestaShop\PrestaShop\Core\Configuration\DataConfigurationInterface;
use PrestaShop\PrestaShop\Core\ConfigurationInterface;

/**
 * Configuration is used to save data to configuration table and retrieve from it.
 */
final class AppointmentManagerDataConfig implements DataConfigurationInterface
{
    public const APPOINTMENTMANAGER_GOOGLE_API_KEY = 'APPOINTMENTMANAGER_GOOGLE_API_KEY';
    public const APPOINTMENTMANAGER_API_KEY_MAXLENGTH = 40;

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

        return $return;
    }

    public function updateConfiguration(array $configuration): array
    {
        $errors = [];

        if ($this->validateConfiguration($configuration)) {
            if (strlen($configuration['google_api_key']) <= static::APPOINTMENTMANAGER_API_KEY_MAXLENGTH) {
                $this->configuration->set(static::APPOINTMENTMANAGER_GOOGLE_API_KEY, $configuration['google_api_key']);
            } else {
                $errors[] = 'APPOINTMENTMANAGER_GOOGLE_API_KEY value is too long';
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
        return isset($configuration['google_api_key']);
    }
}
