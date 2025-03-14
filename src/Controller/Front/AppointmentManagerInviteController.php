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

namespace PrestaShop\Module\AppointmentManager\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;

class AppointmentInviteController extends AbstractController
{
    public function index(): Response
    {
        // Générer le lien vers le formulaire de prise de RDV
        $link = SymfonyContainer::getInstance()->get('router')->generate(
            'admin_appointment_front_form',
            [],
            \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL
        );

        return $this->render('@Modules/appointmentmanager/views/templates/hook/appointment_invite.html.twig', [
            'appointment_link' => $link,
        ]);
    }
}
