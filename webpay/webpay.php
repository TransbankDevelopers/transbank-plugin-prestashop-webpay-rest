<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(_PS_MODULE_DIR_ . 'webpay/src/Model/TransbankWebpayRestTransaction.php');
require_once(_PS_MODULE_DIR_ . 'webpay/src/Helpers/InteractsWithWebpay.php');
require_once(_PS_MODULE_DIR_ . 'webpay/src/Helpers/InteractsWithOneclick.php');
require_once('libwebpay/HealthCheck.php');
require_once('libwebpay/LogHandler.php');
require_once("libwebpay/Telemetry/PluginVersion.php");
require_once('libwebpay/Utils.php');

if (Utils::isPrestashop_1_6()) {
    require('vendor/autoload.php');
}

use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithWebpay;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithOneclick;


class WebPay extends PaymentModule
{
    use InteractsWithWebpay;
    use InteractsWithOneclick;

    const DEBUG_MODE = true;
    protected $_errors = array();
    public $healthcheck;
    public $log;
    public $title = 'Pago con tarjetas de crédito o Redcompra';
    protected $apiKeySecret_initial_value;

    private $paymentTypeCodearray = [
        "VD" => "Venta débito",
        "VN" => "Venta normal",
        "VC" => "Venta en cuotas",
        "SI" => "3 cuotas sin interés",
        "S2" => "2 cuotas sin interés",
        "NC" => "N cuotas sin interés",
    ];

    public function __construct()
    {
        $this->name = 'webpay';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'Transbank';
        $this->need_instance = 1;
        $this->bootstrap = true;

        parent::__construct();
        $this->displayName = 'Webpay Plus';
        $this->description = 'Recibe pagos en línea con tarjetas de crédito y Redcompra en tu Prestashop a través de Webpay Plus';
        $this->controllers = array('payment', 'validate');
        $this->confirmUninstall = '¿Estás seguro/a que deseas desinstalar este módulo de pago?';

        $this->pluginValidation();
        try {
            $this->healthcheck = new HealthCheck($this->getConfigForHealthCheck());
            $this->datos_hc = json_decode($this->healthcheck->printFullResume());
            $this->log = new LogHandler();
        } catch (Exception $e) {
            print_r($e);
        }
    }

    public function install()
    {
        /* carga la configuracion por defecto al instalar el plugin */
        $this->setDebugActive("");
        $this->loadDefaultConfigurationWebpay();

        $displayOrder = $this->getDisplayOrderHookName();

        return parent::install() &&
            $this->registerHook('paymentOptions') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('displayPayment') &&
            $this->registerHook('displayPaymentReturn') &&
            $this->registerHook('displayAdminOrderLeft') &&
            $this->registerHook($displayOrder) &&
            $this->installWebpayTable();
    }

    public function hookdisplayAdminOrderLeft($params)
    {
        return $this->AdminDisplay($params);
    }
    public function hookdisplayAdminOrderTabContent($params)
    {
        return $this->AdminDisplay($params);
    }

    public function AdminDisplay($params)
    {
        if (!$this->active) {
            return;
        }

        $orderId = $params['id_order'];
        $bsOrder = new Order((int)$orderId);

        if ($bsOrder->module != "webpay") {
            return;
        }

        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . TransbankWebpayRestTransaction::TABLE_NAME . ' WHERE `order_id` = "' . $orderId . '" AND status = ' . TransbankWebpayRestTransaction::STATUS_APPROVED;
        $transaction = \Db::getInstance()->getRow($sql);
        $webpayTransaction = new TransbankWebpayRestTransaction($transaction['id']);
        $transbankResponse = json_decode($webpayTransaction->transbank_response, true);
        $transactionDate = strtotime($transbankResponse['transactionDate']);
        $paymentTypeCode = $transbankResponse['paymentTypeCode'];

        if ($paymentTypeCode == "VD") {
            $paymentType = "Débito";
        } elseif ($paymentTypeCode == "VP") {
            $paymentType = "Prepago";
        } else {
            $paymentType = "Crédito";
        }
        if (in_array($paymentTypeCode, ["SI", "S2", "NC", "VC"])) {
            $tipo_cuotas = $this->paymentTypeCodearray[$paymentTypeCode];
        } else {
            $tipo_cuotas = "Sin cuotas";
        }

        $details = array(
            array(
                'desc' => $this->l('Fecha de Transacción'),
                'data' => date("d-m-Y", $transactionDate) . " " . date("H:i:s", $transactionDate),
            ),
            array(
                'desc' => $this->l('Tipo de Tarjeta'),
                'data' => $paymentType,
            ),
            array(
                'desc' => $this->l('Tipo de Cuotas'),
                'data' => $tipo_cuotas,
            ),
            array(
                'desc' => $this->l('Cantidad de Cuotas'),
                'data' => $transbankResponse['installmentsNumber'],
            ),
            array(
                'desc' => $this->l('Tarjeta'),
                'data' => $transbankResponse['cardDetail']['card_number'],
            ),
            array(
                'desc' => $this->l('Total Cobrado'),
                'data' => "$" . number_format($transbankResponse['amount'], 0, ',', '.'),
            ),
            array(
                'desc' => $this->l('Código de Autorización'),
                'data' => $transbankResponse['authorizationCode'],
            ),
            array(
                'desc' => $this->l('Respuesta del Banco'),
                'data' => $transbankResponse['status'],
            ),
            array(
                'desc' => $this->l('Orden de Compra'),
                'data' => $transbankResponse['buyOrder'],
            ),
            array(
                'desc' => $this->l('Código de Resultado'),
                'data' => $webpayTransaction->response_code,
            ),
            array(
                'desc' => $this->l('Token'),
                'data' => $webpayTransaction->token,
            ),
        );

        $this->context->smarty->assign($this->name, array(
            '_path' => $this->_path,
            'title' => $this->displayName,
            'details' => $details,
        ));

        return $this->display(__FILE__, 'views/templates/admin/admin_order.tpl');
    }

    protected function installWebpayTable()
    {
        $installer = new \PrestaShop\Module\WebpayPlus\Install\Installer();
        return $installer->installWebpayOrdersTable();
    }

    public function uninstall()
    {
        if (!parent::uninstall() || !Configuration::deleteByName("webpay")) {
            return false;
        }
        return true;
    }

    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }

        $nameOrderRef = isset($params['order']) ? 'order' : 'objOrder';
        $orderId = $params[$nameOrderRef]->id;

        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . TransbankWebpayRestTransaction::TABLE_NAME . ' WHERE `order_id` = "' . $orderId . '" AND status = ' . TransbankWebpayRestTransaction::STATUS_APPROVED;
        $transaction = \Db::getInstance()->getRow($sql);
        $webpayTransaction = new TransbankWebpayRestTransaction($transaction['id']);

        if (!$webpayTransaction) {
            (new LogHandler())->logError('Showing confirmation page, but there is no webpayTransaction object, so we cant find an approved transaction for this order.');
        }

        $transbankResponse = json_decode($webpayTransaction->transbank_response, true);
        $transactionDate = strtotime($transbankResponse['transactionDate']);
        $paymentTypeCode = $transbankResponse['paymentTypeCode'];
        if ($paymentTypeCode == "VD") {
            $paymentType = "Débito";
        } elseif ($paymentTypeCode == "VP") {
            $paymentType = "Prepago";
        } else {
            $paymentType = "Crédito";
        }
        if (in_array($paymentTypeCode, ["SI", "S2", "NC", "VC"])) {
            $tipo_cuotas = $this->paymentTypeCodearray[$paymentTypeCode];
        } else {
            $tipo_cuotas = "Sin cuotas";
        }


        $this->smarty->assign(array(
            'shop_name' => $this->context->shop->name,
            'total_to_pay' =>  $params[$nameOrderRef]->getOrdersTotalPaid(),
            'status' => 'ok',
            'id_order' => $orderId,
            'WEBPAY_RESULT_DESC' => "Transacción aprobada",
            'WEBPAY_VOUCHER_NROTARJETA' => $transbankResponse['cardDetail']['card_number'],
            'WEBPAY_VOUCHER_TXDATE_FECHA' => date("d-m-Y", $transactionDate),
            'WEBPAY_VOUCHER_TXDATE_HORA' => date("H:i:s", $transactionDate),
            'WEBPAY_VOUCHER_TOTALPAGO' => number_format($transbankResponse['amount'], 0, ',', '.'),
            'WEBPAY_VOUCHER_ORDENCOMPRA' => $transbankResponse['buyOrder'],
            'WEBPAY_VOUCHER_AUTCODE' => $transbankResponse['authorizationCode'],
            'WEBPAY_VOUCHER_TIPOCUOTAS' => $tipo_cuotas,
            'WEBPAY_VOUCHER_TIPOPAGO' => $paymentType,
            'WEBPAY_VOUCHER_NROCUOTAS' => $transbankResponse['installmentsNumber'] ? $transbankResponse['installmentsNumber'] : "0",
            'WEBPAY_VOUCHER_AMOUNT_CUOTAS' => $transbankResponse['installmentsAmount'] ? number_format($transbankResponse['installmentsAmount'], 0, ',', '.') : "0",
            'WEBPAY_RESULT_CODE' => $webpayTransaction->response_code
        ));

        if (isset($params[$nameOrderRef]->reference) && !empty($params[$nameOrderRef]->reference)) {
            $this->smarty->assign('reference', $params[$nameOrderRef]->reference);
        }

        return $this->display(__FILE__, 'views/templates/hook/payment_return.tpl');
    }

    public function hookPayment($params)
    {
        if (!$this->active) {
            return;
        }
        Context::getContext()->smarty->assign(array(
            'logo' => $this->_path . "logo.png",
            'title' => $this->title
        ));
        return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
    }

    public function hookPaymentOptions($params)
    {
        if($this->getDebugActive()==1){
            $this->logInfo('*****************************************************');
            $this->logInfo('A.1. Mostrando medios de pago Webpay Plus');
            $this->logInfo(json_encode($params['cart']));
            $this->logInfo('-----------------------------------------------------');
        }

        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }
        $payment_options = [
            $this->getWebpayPaymentOption($this, $this->context)
        ];
        return $payment_options;
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);
        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getContent()
    {
        $activeShopID = (int)Context::getContext()->shop->id;
        $shopDomainSsl = Tools::getShopDomainSsl(true, true);

        $webpayEnvironmentChanged = $this->updateSettings();
        $config = $this->getConfigForHealthCheck();

        $this->healthcheck = new HealthCheck($config);
        if ($webpayEnvironmentChanged) {
            $rs = $this->healthcheck->getpostinstallinfo();
        }

        if ($this->getFormWebpayEnvironment() === 'LIVE') {
            $this->sendPluginVersion($config);
        }

        $this->datos_hc = json_decode($this->healthcheck->printFullResume());

        $ostatus = new OrderState(1);
        $statuses = $ostatus->getOrderStates(1);

        Context::getContext()->smarty->assign(
            array(
                'paymentAcceptedStatusId' => Configuration::get('PS_OS_PAYMENT'),
                'preparationStatusId' => Configuration::get('PS_OS_PREPARATION'),
                'payment_states' => $statuses,
                'errors' => $this->_errors,
                'post_url' => $_SERVER['REQUEST_URI'],

                //webpay_updateSettings
                'data_webpay_commerce_code_default' => $this->getDefaultWebpayCommerceCode(),//data_storeid_init
                'data_webpay_apikey_default' => $this->getDefaultWebpayApiKey(),//data_apikeysecret_init
                'data_webpay_commerce_code' => $this->getWebpayCommerceCode(),//data_storeid
                'data_webpay_apikey' => $this->getWebpayApiKey(),//data_apikeysecret
                'data_webpay_environment' => $this->getWebpayEnvironment(),//data_environment
                'data_webpay_order_after_payment' => $this->getWebpayOrderAfterPayment(),//data_order_after_payment

                'data_oneclick_mall_commerce_code_default' => $this->getDefaultOneclickMallCommerceCode(),
                'data_oneclick_child_commerce_code_default' => $this->getDefaultOneclickChildCommerceCode(),
                'data_oneclick_apikey_default' => $this->getDefaultOneclickApiKey(),
                'data_oneclick_mall_commerce_code' => $this->getOneclickMallCommerceCode(),
                'data_oneclick_child_commerce_code' => $this->getOneclickChildCommerceCode(),
                'data_oneclick_apikey' => $this->getOneclickApiKey(),
                'data_oneclick_environment' => $this->getOneclickEnvironment(),
                'data_oneclick_order_after_payment' => $this->getOneclickOrderAfterPayment(),
                'img_oneclick' =>  _PS_MODULE_DIR_.'/webpay/views/img/oneclick.png',

                'data_debug_active' => $this->getDebugActive(),
                'data_title' => $this->title,
                'version' => $this->version,
                'api_version' => '1.0',
                'img_icono' => "https://www.transbank.cl/public/img/LogoWebpay.png",
                'init_status' => null, //$this->datos_hc->validate_init_transaction->status->string,
                'init_error_error' => null, //(isset($this->datos_hc->validate_init_transaction->response->error)) ? $this->datos_hc->validate_init_transaction->response->error : NULL,
                'init_error_detail' => null, // (isset($this->datos_hc->validate_init_transaction->response->detail)) ? $this->datos_hc->validate_init_transaction->response->detail : NULL,
                'init_success_url' => null, //$this->datos_hc->validate_init_transaction->response->url,
                'init_success_token' => null, //$this->datos_hc->validate_init_transaction->response->token_ws,
                'php_status' => $this->datos_hc->server_resume->php_version->status,
                'php_version' => $this->datos_hc->server_resume->php_version->version,
                'server_version' => $this->datos_hc->server_resume->server_version->server_software,
                'ecommerce' => $this->datos_hc->server_resume->plugin_info->ecommerce,
                'ecommerce_version' => $this->datos_hc->server_resume->plugin_info->ecommerce_version,
                'current_plugin_version' => $this->datos_hc->server_resume->plugin_info->current_plugin_version,
                'last_plugin_version' => $this->datos_hc->server_resume->plugin_info->last_plugin_version,
                'openssl_status' => $this->datos_hc->php_extensions_status->openssl->status,
                'openssl_version' => $this->datos_hc->php_extensions_status->openssl->version,
                'SimpleXML_status' => $this->datos_hc->php_extensions_status->SimpleXML->status,
                'SimpleXML_version' => $this->datos_hc->php_extensions_status->SimpleXML->version,
                'soap_status' => $this->datos_hc->php_extensions_status->soap->status,
                'soap_version' => $this->datos_hc->php_extensions_status->soap->version,
                'dom_status' => $this->datos_hc->php_extensions_status->dom->status,
                'dom_version' => $this->datos_hc->php_extensions_status->dom->version,
                'php_info' => $this->datos_hc->php_info->string->content,
                'lockfile' => json_decode($this->log->getLockFile(), true)['status'],
                'logs' => (isset(json_decode($this->log->getLastLog(), true)['log_content'])) ?  json_decode($this->log->getLastLog(), true)['log_content'] : null,
                'log_file' => (isset(json_decode($this->log->getLastLog(), true)['log_file'])) ?  json_decode($this->log->getLastLog(), true)['log_file'] : null,
                'log_weight' => (isset(json_decode($this->log->getLastLog(), true)['log_weight'])) ?  json_decode($this->log->getLastLog(), true)['log_weight'] : null,
                'log_regs_lines' => (isset(json_decode($this->log->getLastLog(), true)['log_regs_lines'])) ?  json_decode($this->log->getLastLog(), true)['log_regs_lines'] : null,
                'log_days' => $this->log->getValidateLockFile()['max_logs_days'],
                'log_size' => $this->log->getValidateLockFile()['max_log_weight'],
                'log_dir' => json_decode($this->log->getResume(), true)['log_dir'],
                'logs_count' => json_decode($this->log->getResume(), true)['logs_count']['log_count'],
                'logs_list' => json_decode($this->log->getResume(), true)['logs_list'],

                'view_base' => _PS_MODULE_DIR_.'/webpay/views/templates',
            )
        );

        return $this->display($this->name, 'views/templates/admin/config.tpl');
    }

    private function pluginValidation()
    {
        $this->_errors = array();
    }
    /**
     * @return array
     */
    public function getConfigForHealthCheck()
    {
        $config = array(
            'ENVIRONMENT' => $this->getWebpayEnvironment(),
            'COMMERCE_CODE' => $this->getWebpayCommerceCode(),
            'API_KEY_SECRET' => $this->getWebpayApiKey(),
            'ECOMMERCE' => 'prestashop'
        );
        return $config;
    }
    /**
     * @param array $config
     */
    public function sendPluginVersion(array $config)
    {
        $telemetryData = $this->healthcheck->getPluginInfo($this->healthcheck->ecommerce);
        (new \Transbank\Telemetry\PluginVersion())->registerVersion(
            $config['COMMERCE_CODE'],
            $telemetryData['current_plugin_version'],
            $telemetryData['ecommerce_version'],
            \Transbank\Telemetry\PluginVersion::ECOMMERCE_PRESTASHOP
        );
    }
    /**
     * @return string
     */
    public function getDisplayOrderHookName()
    {
        $displayOrder = 'displayAdminOrderLeft';
        if (version_compare(_PS_VERSION_, '1.7.7.0', '>=')) {
            $displayOrder = 'displayAdminOrderTabContent';
        }

        return $displayOrder;
    }

    private function adminValidation()
    {
        $this->_errors = array();
    }



    protected function logError($msg){
        (new LogHandler())->logError($msg);
    }

    protected function logInfo($msg){
        (new LogHandler())->logInfo($msg);
    }

    protected function getDebugActive(){
        return Configuration::get('DEBUG_ACTIVE');
    }

    protected function setDebugActive($value){
        return Configuration::updateValue('DEBUG_ACTIVE', $value);
    }

    protected function getFormDebugActive(){
        return trim(Tools::getValue('form_debug_active'));
    }

    public function updateSettings(){
        $this->webpayUpdateSettings();
        $this->oneclickUpdateSettings();
        if (Tools::getIsset('btn_webpay_update')) {
            $this->setDebugActive($this->getFormDebugActive());
            return $this->getFormWebpayEnvironment() !=  $this->getWebpayEnvironment();
        }
        return false;
    }


}
