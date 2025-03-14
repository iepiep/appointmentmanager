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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class AppointmentManagerAppointmentFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Par exemple, générer des choix pour des créneaux (ici pour les 15 prochains jours)
        $dates = [];
        $today = new \DateTime();
        for ($i = 0; $i < 15; $i++) {
            $date = clone $today;
            $date->modify("+$i days");
            $formattedDate = $date->format('d/m/Y');
            $dates["$formattedDate Matin"] = "$formattedDate Matin";
            $dates["$formattedDate Après-midi"] = "$formattedDate Après-midi";
        }

        $builder
            ->add('lastname', TextType::class, [
                'label' => $this->trans('Last Name', 'Modules.Appointmentmanager.Shop'),
                'constraints' => [new NotBlank(['message' => $this->trans('This field is required.', 'Modules.Appointmentmanager.Shop')])],
                'attr' => ['class' => 'form-control']
            ])
            ->add('firstname', TextType::class, [
                'label' => $this->trans('First Name', 'Modules.Appointmentmanager.Shop'),
                'constraints' => [new NotBlank(['message' => $this->trans('This field is required.', 'Modules.Appointmentmanager.Shop')])],
                'attr' => ['class' => 'form-control']
            ])
            ->add('address', TextType::class, [
                'label' => $this->trans('Address', 'Modules.Appointmentmanager.Shop'),
                'constraints' => [new NotBlank(['message' => $this->trans('This field is required.', 'Modules.Appointmentmanager.Shop')])],
                'attr' => ['class' => 'form-control']
            ])
            ->add('postal_code', TextType::class, [
                'label' => $this->trans('Postal Code', 'Modules.Appointmentmanager.Shop'),
                'constraints' => [new NotBlank(['message' => $this->trans('This field is required.', 'Modules.Appointmentmanager.Shop')])],
                'attr' => ['class' => 'form-control']
            ])
            ->add('city', TextType::class, [
                'label' => $this->trans('City', 'Modules.Appointmentmanager.Shop'),
                'constraints' => [new NotBlank(['message' => $this->trans('This field is required.', 'Modules.Appointmentmanager.Shop')])],
                'attr' => ['class' => 'form-control']
            ])
            ->add('phone', TelType::class, [
                'label' => $this->trans('Phone', 'Modules.Appointmentmanager.Shop'),
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('email', EmailType::class, [
                'label' => $this->trans('Email', 'Modules.Appointmentmanager.Shop'),
                'required' => false,
                'constraints' => [new Email(['message' => $this->trans('Please enter a valid email address.', 'Modules.Appointmentmanager.Shop')])],
                'attr' => ['class' => 'form-control']
            ])
            ->add('rdv_option_1', ChoiceType::class, [
                'label' => $this->trans('First Time Slot', 'Modules.Appointmentmanager.Shop'),
                'choices' => $dates,
                'constraints' => [new NotBlank(['message' => $this->trans('This field is required.', 'Modules.Appointmentmanager.Shop')])],
                'attr' => ['class' => 'form-control']
            ])
            ->add('rdv_option_2', ChoiceType::class, [
                'label' => $this->trans('Second Time Slot', 'Modules.Appointmentmanager.Shop'),
                'choices' => $dates,
                'constraints' => [new NotBlank(['message' => $this->trans('This field is required.', 'Modules.Appointmentmanager.Shop')])],
                'attr' => ['class' => 'form-control']
            ])
            ->add('GDPR', CheckboxType::class, [
                'label' => $this->trans('I accept the privacy policy', 'Modules.Appointmentmanager.Shop'),
                'mapped' => false,
                'constraints' => [new NotBlank(['message' => $this->trans('You must accept the privacy policy.', 'Modules.Appointmentmanager.Shop')])],

            ])
            ->add('submit', SubmitType::class, [
                'label' => $this->trans('Submit', 'Modules.Appointmentmanager.Shop'),
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }
}    
