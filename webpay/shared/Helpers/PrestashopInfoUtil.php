<?php

namespace Transbank\Plugin\Helpers;

use Transbank\Plugin\Exceptions\EcommerceException;

class PrestashopInfoUtil
{
    public static function getVersion()
    {
        if (!defined('_PS_VERSION_')) {
            throw new EcommerceException('No existe instalación Prestashop');
        }
        return _PS_VERSION_;
    }

    public static function getPluginVersion()
    {
        if (!file_exists(_PS_ROOT_DIR_.'/modules/webpay/config.xml')) {
            throw new EcommerceException('No existe instalación Prestashop');
        }
        $xml = simplexml_load_file(_PS_ROOT_DIR_.'/modules/webpay/config.xml',
            null, LIBXML_NOCDATA);
        $json = json_encode($xml);
        $arr = json_decode($json, true);
        return $arr['version'];
    }

    /**
     * Este método obtiene un resumen de información del ecommerce Prestashop
     *
     * @return array
     */
    public static function getSummary()
    {
        $result = [];
        $result['ecommerce'] = TbkConstants::ECOMMERCE_PRESTASHOP;
        $result['currentEcommerceVersion'] = PrestashopInfoUtil::getVersion();
        $result['lastEcommerceVersion'] = GitHubUtil::getLastGitHubReleaseVersion(
            TbkConstants::REPO_OFFICIAL_PRESTASHOP);
        $result['currentPluginVersion'] = PrestashopInfoUtil::getPluginVersion();
        $result['lastPluginVersion'] = GitHubUtil::getLastGitHubReleaseVersion(
            TbkConstants::REPO_PRESTASHOP);
        return $result;
    }
}
