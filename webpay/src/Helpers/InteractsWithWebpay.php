<?php

namespace PrestaShop\Module\WebpayPlus\Helpers;

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use Configuration;
use Tools;
use Transbank\Webpay\WebpayPlus;
use Transbank\Webpay\Options;

/**
 * Trait InteractsWithWebpay.
 */
trait InteractsWithWebpay
{
    public function getWebpayPaymentOption($base, $context)
    {
        $WPOption = new PaymentOption();
        $paymentController = $context->link->getModuleLink($base->name, 'webpaypluspayment', array(), true);

        return $WPOption->setCallToActionText('Permite el pago de productos y/o servicios, con tarjetas de crédito, débito y prepago a través de Webpay Plus')
            ->setAction($paymentController)
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/webpay_plus_80px.svg'));
    }

    private function loadDefaultConfigurationWebpay()
    {
        /* Si ya existe alguna configuracion la copiara */
        $webpayEnviroment = $this->getWebpayEnvironment();
        $webpayCommerceCode = $this->getWebpayCommerceCode();
        $webpayApikey = $this->getWebpayApiKey();
        $webpayDefaultOrderStateIdAfterPayment = $this->getWebpayOrderAfterPayment();

        $webpayEnviroment = isset($webpayEnviroment) ? $webpayEnviroment : Options::DEFAULT_INTEGRATION_TYPE;
        if ($webpayEnviroment == Options::DEFAULT_INTEGRATION_TYPE){/* Si es el entorno de integración y no existen parámetros, cargamos los valores por defecto */
            $webpayCommerceCode = $this->getDefaultWebpayCommerceCode();
            $webpayApikey = $this->getDefaultWebpayApiKey();
            $webpayDefaultOrderStateIdAfterPayment = $this->getDefaultWebpayOrderAfterPayment();
        }

        $this->setWebpayEnvironment($webpayEnviroment);
        $this->setWebpayCommerceCode($webpayCommerceCode);
        $this->setWebpayApiKey($webpayApikey);
        $this->setWebpayOrderAfterPayment($webpayDefaultOrderStateIdAfterPayment);
    }

    public function webpayUpdateSettings(){
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

    protected function getDefaultWebpayCommerceCode(){
        return WebpayPlus::DEFAULT_COMMERCE_CODE;
    }

    protected function getDefaultWebpayApiKey(){
        return WebpayPlus::DEFAULT_API_KEY;
    }

    protected function getDefaultWebpayEnvironment(){
        return  Options::DEFAULT_INTEGRATION_TYPE;
    }

    protected function getDefaultWebpayOrderAfterPayment(){
        // We assume that the default state is "PREPARATION" and then set it
        // as the default order status after payment for our plugin
        return Configuration::get('PS_OS_PREPARATION');
    }
}
