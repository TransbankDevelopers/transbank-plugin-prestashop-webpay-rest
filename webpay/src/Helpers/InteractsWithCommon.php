<?php

namespace PrestaShop\Module\WebpayPlus\Helpers;

use Configuration;
use Tools;

/**
 * Trait InteractsWithCommon.
 */
trait InteractsWithCommon
{
    protected function getDebugActive(){
        return Configuration::get('DEBUG_ACTIVE');
    }

    protected function setDebugActive($value){
        Configuration::updateValue('DEBUG_ACTIVE', $value);
    }

    protected function getFormDebugActive(){
        return trim(Tools::getValue('form_debug_active'));
    }

    public function commonUpdateSettings(){
        if (Tools::getIsset('btn_common_update')) {
            $this->setDebugActive($this->getFormDebugActive());
        }
    }

}
