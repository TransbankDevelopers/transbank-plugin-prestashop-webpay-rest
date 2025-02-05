<?php

namespace PrestaShop\Module\WebpayPlus\Helpers;

use Tools;
use PrestaShop\Module\WebpayPlus\Config\WebpayConfig;

/**
 * Trait InteractsWithWebpay.
 */
trait InteractsWithWebpay
{
    protected function webpayUpdateSettings()
    {
        $theEnvironmentChanged = false;
        $environment = Tools::getValue('form_webpay_environment');
        if (Tools::getIsset('btn_webpay_update')) {
            if ($environment != $this->getWebpayEnvironment()) {
                $theEnvironmentChanged = true;
            }
            WebpayConfig::setCommerceCode(trim(Tools::getValue('form_webpay_commerce_code')));
            WebpayConfig::setApiKey(trim(Tools::getValue('form_webpay_api_key')));
            WebpayConfig::setEnvironment($environment);
            WebpayConfig::setOrderStateIdAfterPayment(Tools::getValue('form_webpay_order_after_payment'));
        }
        return $theEnvironmentChanged;
    }
}
