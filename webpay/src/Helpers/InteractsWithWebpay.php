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
        if ($this->getWebpayActive()!=1){
            return [];
        }
        $WPOption = new PaymentOption();
        $paymentController = $context->link->getModuleLink($base->name, 'webpaypluspayment', array(), true);

        return [ $WPOption->setCallToActionText('Permite el pago de productos y/o servicios, con tarjetas de crédito, débito y prepago a través de Webpay Plus')
            ->setAction($paymentController)
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/wpplus_small.png')) ];
    }

    protected function loadDefaultConfigurationWebpay()
    {
        $webpayEnviroment = $this->getWebpayEnvironment();
        /* Si existe configuración de producción se copiara */
        if (isset($webpayEnviroment) && $webpayEnviroment == Options::ENVIRONMENT_PRODUCTION){
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
            ){
                $this->setWebpayActive($webpayActive);
                $this->setWebpayCommerceCode($webpayCommerceCode);
                $this->setWebpayApiKey($webpayApikey);
                $this->setWebpayOrderAfterPayment($webpayDefaultOrderStateIdAfterPayment);
                $this->logWebpayPlusInstallConfigLoad($webpayCommerceCode, $webpayDefaultOrderStateIdAfterPayment);
            }
            else{
                $this->loadDefaultWebpay();
                $this->logWebpayPlusInstallConfigLoadDefaultPorIncompleta();               
            }
        }
        else{
            $this->loadDefaultWebpay();
            $this->logWebpayPlusInstallConfigLoadDefault();
        }
    }

    protected function webpayUpdateSettings(){
        $theEnvironmentChanged = false;
        if (Tools::getIsset('btn_webpay_update')) {
            if ($this->getFormWebpayEnvironment() !=  $this->getWebpayEnvironment()) {
                $theEnvironmentChanged = true;
            }
            $this->setWebpayCommerceCode($this->getFormWebpayCommerceCode());
            $this->setWebpayApiKey($this->getFormWebpayApiKey());
            $this->setWebpayEnvironment($this->getFormWebpayEnvironment());
            $this->setWebpayOrderAfterPayment($this->getFormWebpayOrderAfterPayment());
        } 
        return $theEnvironmentChanged;
    }

    protected function getFormWebpayCommerceCode(){
        return trim(Tools::getValue('form_webpay_commerce_code'));//storeID
    }

    protected function getFormWebpayApiKey(){
        return trim(Tools::getValue('form_webpay_api_key'));//apiKeySecret
    }

    protected function getFormWebpayEnvironment(){
        return Tools::getValue('form_webpay_environment');
    }

    protected function getFormWebpayOrderAfterPayment(){
        return (int)Tools::getValue('form_webpay_order_after_payment');
    }

    protected function getWebpayCommerceCode(){
        return Configuration::get('WEBPAY_STOREID');
    }

    protected function getWebpayApiKey(){
        return Configuration::get('WEBPAY_API_KEY_SECRET');
    }

    protected function getWebpayEnvironment(){
        return Configuration::get('WEBPAY_ENVIRONMENT');
    }

    protected function getWebpayOrderAfterPayment(){
        return Configuration::get('WEBPAY_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT');
    }

    protected function getWebpayActive(){
        return Configuration::get('WEBPAY_ACTIVE');
    }

    protected function setWebpayApiKey($value){
        return Configuration::updateValue('WEBPAY_API_KEY_SECRET', $value);
    }

    protected function setWebpayCommerceCode($value){
        return Configuration::updateValue('WEBPAY_STOREID', $value);
    }

    protected function setWebpayEnvironment($value){
        return Configuration::updateValue('WEBPAY_ENVIRONMENT', $value);
    }

    protected function setWebpayOrderAfterPayment($value){
        return Configuration::updateValue('WEBPAY_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT', $value);
    }

    protected function setWebpayActive($value){
        return Configuration::updateValue('WEBPAY_ACTIVE', $value);
    }

    protected function getDefaultWebpayCommerceCode(){
        return WebpayPlus::DEFAULT_COMMERCE_CODE;
    }

    protected function getDefaultWebpayApiKey(){
        return WebpayPlus::DEFAULT_API_KEY;
    }

    protected function getDefaultWebpayEnvironment(){
        return  Options::DEFAULT_INTEGRATION_TYPE;
    }

    protected function getDefaultWebpaykActive(){
        return 1;
    }

    protected function getDefaultWebpayOrderAfterPayment(){
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

    protected function configWebpayIsOk(){
        $webpayEnviroment = $this->getWebpayEnvironment();
        $webpayCommerceCode = $this->getWebpayCommerceCode();
        $webpayApikey = $this->getWebpayApiKey();
        $webpayDefaultOrderStateIdAfterPayment = $this->getWebpayOrderAfterPayment();

        if (
            StringUtils::isNotBlankOrNull($webpayEnviroment) 
            && StringUtils::isNotBlankOrNull($webpayCommerceCode)
            && StringUtils::isNotBlankOrNull($webpayApikey)
            && StringUtils::isNotBlankOrNull($webpayDefaultOrderStateIdAfterPayment)
        ){
            return true;
        }
        return false;
    }


}
