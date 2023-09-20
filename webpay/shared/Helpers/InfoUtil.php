<?php

namespace Transbank\Plugin\Helpers;

class InfoUtil
{
    /**
     * Este método valida la versión de PHP.
     *
     * @return array
     */
    public static function getValidatephp()
    {
        if (version_compare(phpversion(), '7.4.28', '<=') &&
                version_compare(phpversion(), '7.0.0', '>=')) {
            return [
                'status'  => 'OK',
                'version' => phpversion(),
            ];
        } else {
            return [
                'status'  => 'WARN: El plugin no ha sido testeado con esta version',
                'version' => phpversion(),
            ];
        }
    }

    /**
     * Este método comprueba que la extensión se encuentre instalada.
     *
     * @param string $extension Es el nombre de la extensión a validar.
     * @return array
     */
    public static function getCheckExtension($extension)
    {
        if (extension_loaded($extension)) {
            if ($extension == 'openssl') {
                $version = OPENSSL_VERSION_TEXT;
            } else {
                $version = phpversion($extension);
                if (empty($version) || $version == null
                    || $version === false || $version == ' ' || $version == '') {
                    $version = 'PHP Extension Compiled. ver:'.phpversion();
                }
            }
            $status = 'OK';
            $result = [
                'status'  => $status,
                'version' => $version,
            ];
        } else {
            $result = [
                'status'  => 'Error!',
                'version' => 'No Disponible',
            ];
        }

        return $result;
    }

    /**
     * Este método obtiene las últimas versiones publicas de los ecommerces en github
     * (no compatible con virtuemart) lo ideal es que el :usuario/:repo sean entregados como string,
     * permite un maximo de 60 consultas por hora
     *
     * @param string $ecommerce Es el nombre del ecommerce a validar.
     * @return array
     */
    public static function getLastGitHubReleaseVersion($ecommerce)
    {
        $baseurl = 'https://api.github.com/repos/'.$ecommerce.'/releases/latest';
        $agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $baseurl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 2);
        $content = curl_exec($ch);
        curl_close($ch);
        $con = json_decode($content, true);
        return array_key_exists('tag_name', $con) ? $con['tag_name'] : '';
    }

    public static function getEcommerceInfo($ecommerce){
        switch ($ecommerce) {
            case TbkConstans::ECOMMERCE_WOOCOMERCE:
              return InfoUtil::getWoocomerceEcommerceInfo();
            case TbkConstans::ECOMMERCE_PRESTASHOP:
                return InfoUtil::getPrestashopEcommerceInfo();
            default:
              return [];
        }
    }

    public static function getPrestashopVersion()
    {
        if (!defined('_PS_VERSION_')) {
            exit;
        }
        return _PS_VERSION_;
    }

    public static function getPluginPrestashopVersion()
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

    public static function getWoocomerceVersion()
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

    public static function getPluginWoocomerceVersion()
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
     * Este método obtiene un resumen de información del ecommerce Prestashop
     *
     * @return array
     */
    public static function getPrestashopEcommerceInfo()
    {
        $result = [];
        $result['ecommerce'] = TbkConstans::ECOMMERCE_PRESTASHOP;
        $result['currentEcommerceVersion'] = InfoUtil::getWoocomerceVersion();
        $result['lastEcommerceVersion'] = InfoUtil::getLastGitHubReleaseVersion(
            TbkConstans::REPO_OFFICIAL_PRESTASHOP);
        $result['currentPluginVersion'] = InfoUtil::getPluginPrestashopVersion();
        $result['lastPluginVersion'] = InfoUtil::getLastGitHubReleaseVersion(
            TbkConstans::REPO_PRESTASHOP);
        return $result;
    }

    /**
     * Este método obtiene un resumen de información del ecommerce Woocommerce
     *
     * @return array
     */
    public static function getWoocomerceEcommerceInfo()
    {
        $result = [];
        $result['ecommerce'] = TbkConstans::ECOMMERCE_WOOCOMERCE;
        $result['currentEcommerceVersion'] = InfoUtil::getWoocomerceVersion();
        $result['lastEcommerceVersion'] = InfoUtil::getLastGitHubReleaseVersion(
            TbkConstans::REPO_OFFICIAL_WOOCOMERCE);
        $result['currentPluginVersion'] = InfoUtil::getPluginWoocomerceVersion();
        $result['lastPluginVersion'] = InfoUtil::getLastGitHubReleaseVersion(
            TbkConstans::REPO_WOOCOMERCE);
        return $result;
    }

    /**
     * Este método obtiene un resumen del estado de las extensiones necesarias para el plugin
     *
     * @return array
     */
    public static function getExtensionsValidate()
    {
        $result = [];
        $extensions = [
            'openssl',
            'SimpleXML',
            'soap',
            'dom',
        ];
        foreach ($extensions as $value) {
            $result[$value] = InfoUtil::getCheckExtension($value);
        }

        return $result;
    }

    /**
     * Este método obtiene informacion relevante del servidor.
     *
     * @return array
     */
    public static function getServerResume()
    {
        return $_SERVER['SERVER_SOFTWARE'];
    }
    
    /**
     * Este método obtiene el PHP info
     *
     * @return array
     */
    public static function getPhpInfo()
    {
        ob_start();
        phpinfo();
        $info = ob_get_contents();
        ob_end_clean();
        $newinfo = strstr($info, '<table>');
        $newinfo = strstr($newinfo, '<h1>PHP Credits</h1>', true);
        return ['string' => ['content' => str_replace('</div></body></html>', '', $newinfo)]];
    }

    public static function getResume($ecommerce){
        return [
            'server'          => InfoUtil::getServerResume(),
            'phpExtensions'   => InfoUtil::getExtensionsValidate(),
            'commerce'        => InfoUtil::getEcommerceInfo($ecommerce),
            'php'             => InfoUtil::getValidatephp()
        ];
    }
}
