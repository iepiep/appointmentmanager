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

use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

class AppointmentManagerConfigFormType extends TranslatorAwareType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'google_api_key',
                TextType::class,
                [
                    'label' => $this->trans('Google API key', 'Modules.AppointmentManager.Admin'),
                    'required'   => true,
                    'help' => $this->trans('Maximum 40 characters', 'Modules.AppointmentManager.Admin'),
                ]
            )
            ->add(
                'appointment_length',
                TextType::class,
                [
                    'label' => $this->trans('Appointment length', 'Modules.AppointmentManager.Admin'),
                    'required'   => true,
                    'help' => $this->trans('Enter appointment length in minutes (30-240)', 'Modules.AppointmentManager.Admin'),
                ]
            )
            ->add(
                'lunch_break_length',
                TextType::class,
                [
                    'label' => $this->trans('Lunch break length', 'Modules.AppointmentManager.Admin'),
                    'required'   => true,
                    'help' => $this->trans('Enter lunch break length in minutes (0-90)', 'Modules.AppointmentManager.Admin'),
                ]
            );
    }
}
