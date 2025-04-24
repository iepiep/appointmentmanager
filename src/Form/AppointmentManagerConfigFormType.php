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
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

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
                    'label' => $this->trans('Google API key', 'Modules.Appointmentmanager.Admin'),
                    'required' => true,
                    'help' => $this->trans('Enter your Google Maps API key.', 'Modules.Appointmentmanager.Admin'),
                    'constraints' => [
                        new NotBlank(),
                        new Length(['max' => 40]),
                    ],
                ]
            )
            ->add(
                'appointment_length',
                NumberType::class,
                [
                    'label' => $this->trans('Appointment length (minutes)', 'Modules.Appointmentmanager.Admin'),
                    'required' => true,
                    'help' => $this->trans('Default duration for each appointment.', 'Modules.Appointmentmanager.Admin'),
                    'constraints' => [
                        new NotBlank(),
                        new Range(['min' => 30, 'max' => 240]),
                    ],
                ]
            )
            ->add(
                'lunch_break_length',
                NumberType::class,
                [
                    'label' => $this->trans('Lunch break length (minutes)', 'Modules.Appointmentmanager.Admin'),
                    'required' => true,
                    'help' => $this->trans('Duration of the lunch break (0 for no break).', 'Modules.Appointmentmanager.Admin'),
                    'constraints' => [
                        new NotBlank(), // If 0 is allowed, maybe NotNull is better, but NotBlank ensures *something* is entered.
                        new Range(['min' => 0, 'max' => 90]),
                    ],
                ]
            );
    }
}
