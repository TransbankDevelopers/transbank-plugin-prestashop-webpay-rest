<?php

namespace Transbank\Plugin\Helpers;

class PrestashopInfoUtil
{
    public static function getVersion()
    {
        if (!defined('_PS_VERSION_')) {
            exit;
        }
        return _PS_VERSION_;
    }

    public static function getPluginVersion()
    {
        if (!file_exists(_PS_ROOT_DIR_.'/modules/webpay/config.xml')) {
            exit;
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
    public static function getEcommerceInfo()
    {
        $result = [];
        $result['ecommerce'] = TbkConstans::ECOMMERCE_PRESTASHOP;
        $result['currentEcommerceVersion'] = PrestashopInfoUtil::getVersion();
        $result['lastEcommerceVersion'] = GitHubUtil::getLastGitHubReleaseVersion(
            TbkConstans::REPO_OFFICIAL_PRESTASHOP);
        $result['currentPluginVersion'] = PrestashopInfoUtil::getPluginVersion();
        $result['lastPluginVersion'] = GitHubUtil::getLastGitHubReleaseVersion(
            TbkConstans::REPO_PRESTASHOP);
        return $result;
    }
}
