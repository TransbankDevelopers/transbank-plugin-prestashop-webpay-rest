<?php

namespace Transbank\Plugin\Helpers;

use Exception;
use Throwable;
use Transbank\Plugin\Helpers\StringUtils;
use Transbank\Webpay\Options;


/**
 * Esta clase tiene el propósito de identificar errores retornados por el api de Transbank
 * estos mensajes retornados podrían ser los siguientes:
 * status 401 => "error_message": "Not Authorized"
 * status 422 => "error_message": "Api mismatch error, required version is 1.2"
 * status 422 => "error_message": "Invalid value for parameter: token"
 * status 422 => "error_message":
 *            "The transactions's date has passed max time (7 days) to recover the status"
 * status 422 => "error_message": "Invalid value for parameter: transaction not found"
 *
 */
class TbkValidateUtil {

    /**
     * Este método recibe una excepción, y valida que sea del tipo:
     * status 422 => "error_message": "Api mismatch error, required version is 1.2"
     *
     * @param Throwable $e
     */
    public static function isApiMismatchError(Throwable $e)
    {
        $error = $e->getMessage();
        $position = strpos($error, 'Api mismatch error');
        return $position !== false;
    }

    /**
     * Este método recibe una excepción por uso de versión de api incorrecto
     * y extrae la versión del api usado.
     * El mensaje recibido tiene este formato 'Api mismatch error, required version is 1.3'
     *
     * @param Throwable $e
     */
    public static function getVersionFromApiMismatchError(Throwable $e)
    {
        if (TbkValidateUtil::isApiMismatchError($e)){
            $pattern = '/\d+\.\d+/';
            if (preg_match($pattern, $e->getMessage(), $matches)) {
                return $matches[0];
            }
        }
        return null;
    }

    /**
     * Este método recibe una excepción, y valida que sea del tipo: 
     * status 401 => "error_message": "Not Authorized"
     *
     * @param Throwable $e
     */
    public static function isNotAuthorizedError(Throwable $e)
    {
        $error = $e->getMessage();
        $position = strpos($error, 'Not Authorized');
        return $position !== false;
    }

    /**
     * Este método recibe una excepción, y valida que sea del tipo:
     * status 422 => "error_message":
     * "The transactions's date has passed max time (7 days) to recover the status"
     *
     * @param Throwable $e
     */
    public static function isMaxTimeError(Throwable $e)
    {
        $error = $e->getMessage();
        $position = strpos($error, 'date has passed max time');
        return $position !== false;
    }

    public static function validateCommerceCode($commerceCode){
        if (!StringUtils::hasLength($commerceCode, 12)){
            return "El código de comercio no tiene 12 digitos";
        }
        if (!strpos($commerceCode, '5970') === 0) {
            return "El código de comercio no comienza con 5970";
        }
        return null;
    }

    public static function checkAccessibleTbkUrl($url){
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return $httpCode === 401 ? null : "No se consiguio la respuesta esperada";
        } catch (Exception $e) {
            return "Ocurrio un error validando el acceso a '$url', ERROR: {$e->getMessage()}";
        }
    }

    public static function checkAccessibleProductionUrl(){
        return TbkValidateUtil::checkAccessibleTbkUrl(
            Options::BASE_URL_PRODUCTION.'rswebpaytransaction/api/');
    }

    public static function checkAccessibleIntegrationUrl(){
        return TbkValidateUtil::checkAccessibleTbkUrl(
            Options::BASE_URL_INTEGRATION.'rswebpaytransaction/api/');
    }

    public static function proccessArrayErrors($errors){
        $ok = true;
        $errors["ok"] = null;
        foreach ($errors as $attribute => $value) {
            if (is_array($value)){
                if ($value["ok"] === false){
                    $ok = false;
                    break;
                }
            }
            elseif (!is_null($value)) {
                $ok = false;
                break;
            }
        }
        $errors["ok"] = $ok;
        return $errors;
    }

}
