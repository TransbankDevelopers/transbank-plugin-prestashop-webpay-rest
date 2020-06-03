<?php
require_once('../../../config/config.inc.php');
if (!defined('_PS_VERSION_')) exit;

require_once('ReportPdfLog.php');
require_once('HealthCheck.php');

$config = array(
    "MODO" => Configuration::get('WEBPAY_AMBIENT'),
    "PRIVATE_KEY" => Configuration::get('WEBPAY_SECRETCODE'),
    "PUBLIC_CERT" => Configuration::get('WEBPAY_CERTIFICATE'),
    "WEBPAY_CERT" => Configuration::get('WEBPAY_CERTIFICATETRANSBANK'),
    "COMMERCE_CODE" => Configuration::get('WEBPAY_STOREID'),
    'ECOMMERCE' => 'prestashop'
);

$document = $_POST["document"];
$healthcheck = new HealthCheck($config);
$json = $healthcheck->printFullResume();

$temp = json_decode($json);
if ($document == "report"){
    unset($temp->php_info);
} else {
    $temp = array('php_info' => $temp->php_info);
}

$rl = new ReportPdfLog($document);
$rl->getReport(json_encode($temp));
