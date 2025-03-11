<?php
/**
 * @author Roberto Minini <r.minini@solution61.fr>
 * @copyright 2025 Roberto Minini
 * @license MIT
 *
 * This file is part of the AppointmentManager Module
 * License: MIT License
 */

 namespace AppointmentManager\Form;

 if (!defined('_PS_VERSION_')) {
    exit;
}

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class ConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('google_api_key', TextType::class, [
                'label' => 'Google API Key',
                'required' => false
            ])
            ->add('start_time', TextType::class, [
                'label' => 'Start Time',
                'data' => '08:30'
            ])
            ->add('appointment_length', IntegerType::class, [
                'label' => 'Appointment Length (minutes)',
                'data' => 120
            ])
            ->add('break_length', IntegerType::class, [
                'label' => 'Break Length (minutes)',
                'data' => 60
            ])
            ->add('home_address', TextType::class, [  // Add missing home address fields
                'label' => 'Home Address',
                'required' => true
            ])
            ->add('home_postal_code', TextType::class, [
                'label' => 'Home Postal Code',
                'required' => true
            ])
            ->add('home_city', TextType::class, [
                'label' => 'Home City',
                'required' => true
            ]);
    }
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array());
    }
}
