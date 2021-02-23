<?php

namespace Transbank\Telemetry;

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
        $this->client = new \SoapClient($this->soapUri);
    }

    public function registerVersion($commerceCode, $pluginVersion, $ecommerceVersion, $ecommerceId, $environment = self::ENV_PRODUCTION, $product = self::PRODUCT_WEBPAY)
    {
        try {
            return $this->client->version_register($commerceCode, $pluginVersion, $ecommerceVersion, $ecommerceId, $environment, $product);
        } catch (\Exception $e) {
            // Si la conexión falla, simplemente no hacer nada.
        }
    }
}
