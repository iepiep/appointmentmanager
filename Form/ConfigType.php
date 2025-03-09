<?php
/**
 * ConfigType form
 *
 * Author: Roberto Minini (iepiep74@gmail.com)
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
        $builder->add('google_api_key', TextType::class, array(
                    'label'    => 'Google API Key',
                    'required' => false
                ))
                ->add('start_time', TextType::class, array(
                    'label'    => 'Start Time',
                    'data'     => '08:30'
                ))
                ->add('appointment_length', IntegerType::class, array(
                    'label'    => 'Appointment Length (minutes)',
                    'data'     => 120
                ))
                ->add('break_length', IntegerType::class, array(
                    'label'    => 'Break Length (minutes)',
                    'data'     => 60
                ));
    }
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array());
    }
}