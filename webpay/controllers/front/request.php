<?php
// require_once(dirname(__FILE__).'../../../../../config/config.inc.php');
if (!defined('_PS_VERSION_')) exit;

class WebPayRequestModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        try {
        
            $config = array(
                "ENVIRONMENT" => Configuration::get('WEBPAY_ENVIRONMENT'),
                "API_KEY_SECRET" => Configuration::get('WEBPAY_API_KEY_SECRET'),
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
}
