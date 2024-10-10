<?php

namespace PrestaShop\Module\WebpayPlus\Helpers;

use PrestaShop\Module\WebpayPlus\Form\DiagnosisDataConfiguration;
use Configuration;
use Tools;

/**
 * Trait InteractsWithCommon.
 */
trait InteractsWithCommon
{
    protected function getDebugActive(){
        return Configuration::get(DiagnosisDataConfiguration::WEBPAY_DEBUG_ACTIVE);
    }

    protected function setDebugActive($value){
        Configuration::updateValue(DiagnosisDataConfiguration::WEBPAY_DEBUG_ACTIVE, $value);
    }

    protected function getFormDebugActive(){
        return trim(Tools::getValue('form_debug_active'));
    }

    public function commonUpdateSettings(){
        if (Tools::getIsset('btn_common_update')) {
            $this->setDebugActive($this->getFormDebugActive());
        }
    }

    protected function isDebugActive(){
        return Configuration::get(DiagnosisDataConfiguration::WEBPAY_DEBUG_ACTIVE) ===
            DiagnosisDataConfiguration::ENABLED;
    }

}
