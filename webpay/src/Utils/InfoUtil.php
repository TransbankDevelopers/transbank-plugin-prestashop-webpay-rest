<?php

namespace PrestaShop\Module\WebpayPlus\Utils;

class InfoUtil
{
    // valida version de php
    public static function getValidatephp()
    {
        if (version_compare(phpversion(), '7.4.28', '<=') and version_compare(phpversion(), '7.0.0', '>=')) {
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

    // verifica si existe la extension y cual es la version de esta
    public static function getCheckExtension($extension)
    {
        if (extension_loaded($extension)) {
            if ($extension == 'openssl') {
                $version = OPENSSL_VERSION_TEXT;
            } else {
                $version = phpversion($extension);
                if (empty($version) or $version == null or $version === false or $version == ' ' or $version == '') {
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

    //obtiene ultimas versiones
    // obtiene versiones ultima publica en github (no compatible con virtuemart) lo ideal es que el :usuario/:repo sean entregados como string
    // permite un maximo de 60 consultas por hora
    public static function getLastGitHubReleaseVersion($string)
    {
        $baseurl = 'https://api.github.com/repos/'.$string.'/releases/latest';
        $agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $baseurl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        //curl_setopt($ch,CURLOPT_HEADER, false);
        $content = curl_exec($ch);
        curl_close($ch);
        $con = json_decode($content, true);
        $version = array_key_exists('tag_name', $con) ? $con['tag_name'] : '';
        return $version;
    }

    public static function getPluginLastVersion()
    {
        return InfoUtil::getLastGitHubReleaseVersion('TransbankDevelopers/transbank-plugin-prestashop-webpay-rest');
    }

    // funcion para obtener info de cada ecommerce, si el ecommerce es incorrecto o no esta seteado se escapa como respuesta "NO APLICA"
    public static function getEcommerceInfo()
    {
        if (!defined('_PS_VERSION_')) {
            exit;
        } else {
            $actualversion = _PS_VERSION_;
            $lastversion = InfoUtil::getLastGitHubReleaseVersion('PrestaShop/PrestaShop');
            if (!file_exists(_PS_ROOT_DIR_.'/modules/webpay/config.xml')) {
                exit;
            } else {
                $xml = simplexml_load_file(_PS_ROOT_DIR_.'/modules/webpay/config.xml', null, LIBXML_NOCDATA);
                $json = json_encode($xml);
                $arr = json_decode($json, true);
                $currentplugin = $arr['version'];
            }
        }
        return [
            'ecommerce' => 'prestashop',
            'current_ecommerce_version' => $actualversion,
            'last_ecommerce_version'    => $lastversion,
            'current_plugin_version'    => $currentplugin,
            'last_plugin_version'       => InfoUtil::getPluginLastVersion(),
        ];
    }

    // lista y valida extensiones/ modulos de php en servidor ademas mostrar version
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

    // crea resumen de informacion del servidor. NO incluye a PHP info
    public static function getServerResume()
    {
        return $_SERVER['SERVER_SOFTWARE'];
    }
    
    // guarda en array informacion de funcion phpinfo
    public static function getPhpInfo()
    {
        ob_start();
        phpinfo();
        $info = ob_get_contents();
        ob_end_clean();
        $newinfo = strstr($info, '<table>');
        $newinfo = strstr($newinfo, '<h1>PHP Credits</h1>', true);
        $return = ['string' => ['content' => str_replace('</div></body></html>', '', $newinfo)]];
        return $return;
    }

    public static function getFullResume(){
        return [
            'server_resume'          => InfoUtil::getServerResume(),
            'php_extensions_status'  => InfoUtil::getExtensionsValidate(),
            'commerce_info'          => InfoUtil::getEcommerceInfo(),
            'php_info'               => InfoUtil::getPhpInfo(),
            'php'                    => InfoUtil::getValidatephp()
        ];
    }


}
