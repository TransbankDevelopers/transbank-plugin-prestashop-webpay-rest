<?php
require_once(dirname(__FILE__).'../../../../../config/config.inc.php');
if (!defined('_PS_VERSION_')) exit;

require_once(_PS_MODULE_DIR_.'webpay/libwebpay/HealthCheck.php');
require_once(_PS_MODULE_DIR_.'webpay/libwebpay/LogHandler.php');

$type = $_POST['type'];

if ($type == 'checkInit') {
    try {

        $config = array(
            "MODO" => Configuration::get('WEBPAY_AMBIENT'),
            "API_KEY" => Configuration::get('WEBPAY_APIKEY'),
            "COMMERCE_CODE" => Configuration::get('WEBPAY_STOREID'),
            'ECOMMERCE' => 'prestashop'
        );

        $healthcheck = new HealthCheck($config);
        $response = $healthcheck->getInitTransaction();

        $log = new LogHandler();
        $logHandler = json_decode($log->getResume(), true);

        echo json_encode(['success' => true, 'msg' => json_decode($response), 'log' => $logHandler]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    }
}
