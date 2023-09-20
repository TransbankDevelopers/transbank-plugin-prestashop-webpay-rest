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

    public static function getEcommerceInfo($ecommerce){
        switch ($ecommerce) {
            case TbkConstans::ECOMMERCE_WOOCOMERCE:
              return WoocommerceInfoUtil::getEcommerceInfo();
            case TbkConstans::ECOMMERCE_PRESTASHOP:
                return PrestashopInfoUtil::getEcommerceInfo();
            default:
              return [];
        }
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
