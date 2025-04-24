{**
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
 *}
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8 col-12 text-center bg-light p-4 rounded shadow-sm">
            <h2 class="text-primary mb-3">Besoin d'un rendez-vous ?</h2>
            <p class="text-secondary mb-4">Nous sommes à votre disposition pour organiser un rendez-vous à votre convenance.</p>
            <!-- Le bouton renvoie vers le formulaire (l'URL est passée via $appointment_link) -->
            <a href="{$appointment_link|escape:'html':'UTF-8'}" class="btn btn-primary btn-lg px-4 py-2">Prendre un rendez-vous</a>
        </div>
    </div>
</div>