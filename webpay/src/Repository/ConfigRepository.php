<?php

namespace PrestaShop\Module\WebpayPlus\Repository;

use Transbank\Plugin\Repository\IConfigRepository;
use Transbank\Plugin\Model\OneclickConfig;
use Transbank\Plugin\Model\WebpayplusConfig;
use Transbank\Plugin\Model\LogConfig;
use Configuration;
use ShopCore;
use Transbank\Plugin\Helpers\ArrayUtils;
use Transbank\Plugin\Helpers\StringUtils;
use Transbank\Plugin\Helpers\TbkConstans;
use Transbank\Plugin\Model\ContactDto;
use Transbank\Plugin\Model\StoreDto;
use Transbank\Plugin\Model\WebpayplusMallConfig;
use Transbank\Webpay\Options;

class ConfigRepository implements IConfigRepository {
    
    public const WEBPAY_WEBPAYPLUS = 'WEBPAY_WEBPAYPLUS';
    public const WEBPAY_WEBPAYPLUSMALL = 'WEBPAY_WEBPAYPLUSMALL';
    public const WEBPAY_ONECLICK = 'WEBPAY_ONECLICK';
    public const WEBPAY_CONTACT = 'WEBPAY_CONTACT';


    public const WEBPAY_ACTIVE = 'WEBPAY_ACTIVE';
    public const WEBPAY_PRODUCTION = 'WEBPAY_PRODUCTION';
    public const WEBPAY_COMMERCE_CODE = 'WEBPAY_STOREID';
    public const WEBPAY_API_KEY = 'WEBPAY_API_KEY_SECRET';
    public const WEBPAY_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT = 'WEBPAY_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT';
    public const OLD_WEBPAY_ENVIRONMENT = 'WEBPAY_ENVIRONMENT';

    public const ONECLICK_ACTIVE = 'ONECLICK_ACTIVE';
    public const ONECLICK_PRODUCTION = 'ONECLICK_PRODUCTION';
    public const ONECLICK_MALL_COMMERCE_CODE = 'ONECLICK_MALL_COMMERCE_CODE';
    public const ONECLICK_CHILD_COMMERCE_CODE = 'ONECLICK_CHILD_COMMERCE_CODE';
    public const ONECLICK_API_KEY = 'ONECLICK_API_KEY';
    public const ONECLICK_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT = 'ONECLICK_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT';
    public const OLD_ONECLICK_ENVIRONMENT = 'ONECLICK_ENVIRONMENT';

    public const LOG_DIR = 'LOG_DIR';

    public function getEcommerce(){
        return TbkConstans::ECOMMERCE_PRESTASHOP;
    }

    public function getTimezone(){
        return $this->getVar('PS_TIMEZONE');
    }

    /**
     * @return StoreDto[]
    */
    public function getAllStores(){
        $result = [];
        /*
 {"1":{"id_shop":"1","id_shop_group":"1","name":"PrestaShop","id_category":"2",
"theme_name":"classic","domain":"localhost:8080","domain_ssl":"localhost:8080","uri":"\/","active":"1"},"2":
{"id_shop":"2","id_shop_group":"2","name":"Prueba2","id_category":"2",
        "theme_name":"classic","domain":"localhost:8080","domain_ssl":"localhost:8080","uri":"\/prueba2\/","active":"1"}}
        */
        //PS_MULTISHOP_FEATURE_ACTIVE 
        $stores = ShopCore::getShops();
        foreach ($stores as $property => $value) {
            $result[] = new StoreDto($value["id_shop"], $value["name"]);
        }
        return $result;
    }

    /**
     * @return ContactDto
    */
    public function getContact(){
        $data = $this->getVar(static::WEBPAY_CONTACT);
        if (!StringUtils::isNotBlankOrNull($data)){
            return new ContactDto();
        }
        $json = json_decode($data, true);
        return new ContactDto($json);
    }

    /**
     * @return ContactDto
    */
    public function saveContact(ContactDto $data){
        $this->setVar(static::WEBPAY_CONTACT, json_encode($data));
        return $this->getContact();
    }

    /**
     * @return WebpayplusConfig
    */
    public function getWebpayplusConfig(){
        $data = $this->getVar(static::WEBPAY_WEBPAYPLUS);
        if (!StringUtils::isNotBlankOrNull($data)){
            $product = new WebpayplusConfig();
            $product->setOrderStatusAfterPayment($this->getDefaultOrderStatusAfterPayment());
            return $product;
        }
        $json = json_decode($data, true);
        return new WebpayplusConfig($json);
    }

    public function saveWebpayplusConfig(WebpayplusConfig $data){
        $this->setVar(static::WEBPAY_WEBPAYPLUS, json_encode($data));
        return $this->getWebpayplusConfig();
    }

    public function getWebpayplusMallConfig(){
        $data = $this->getVar(static::WEBPAY_WEBPAYPLUSMALL);
        if (!StringUtils::isNotBlankOrNull($data)){
            $product = new WebpayplusMallConfig();
            $product->setOrderStatusAfterPayment($this->getDefaultOrderStatusAfterPayment());
            return $product;
        }
        $json = json_decode($data, true);
        return new WebpayplusMallConfig($json);
    }

    public function saveWebpayplusMallConfig(WebpayplusMallConfig $data){
        $this->setVar(static::WEBPAY_WEBPAYPLUSMALL, json_encode($data));
        return $this->getWebpayplusMallConfig();
    }

    /**
     * @return OneclickConfig
    */
    public function getOneclickConfig(){
        $data = $this->getVar(static::WEBPAY_ONECLICK);
        if (!StringUtils::isNotBlankOrNull($data)){
            $product = new OneclickConfig();
            $product->setOrderStatusAfterPayment($this->getDefaultOrderStatusAfterPayment());
            return $product;
        }
        $json = json_decode($data, true);
        return new OneclickConfig($json);
    }

    public function saveOneclickConfig(OneclickConfig $data){
        $this->setVar(static::WEBPAY_ONECLICK, json_encode($data));
        return $this->getOneclickConfig();
    }

    public function getLogConfig(){
        $result = new LogConfig();
        $logDir = $this->getVar(static::LOG_DIR);
        if (isset($logDir)){
            $logDir = _PS_ROOT_DIR_.'/var/logs/Transbank_webpay';
        }
        $result->setLogDir($logDir);
        return $result;
    }

    public function saveLogConfig(LogConfig $data){
        $this->setVar(static::LOG_DIR, $data->getLogDir());
        return "";
    }

    public function getDefaultOrderStatusAfterPayment(){
        return $this->getVar('PS_OS_PREPARATION');
    }

    public function getListOrderStatusAfterPayment(){
        return [
            [
                "value" => $this->getVar('PS_OS_PAYMENT'),
                "label" => "Pago Aceptado"
            ],
            [
                "value" => $this->getVar('PS_OS_PREPARATION'),
                "label" => "Preparación en curso"
            ]
        ];
    }

    public function getPreviousWebpayplusConfigIfProduction(){
        if ($this->getVar(static::WEBPAY_PRODUCTION) === TbkConstans::FLAG_INACTIVE){
            return null;
        }
        if ($this->getVar(static::OLD_WEBPAY_ENVIRONMENT) === Options::ENVIRONMENT_PRODUCTION || $this->getVar(static::WEBPAY_PRODUCTION) === TbkConstans::FLAG_ACTIVE){
            $config = $this->getWebpayplusConfig();
            $config->setProduction(true);
            return $config;
        }
        return null;
    }

    public function getPreviousOneclickConfigIfProduction(){
        if ($this->getVar(static::ONECLICK_PRODUCTION) === TbkConstans::FLAG_INACTIVE){
            return null;
        }
        if ($this->getVar(static::OLD_ONECLICK_ENVIRONMENT) === Options::ENVIRONMENT_PRODUCTION || $this->getVar(static::ONECLICK_PRODUCTION) === TbkConstans::FLAG_ACTIVE){
            $config = $this->getOneclickConfig();
            $config->setProduction(true);
            return $config;
        }
        return null;
    }

    private function setVar($name, $value){
        Configuration::updateGlobalValue($name, $value);//updateValue
    }

    private function getVar($name){
        $value = Configuration::get($name);
        if (!StringUtils::isNotBlankOrNull($value)){
            return '';
        }
        return $value;
    }

}
