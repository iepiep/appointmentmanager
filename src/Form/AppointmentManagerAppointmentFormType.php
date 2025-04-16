<?php
// FILE: src/Form/AppointmentManagerAppointmentFormType.php

declare(strict_types=1);

namespace PrestaShop\Module\AppointmentManager\Form;

// ** CHANGE TO TranslatorAwareType for easier access to $this->trans() **
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;

class AppointmentManagerAppointmentFormType extends TranslatorAwareType

{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Generate date choices (Keep existing logic, maybe refine based on BO config later)
        $dates = [];
        $today = new \DateTime();
        $today->setTime(0, 0);
        for ($i = 0; $i < 15; $i++) { // Maybe make '15' configurable?
            $date = clone $today;
            $date->modify("+$i days");
            // Skip Saturday (6) and Sunday (7)
            if ($date->format('N') >= 6) {
                 continue;
            }
            $formattedDate = $date->format('d/m/Y');
            // Use more customer-friendly labels for time slots if needed
            $dates[$this->trans('%date% Morning', ['%date%' => $formattedDate], 'Modules.Appointmentmanager.Shop')] = $formattedDate . ' Matin';
            $dates[$this->trans('%date% Afternoon', ['%date%' => $formattedDate], 'Modules.Appointmentmanager.Shop')] = $formattedDate . ' Après-midi';
        }

        $builder
            ->add('lastname', TextType::class, [
                'label' => $this->trans('Last Name', 'Modules.Appointmentmanager.Shop'),
                'constraints' => [new NotBlank(['message' => $this->trans('Please enter your last name.', 'Modules.Appointmentmanager.Shop')])], // Customer-friendly message
                'attr' => ['class' => 'form-control', 'placeholder' => $this->trans('Chevalier', 'Modules.Appointmentmanager.Shop')], // Add placeholder
                'required' => true,
            ])
            ->add('firstname', TextType::class, [
                'label' => $this->trans('First Name', 'Modules.Appointmentmanager.Shop'),
                'constraints' => [new NotBlank(['message' => $this->trans('Please enter your first name.', 'Modules.Appointmentmanager.Shop')])],
                'attr' => ['class' => 'form-control', 'placeholder' => $this->trans('Maurice', 'Modules.Appointmentmanager.Shop')],
                'required' => true,
            ])
            ->add('address', TextType::class, [
                'label' => $this->trans('Address', 'Modules.Appointmentmanager.Shop'),
                'constraints' => [new NotBlank(['message' => $this->trans('Please enter your address.', 'Modules.Appointmentmanager.Shop')])],
                'attr' => ['class' => 'form-control', 'placeholder' => $this->trans('8 rue de Rivoli', 'Modules.Appointmentmanager.Shop')],
                'required' => true,
            ])
            ->add('postal_code', TextType::class, [
                'label' => $this->trans('Postal Code', 'Modules.Appointmentmanager.Shop'),
                'constraints' => [new NotBlank(['message' => $this->trans('Please enter your postal code.', 'Modules.Appointmentmanager.Shop')])],
                'attr' => ['class' => 'form-control', 'placeholder' => $this->trans('75001', 'Modules.Appointmentmanager.Shop')],
                'required' => true,
            ])
            ->add('city', TextType::class, [
                'label' => $this->trans('City', 'Modules.Appointmentmanager.Shop'),
                'constraints' => [new NotBlank(['message' => $this->trans('Please enter your city.', 'Modules.Appointmentmanager.Shop')])],
                'attr' => ['class' => 'form-control', 'placeholder' => $this->trans('Paris', 'Modules.Appointmentmanager.Shop')],
                'required' => true,
            ])
            ->add('phone', TelType::class, [
                'label' => $this->trans('Phone', 'Modules.Appointmentmanager.Shop'),
                'required' => false, // Still optional as per original logic
                'attr' => ['class' => 'form-control', 'placeholder' => $this->trans('Optional', 'Modules.Appointmentmanager.Shop')],
                 // Add validation constraints if needed (e.g., length, format)
                'help' => $this->trans('Used only to contact you about the appointment.', 'Modules.Appointmentmanager.Shop') // Add help text
            ])
            ->add('email', EmailType::class, [
                'label' => $this->trans('Email', 'Modules.Appointmentmanager.Shop'),
                'required' => false, // Still optional
                'constraints' => [new Email(['message' => $this->trans('Please enter a valid email address.', 'Modules.Appointmentmanager.Shop')])],
                'attr' => ['class' => 'form-control', 'placeholder' => $this->trans('Optional', 'Modules.Appointmentmanager.Shop')],
                'help' => $this->trans('Used only to contact you about the appointment.', 'Modules.Appointmentmanager.Shop') // Add help text
            ])
            ->add('rdv_option_1', ChoiceType::class, [
                'label' => $this->trans('Preferred time slot', 'Modules.Appointmentmanager.Shop'),
                'choices' => $dates,
                'placeholder' => $this->trans('Choose a time slot...', 'Modules.Appointmentmanager.Shop'), // Customer-friendly placeholder
                'constraints' => [new NotBlank(['message' => $this->trans('Please select your preferred time slot.', 'Modules.Appointmentmanager.Shop')])],
                'attr' => ['class' => 'form-control custom-select'], // Use custom-select for better dropdown styling
                'required' => true,
            ])
            ->add('rdv_option_2', ChoiceType::class, [
                'label' => $this->trans('Alternative time slot', 'Modules.Appointmentmanager.Shop'),
                'choices' => $dates,
                'placeholder' => $this->trans('Choose a time slot...', 'Modules.Appointmentmanager.Shop'),
                'constraints' => [new NotBlank(['message' => $this->trans('Please select an alternative time slot.', 'Modules.Appointmentmanager.Shop')])],
                'attr' => ['class' => 'form-control custom-select'],
                'required' => true,
                 // Add validation to ensure option 2 is different from option 1 if needed (complex)
            ])
            ->add('GDPR', CheckboxType::class, [
                 // Label is handled in the Twig template to include the link easily
                'label' => false,
                'mapped' => false, // Correct, not part of the data saved directly like this
                'constraints' => [new NotBlank(['message' => $this->trans('You must accept the privacy policy to submit your request.', 'Modules.Appointmentmanager.Shop')])],
                'required' => true, // Makes the checkbox mandatory
                'attr' => ['class' => 'form-check-input'] // Standard class for BS checkbox
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // No data_class needed if not mapping directly to an entity/object
            // 'csrf_protection' => true, // Enable CSRF protection (good practice)
            // 'csrf_field_name' => '_token',
            // 'csrf_token_id'   => 'appointment_item', // Unique ID for the token
        ]);
    }
}