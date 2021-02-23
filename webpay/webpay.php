<?php

if (!defined('_PS_VERSION_')) {
    exit;
}


require_once(_PS_MODULE_DIR_ . 'webpay/src/Model/TransbankWebpayRestTransaction.php');
require_once('libwebpay/HealthCheck.php');
require_once('libwebpay/LogHandler.php');
require_once("libwebpay/Telemetry/PluginVersion.php");
require_once('libwebpay/Utils.php');

if (Utils::isPrestashop_1_6()) {
    require('vendor/autoload.php');
}


class WebPay extends PaymentModule {
    protected $_errors = array();
    var $healthcheck;
    var $log;
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
    
    public function __construct() {
        
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
        
        $this->loadIntegrationCertificates();
        
        $this->pluginValidation();
        try {
        $this->loadPluginConfiguration();
        
        $config = array(
            'ENVIRONMENT' => $this->environment,
            'COMMERCE_CODE' => $this->storeID,
            'API_KEY_SECRET' => $this->apiKeySecret,
            'ECOMMERCE' => 'prestashop'
        );
        
        $this->healthcheck = new HealthCheck($config);
        $this->datos_hc = json_decode($this->healthcheck->printFullResume());
        $this->log = new LogHandler();
        } catch (Exception $e) {
            print_r($e);
        }
        
    }
    
    public function install() {
        
        $this->setupPlugin();
        
        return parent::install() &&
            $this->registerHook('paymentOptions') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('displayPayment') &&
            $this->registerHook('displayPaymentReturn') &&
            $this->registerHook('displayAdminOrderLeft') && 
            $this->installWebpayTable();
        
    }

    public function hookdisplayAdminOrderLeft($params) {
        if (!$this->active)
            return;
            
        $orderId = $params['id_order'];
        $bsOrder = new Order((int)$orderId);

        if ($bsOrder->module != "webpay"){
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
                'data' => date("d-m-Y", $transactionDate)." ".date("H:i:s", $transactionDate),
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
                'data' => "$".number_format($transbankResponse['amount'], 0, ',', '.'),
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

    protected function installWebpayTable() {
        $installer = new \PrestaShop\Module\WebpayPlus\Install\Installer();
        return $installer->installWebpayOrdersTable();
    }
    
    public function uninstall() {
        if (!parent::uninstall() || !Configuration::deleteByName("webpay"))
            return false;
        return true;
    }
    
    public function hookPaymentReturn($params) {
        if (!$this->active)
            return;
        
        
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
    
    public function hookPayment($params) {
        if (!$this->active) {
            return;
        }
        Context::getContext()->smarty->assign(array(
            'logo' => $this->_path . "logo.png",
            'title' => $this->title
        ));
        return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
    }
    
    public function hookPaymentOptions($params) {
        if (!$this->active) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }
        $payment_options = [
            $this->getWebpayPaymentOption()
        ];
        return $payment_options;
    }
    
    public function checkCurrency($cart) {
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
    
    public function getWebpayPaymentOption() {
        $WPOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $paymentController = $this->context->link->getModuleLink($this->name,'payment',array(),true);
        
        return $WPOption->setCallToActionText('Pago con tarjetas de crédito o Redcompra')
            ->setAction($paymentController)
            ->setLogo('https://www.transbankdevelopers.cl/public/library/img/svg/logo_webpay_plus.svg');
    }
    
    public function getContent() {
        
        $activeShopID = (int)Context::getContext()->shop->id;
        $shopDomainSsl = Tools::getShopDomainSsl(true, true);
        $theEnvironmentChanged=false;
        
        if (Tools::getIsset('webpay_updateSettings')) {
            if (Tools::getValue('environment') !=  Configuration::get('WEBPAY_ENVIRONMENT')) {
                $theEnvironmentChanged=true;
            }
            
            
            Configuration::updateValue('WEBPAY_STOREID', trim(Tools::getValue('storeID')));
            Configuration::updateValue('WEBPAY_API_KEY_SECRET', trim(Tools::getValue('apiKeySecret')));
            Configuration::updateValue('WEBPAY_ENVIRONMENT', Tools::getValue('environment'));
            Configuration::updateValue('WEBPAY_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT', (int)Tools::getValue('webpay_default_order_state_id_after_payment'));
            $this->loadPluginConfiguration();
            $this->pluginValidation();
            
        } else {
            $this->loadPluginConfiguration();
        }
        
        $config = $this->getConfigForHealthCheck();
        
        $this->healthcheck = new HealthCheck($config);
        if ($theEnvironmentChanged) {
            $rs = $this->healthcheck->getpostinstallinfo();
        }
        
        if (Tools::getValue('environment') === 'LIVE') {
            $this->sendPluginVersion($config);
        }
        
        $this->datos_hc = json_decode($this->healthcheck->printFullResume());
        
        $ostatus = new OrderState(1);
        $statuses = $ostatus->getOrderStates(1);
        $defaultPaymentStatus = Configuration::get('WEBPAY_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT');
        $paymentAcceptedStatusId = Configuration::get('PS_OS_PAYMENT');
        $preparationStatusId = Configuration::get('PS_OS_PREPARATION');
    
        Context::getContext()->smarty->assign(
            array(
                'default_after_payment_order_state_id' => $defaultPaymentStatus,
                'paymentAcceptedStatusId' => $paymentAcceptedStatusId,
                'preparationStatusId' => $preparationStatusId,
                'payment_states' => $statuses,
                'errors' => $this->_errors,
                'post_url' => $_SERVER['REQUEST_URI'],
                'data_storeid_init' => $this->storeID_init,
                'data_apikeysecret_init' => $this->apiKeySecret_initial_value,
                'data_storeid' => $this->storeID,
                'data_apikeysecret' => $this->apiKeySecret,
                'data_environment' => $this->environment,
                'data_title' => $this->title,
                'version' => $this->version,
                'api_version' => '1.0',
                'img_icono' => "https://www.transbank.cl/public/img/LogoWebpay.png",
                'init_status' => null, //$this->datos_hc->validate_init_transaction->status->string,
                'init_error_error' => null, //(isset($this->datos_hc->validate_init_transaction->response->error)) ? $this->datos_hc->validate_init_transaction->response->error : NULL,
                'init_error_detail' => null, // (isset($this->datos_hc->validate_init_transaction->response->detail)) ? $this->datos_hc->validate_init_transaction->response->detail : NULL,
                'init_success_url' => null, //$this->datos_hc->validate_init_transaction->response->url,
                'init_success_token' => null, //$this->datos_hc->validate_init_transaction->response->token_ws,
                'php_status' =>$this->datos_hc->server_resume->php_version->status,
                'php_version' =>$this->datos_hc->server_resume->php_version->version,
                'server_version' =>$this->datos_hc->server_resume->server_version->server_software,
                'ecommerce' =>$this->datos_hc->server_resume->plugin_info->ecommerce,
                'ecommerce_version' =>$this->datos_hc->server_resume->plugin_info->ecommerce_version,
                'current_plugin_version' =>$this->datos_hc->server_resume->plugin_info->current_plugin_version,
                'last_plugin_version' =>$this->datos_hc->server_resume->plugin_info->last_plugin_version,
                'openssl_status' =>$this->datos_hc->php_extensions_status->openssl->status,
                'openssl_version' =>$this->datos_hc->php_extensions_status->openssl->version,
                'SimpleXML_status' =>$this->datos_hc->php_extensions_status->SimpleXML->status,
                'SimpleXML_version' =>$this->datos_hc->php_extensions_status->SimpleXML->version,
                'soap_status' =>$this->datos_hc->php_extensions_status->soap->status,
                'soap_version' =>$this->datos_hc->php_extensions_status->soap->version,
                'dom_status' =>$this->datos_hc->php_extensions_status->dom->status,
                'dom_version' =>$this->datos_hc->php_extensions_status->dom->version,
                'php_info' =>$this->datos_hc->php_info->string->content,
                'lockfile' => json_decode($this->log->getLockFile(),true)['status'],
                'logs' => (isset( json_decode($this->log->getLastLog(),true)['log_content'])) ?  json_decode($this->log->getLastLog(),true)['log_content'] : NULL,
                'log_file' => (isset( json_decode($this->log->getLastLog(),true)['log_file'])) ?  json_decode($this->log->getLastLog(),true)['log_file'] : NULL,
                'log_weight' => (isset( json_decode($this->log->getLastLog(),true)['log_weight'])) ?  json_decode($this->log->getLastLog(),true)['log_weight'] : NULL,
                'log_regs_lines' => (isset( json_decode($this->log->getLastLog(),true)['log_regs_lines'])) ?  json_decode($this->log->getLastLog(),true)['log_regs_lines'] : NULL,
                'log_days' => $this->log->getValidateLockFile()['max_logs_days'],
                'log_size' => $this->log->getValidateLockFile()['max_log_weight'],
                'log_dir' => json_decode($this->log->getResume(),true)['log_dir'],
                'logs_count' => json_decode($this->log->getResume(),true)['logs_count']['log_count'],
                'logs_list' => json_decode($this->log->getResume(),true)['logs_list']
            )
        );
        
        return $this->display($this->name, 'views/templates/admin/config.tpl');
    }
    
    private function pluginValidation() {
        $this->_errors = array();
    }
    /**
     * @return array
     */
    public function getConfigForHealthCheck()
    {
        $config = [
            'ENVIRONMENT' => $this->environment,
            'COMMERCE_CODE' => $this->storeID,
            'API_KEY_SECRET' => $this->apiKeySecret,
            'ECOMMERCE' => 'prestashop'
        ];
        
        return $config;
    }
    /**
     * @param array $config
     */
    public function sendPluginVersion(array $config)
    {
        $telemetryData = $this->healthcheck->getPluginInfo($this->healthcheck->ecommerce);
        (new \Transbank\Telemetry\PluginVersion())->registerVersion($config['COMMERCE_CODE'],
            $telemetryData['current_plugin_version'], $telemetryData['ecommerce_version'],
            \Transbank\Telemetry\PluginVersion::ECOMMERCE_PRESTASHOP);
    }
    
    private function adminValidation() {
        $this->_errors = array();
    }
    
    private function loadPluginConfiguration() {
        $this->storeID = Configuration::get('WEBPAY_STOREID');
        $this->apiKeySecret = Configuration::get('WEBPAY_API_KEY_SECRET');
        $this->environment = Configuration::get('WEBPAY_ENVIRONMENT');
    }
    
    private function setupPlugin() {
        $this->loadIntegrationCertificates();
        Configuration::updateValue('WEBPAY_STOREID', $this->storeID_init);
        Configuration::updateValue('WEBPAY_API_KEY_SECRET', $this->apiKeySecret_initial_value);
        Configuration::updateValue('WEBPAY_ENVIRONMENT', "TEST");
        // We assume that the default state is "PREPARATION" and then set it
        // as the default order status after payment for our plugin
        $orderInPreparationStateId = Configuration::get('PS_OS_PREPARATION');
        Configuration::updateValue('WEBPAY_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT', $orderInPreparationStateId);
    }
    
    private function loadIntegrationCertificates() {
        $this->storeID_init = \Transbank\Webpay\Options::DEFAULT_COMMERCE_CODE;
        
        $this->apiKeySecret_initial_value = \Transbank\Webpay\Options::DEFAULT_API_KEY;
        
        
        $this->environment = Configuration::get('WEBPAY_ENVIRONMENT');
    }
}
