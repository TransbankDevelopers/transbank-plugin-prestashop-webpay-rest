<?php

namespace PrestaShop\Module\WebpayPlus\Utils;

use Transbank\Utils\HttpClient;

class MetricsUtil
{
    public static function sendMetrics($phpVersion, $plugin, $pluginVersion, $ecommerceVersion, $ecommerceId, $product, $enviroment, $commerceCode, $meta) {
        $client = new HttpClient();
        $headers = ['Content-Type' => 'application/json'];
        $response = $client->request('POST','https://tbk-app-y8unz.ondigitalocean.app/records/newRecord', [
            'phpVersion' => $phpVersion,
            'plugin' => $plugin,
            'pluginVersion' => $pluginVersion,
            'ecommerceVersion' => $ecommerceVersion,
            'ecommerceId' => $ecommerceId,
            'product' => $product,
            'environment' => $enviroment,
            'commerceCode' => $commerceCode,
            'metadata' => json_encode($meta)
        ], $headers);
        $result = json_decode($response->getBody(), true);
        return $result;
    }

}
