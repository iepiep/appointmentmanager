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
use PrestaShop\PrestaShop\Core\Form\FormDataProviderInterface;

/**
 * Provider is responsible for providing form data, in this case, it is returned from the configuration component.
 *
 * Class AppointmentManagerDataProvider
 */
class AppointmentManagerDataProvider implements FormDataProviderInterface
{
    /**
     * @var DataConfigurationInterface
     */
    private $AppointmentManagerDataConfig;

    public function __construct(DataConfigurationInterface $AppointmentManagerDataConfig)
    {
        $this->AppointmentManagerDataConfig = $AppointmentManagerDataConfig;
    }

    public function getData(): array
    {
        return $this->AppointmentManagerDataConfig->getConfiguration();
    }

    public function setData(array $data): array
    {
        return $this->AppointmentManagerDataConfig->updateConfiguration($data);
    }
}
