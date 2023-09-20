<?php

namespace Transbank\Plugin\Helpers;

class WoocommerceInfoUtil
{
    public static function getVersion()
    {
        if (!class_exists('WooCommerce')) {
            exit;
        }
        global $woocommerce;
        if (!$woocommerce->version) {
            exit;
        }
        return $woocommerce->version;
    }

    public static function getPluginVersion()
    {
        $file = __DIR__.'/../../webpay-rest.php';
        $search = ' * Version:';
        $lines = file($file);
        foreach ($lines as $line) {
            if (strpos($line, $search) !== false) {
                return str_replace(' * Version:', '', $line);
            }
        }
        return null;
    }

    /**
     * Este método obtiene un resumen de información del ecommerce Woocommerce
     *
     * @return array
     */
    public static function getEcommerceInfo()
    {
        $result = [];
        $result['ecommerce'] = TbkConstans::ECOMMERCE_WOOCOMERCE;
        $result['currentEcommerceVersion'] = WoocommerceInfoUtil::getVersion();
        $result['lastEcommerceVersion'] = GitHubUtil::getLastGitHubReleaseVersion(
            TbkConstans::REPO_OFFICIAL_WOOCOMERCE);
        $result['currentPluginVersion'] = WoocommerceInfoUtil::getPluginVersion();
        $result['lastPluginVersion'] = GitHubUtil::getLastGitHubReleaseVersion(
            TbkConstans::REPO_WOOCOMERCE);
        return $result;
    }
}
