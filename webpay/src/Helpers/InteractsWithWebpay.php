<?php

namespace PrestaShop\Module\WebpayPlus\Helpers;

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use Configuration;
use Tools;
use Media;
use Transbank\Webpay\WebpayPlus;
use Transbank\Webpay\Options;
use PrestaShop\Module\WebpayPlus\Utils\StringUtils;

/**
 * Trait InteractsWithWebpay.
 */
trait InteractsWithWebpay
{
    protected function getWebpayPaymentOption($base, $context)
    {
        if ($this->getWebpayActive() != 1) {
            return [];
        }
        $WPOption = new PaymentOption();
        $paymentController = $context->link->getModuleLink($base->name, 'webpaypluspayment', array(), true);
        $message = "Permite el pago de productos y/o servicios, con tarjetas de crédito,
            débito y prepago a través de Webpay Plus";
        return [
            $WPOption->setCallToActionText($message)
                ->setAction($paymentController)
                ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/wpplus_small.png'))
        ];
    }

    protected function loadDefaultConfigurationWebpay()
    {
        $webpayEnviroment = $this->getWebpayEnvironment();
        /* Si existe configuración de producción se copiara */
        if (isset($webpayEnviroment) && $webpayEnviroment == Options::ENVIRONMENT_PRODUCTION) {
            $webpayActive = $this->getWebpayActive();
            $webpayActive = isset($webpayActive) ? $webpayActive : 1;
            $webpayCommerceCode = $this->getWebpayCommerceCode();
            $webpayApikey = $this->getWebpayApiKey();
            $webpayDefaultOrderStateIdAfterPayment = $this->getWebpayOrderAfterPayment();
            //si alguno de los datos falta se resetea
            if (
                StringUtils::isNotBlankOrNull($webpayCommerceCode)
                && StringUtils::isNotBlankOrNull($webpayApikey)
                && StringUtils::isNotBlankOrNull($webpayDefaultOrderStateIdAfterPayment)
            ) {
                $this->setWebpayActive($webpayActive);
                $this->setWebpayCommerceCode($webpayCommerceCode);
                $this->setWebpayApiKey($webpayApikey);
                $this->setWebpayOrderAfterPayment($webpayDefaultOrderStateIdAfterPayment);
                $this->logInfo("Configuración de WEBPAY PLUS se cargo de forma correcta =>
                    webpayCommerceCode: {$webpayCommerceCode}, webpayDefaultOrderStateIdAfterPayment:
                    {$webpayDefaultOrderStateIdAfterPayment}");
            } else {
                $this->loadDefaultWebpay();
                $this->logInfo("Configuración por defecto de WEBPAY PLUS se cargo de forma
                    correcta porque los valores de producción estan incompletos");
            }
        } else {
            $this->loadDefaultWebpay();
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
            $this->setWebpayCommerceCode(trim(Tools::getValue('form_webpay_commerce_code')));
            $this->setWebpayApiKey(trim(Tools::getValue('form_webpay_api_key')));
            $this->setWebpayEnvironment($environment);
            $this->setWebpayOrderAfterPayment((int) Tools::getValue('form_webpay_order_after_payment'));
        }
        return $theEnvironmentChanged;
    }

    protected function getWebpayCommerceCode()
    {
        return Configuration::get('WEBPAY_STOREID');
    }

    protected function getWebpayApiKey()
    {
        return Configuration::get('WEBPAY_API_KEY_SECRET');
    }

    protected function getWebpayEnvironment()
    {
        return Configuration::get('WEBPAY_ENVIRONMENT');
    }

    protected function getWebpayOrderAfterPayment()
    {
        return Configuration::get('WEBPAY_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT');
    }

    protected function getWebpayActive()
    {
        return Configuration::get('WEBPAY_ACTIVE');
    }

    protected function setWebpayApiKey($value)
    {
        Configuration::updateValue('WEBPAY_API_KEY_SECRET', $value);
    }

    protected function setWebpayCommerceCode($value)
    {
        Configuration::updateValue('WEBPAY_STOREID', $value);
    }

    protected function setWebpayEnvironment($value)
    {
        Configuration::updateValue('WEBPAY_ENVIRONMENT', $value);
    }

    protected function setWebpayOrderAfterPayment($value)
    {
        Configuration::updateValue('WEBPAY_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT', $value);
    }

    protected function setWebpayActive($value)
    {
        Configuration::updateValue('WEBPAY_ACTIVE', $value);
    }

    protected function getDefaultWebpayCommerceCode()
    {
        return WebpayPlus::DEFAULT_COMMERCE_CODE;
    }

    protected function getDefaultWebpayApiKey()
    {
        return WebpayPlus::DEFAULT_API_KEY;
    }

    protected function getDefaultWebpayEnvironment()
    {
        return Options::DEFAULT_INTEGRATION_TYPE;
    }

    protected function getDefaultWebpaykActive()
    {
        return 1;
    }

    protected function getDefaultWebpayOrderAfterPayment()
    {
        // We assume that the default state is "PREPARATION" and then set it
        // as the default order status after payment for our plugin
        return Configuration::get('PS_OS_PREPARATION');
    }

    protected function loadDefaultWebpay()
    {
        $this->setWebpayActive($this->getDefaultWebpaykActive());
        $this->setWebpayEnvironment($this->getDefaultWebpayEnvironment());
        $this->setWebpayCommerceCode($this->getDefaultWebpayCommerceCode());
        $this->setWebpayApiKey($this->getDefaultWebpayApiKey());
        $this->setWebpayOrderAfterPayment($this->getDefaultWebpayOrderAfterPayment());
    }

    protected function getWebpayOkStatus(){
        $OKStatus = Configuration::get('WEBPAY_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT');
        if ($OKStatus === '0') {
            $OKStatus = Configuration::get('PS_OS_PREPARATION');
        }
        return $OKStatus;
    }

    protected function configWebpayIsOk()
    {
        $webpayEnviroment = $this->getWebpayEnvironment();
        $webpayCommerceCode = $this->getWebpayCommerceCode();
        $webpayApikey = $this->getWebpayApiKey();
        $webpayDefaultOrderStateIdAfterPayment = $this->getWebpayOrderAfterPayment();

        if (
            StringUtils::isNotBlankOrNull($webpayEnviroment)
            && StringUtils::isNotBlankOrNull($webpayCommerceCode)
            && StringUtils::isNotBlankOrNull($webpayApikey)
            && StringUtils::isNotBlankOrNull($webpayDefaultOrderStateIdAfterPayment)
        ) {
            return true;
        }
        return false;
    }
}
