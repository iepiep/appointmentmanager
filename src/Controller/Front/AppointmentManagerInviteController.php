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

use PrestaShopBundle\Controller\Front\FrontController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class AppointmentManagerInviteController extends FrontController
{
    public function index(): Response
    {
        $link = $this->generateUrl('appointment_manager_form');
    
        return $this->render('@Modules/appointmentmanager/views/templates/hook/appointment_invite.tpl', [
            'appointment_link' => $link,
        ]);
    }    
}
