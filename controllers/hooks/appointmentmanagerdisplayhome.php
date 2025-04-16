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

 use ModuleFrontController;
 
 if (!defined('_PS_VERSION_')) {
     exit;
 }

class AppointmentManagerInviteController extends ModuleFrontController
{
    public function initContent(): Response
    {
        $link = $this->generateUrl('appointment_manager_form');
    
        return $this->render('@Modules/appointmentmanager/views/templates/hook/appointment_invite.tpl', [
            'appointment_link' => $link,
        ]);



        
        $this->context->smarty->assign([
            'form_link' => $this->context->link->getModuleLink('appointmentmanager', 'appointmentmanagerdisplayhome')
        ]);

        return $this->display(__FILE__, 'appointment_invite.tpl');





    }    
}


$this->context->smarty->assign([
    'appointment_link' => $this->context->link->getModuleLink('appointmentmanager', 'appointmentmanagerdisplayhome')
]);

return $this->display(__FILE__, 'appointment_invite.tpl');