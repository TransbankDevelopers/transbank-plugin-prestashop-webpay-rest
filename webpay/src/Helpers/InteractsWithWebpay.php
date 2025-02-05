<?php

namespace PrestaShop\Module\WebpayPlus\Helpers;

use Tools;
use Transbank\Webpay\Options;
use PrestaShop\Module\WebpayPlus\Config\WebpayConfig;
use PrestaShop\Module\WebpayPlus\Utils\StringUtils;
use Transbank\Plugin\Helpers\TbkConstants;

/**
 * Trait InteractsWithWebpay.
 */
trait InteractsWithWebpay
{

    protected function loadDefaultConfigurationWebpay(): void
    {
        $webpayEnvironment = WebpayConfig::getEnvironment();
        /* Si existe configuración de producción se copiara */
        if (isset($webpayEnvironment) && $webpayEnvironment == Options::ENVIRONMENT_PRODUCTION) {
            $webpayActive = WebpayConfig::getPaymentActive() ?? TbkConstants::ACTIVE_MODULE;
            $webpayCommerceCode = WebpayConfig::getCommerceCode();
            $webpayApiKey = WebpayConfig::getApiKey();
            $webpayDefaultOrderStateIdAfterPayment = WebpayConfig::getOrderStateIdAfterPayment();
            //si alguno de los datos falta se resetea
            if (
                StringUtils::isNotBlankOrNull($webpayCommerceCode)
                && StringUtils::isNotBlankOrNull($webpayApiKey)
                && StringUtils::isNotBlankOrNull($webpayDefaultOrderStateIdAfterPayment)
            ) {
                WebpayConfig::setPaymentActive($webpayActive);
                WebpayConfig::setCommerceCode($webpayCommerceCode);
                WebpayConfig::setApiKey($webpayApiKey);
                WebpayConfig::setOrderStateIdAfterPayment($webpayDefaultOrderStateIdAfterPayment);
                $this->logInfo("Configuración de WEBPAY PLUS se cargo de forma correcta =>
                    webpayCommerceCode: {$webpayCommerceCode}, webpayDefaultOrderStateIdAfterPayment:
                    {$webpayDefaultOrderStateIdAfterPayment}");
            } else {
                WebpayConfig::loadDefaultConfig();
                $this->logInfo(
                    "Configuración por defecto de WEBPAY PLUS se cargo de forma correcta porque los valores de producción están incompletos"
                );
            }
        } else {
            WebpayConfig::loadDefaultConfig();
            $this->logInfo("Configuración por defecto de WEBPAY PLUS se cargo de forma correcta");
        }
    }

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
