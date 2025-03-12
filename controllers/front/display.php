<?php
/**
 * @author Roberto Minini <r.minini@solution61.fr>
 * @copyright 2025 Roberto Minini
 * @license MIT
 *
 * This file is part of the dimrdv project.
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

 if (!defined('_PS_VERSION_')) {
    exit;
}

class appointmentmanagerdisplayModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        $this->setTemplate('module:appointmentmanager/views/templates/front/display.tpl');
    }
}
