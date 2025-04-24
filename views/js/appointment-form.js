/**
    * Copyright 2025 Solution61
    *
    * NOTICE OF LICENSE
    *
    * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
    * that is bundled with this package in the file LICENSE.md.
    * It is also available through the world-wide-web at this URL:
    * https://opensource.org/licenses/AFL-3.0
    * If you did not receive a copy of the license and are unable to
    * obtain it through the world-wide-web, please send an email
    * to license@prestashop.com so we can send you a copy immediately.
    *
    * @author    Roberto Minini <roberto.minini@solution61.fr>
    * @copyright 2025 Solution61
    * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
    */

// Wait for the DOM to be fully loaded before running the script
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // Standard Bootstrap 4 validation script
    // Fetch all the forms we want to apply custom Bootstrap validation styles to
    var forms = document.getElementsByClassName('needs-validation');

    // Loop over them and prevent submission if invalid
    var validation = Array.prototype.filter.call(forms, function(form) {
        form.addEventListener('submit', function(event) {
            if (form.checkValidity() === false) {
                event.preventDefault(); // Stop form submission
                event.stopPropagation(); // Stop event bubbling
            }
            // Add 'was-validated' class AFTER checking validity
            // This triggers Bootstrap's feedback styles
            form.classList.add('was-validated');
        }, false);
    });

    // Add any other specific JS logic for your form here
    // For example, checking if the two selected dates are different:
    const rdvOption1 = document.getElementById('rdv_option_1');
    const rdvOption2 = document.getElementById('rdv_option_2');
    const appointmentForm = document.getElementById('appointment-form');

    if (appointmentForm && rdvOption1 && rdvOption2) {
         appointmentForm.addEventListener('submit', function(event) {
            if (rdvOption1.value && rdvOption2.value && rdvOption1.value === rdvOption2.value) {
                 // You might want better feedback than an alert
                 alert("Veuillez choisir deux créneaux horaires différents.");
                 // You could also add custom validation feedback similar to Bootstrap's
                 rdvOption2.setCustomValidity("Les deux créneaux doivent être différents."); // Set custom validity
                 rdvOption2.classList.add('is-invalid'); // Add invalid class manually or rely on was-validated
                 event.preventDefault();
                 event.stopPropagation();
            } else if (rdvOption2) {
                 rdvOption2.setCustomValidity(""); // Clear custom validity if okay
            }
         });

         // Reset custom validity when the selection changes
         rdvOption2.addEventListener('change', function() {
            rdvOption2.setCustomValidity("");
         });
    }


}); // End DOMContentLoaded