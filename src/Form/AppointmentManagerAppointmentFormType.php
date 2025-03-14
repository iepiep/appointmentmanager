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
                'label' => 'Nom',
                'constraints' => [new NotBlank()]
            ])
            ->add('firstname', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [new NotBlank()]
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'constraints' => [new NotBlank()]
            ])
            ->add('postal_code', TextType::class, [
                'label' => 'Code postal',
                'constraints' => [new NotBlank()]
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'constraints' => [new NotBlank()]
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => false
            ])
            ->add('rdv_option_1', ChoiceType::class, [
                'label' => 'Premier créneau',
                'choices' => $dates,
                'constraints' => [new NotBlank()]
            ])
            ->add('rdv_option_2', ChoiceType::class, [
                'label' => 'Deuxième créneau',
                'choices' => $dates,
                'constraints' => [new NotBlank()]
            ])
            ->add('GDPR', CheckboxType::class, [
                'label' => 'GDPR',
                'mapped' => false,
                'constraints' => [new NotBlank()]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Envoyer'
            ]);
    }
}
