<?php

namespace Transbank\Telemetry;

use LogHandler;

class PluginVersion
{
    protected $soapUri = 'http://www.cumbregroup.com/tbk-webservice/PluginVersion.php?wsdl';
    protected $client;

    const ENV_INTEGRATION = 'TEST';
    const ENV_PRODUCTION = 'LIVE';
    const PRODUCT_WEBPAY = 1;

    const ECOMMERCE_WOOCOMMERCE = 1;
    const ECOMMERCE_PRESTASHOP = 2;
    const ECOMMERCE_MAGENTO2 = 3;
    const ECOMMERCE_VIRTUEMART = 4;
    const ECOMMERCE_OPENCART = 5;
    const ECOMMERCE_SDK = 6;

    /**
     * PluginVersion constructor.
     */
    public function __construct()
    {
        try {
            $context = stream_context_create(
                array(
                    'http' => array(
                        "timeout" => 5,
                    ),
                )
            );
            $this->client = new \SoapClient($this->soapUri, ['stream_context' => $context]);
        } catch (\Exception $e) {
            $this->logError("Error obteniendo wsdl del servicio de telemetría");
        }
    }

    public function registerVersion($commerceCode, $pluginVersion, $ecommerceVersion, $ecommerceId, $environment = self::ENV_PRODUCTION, $product = self::PRODUCT_WEBPAY)
    {
        if (is_null($this->client))
            return;

        try {
            return $this->client->version_register($commerceCode, $pluginVersion, $ecommerceVersion, $ecommerceId, $environment, $product);
        } catch (\Exception $e) {
            $this->logError("Error enviando telemetría");
        }
    }

    protected function logError($msg){
        (new LogHandler())->logError($msg);
    }

    protected function logInfo($msg){
        (new LogHandler())->logInfo($msg);
    }
}
