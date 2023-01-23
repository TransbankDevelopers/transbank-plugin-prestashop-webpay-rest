<?php

use PrestaShop\Module\WebpayPlus\Utils\MetricsUtil;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithCommon;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithWebpay;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithOneclick;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithWebpayDb;
use PrestaShop\Module\WebpayPlus\Helpers\InteractsWithTabs;
use PrestaShop\Module\WebpayPlus\Utils\HealthCheck;
use PrestaShop\Module\WebpayPlus\Utils\LogHandler;
use PrestaShop\Module\WebpayPlus\Telemetry\PluginVersion;
use PrestaShop\Module\WebpayPlus\Model\TransbankWebpayRestTransaction;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__.'/vendor/autoload.php';

class WebPay extends PaymentModule
{
    use InteractsWithWebpay;
    use InteractsWithOneclick;
    use InteractsWithCommon;
    use InteractsWithWebpayDb;
    use InteractsWithTabs;

    //const DEBUG_MODE = true;
    protected $_errors = array();
    public $log;
    public $title = 'Pago con tarjetas de crédito o Redcompra';

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
        $this->addTabs($this);
        parent::__construct();
        $this->displayName = 'Webpay Plus';
        $this->description = 'Recibe pagos en línea con tarjetas de crédito y Redcompra en tu Prestashop a través de Webpay Plus y Oneclick';
        $this->confirmUninstall = '¿Estás seguro/a que deseas desinstalar este módulo de pago?';
        $this->ps_versions_compliancy = array('min' => '1.7.6.0', 'max' => _PS_VERSION_);
        $this->pluginValidation();
        try {
            $this->log = new LogHandler();
        } catch (Exception $e) {
            print_r($e);
        }
    }

    public function uninstall()
    {
        $this->uninstallTab();
        if (!parent::uninstall() || !Configuration::deleteByName("webpay")) {
            return false;
        }
        return true;
    }

    public function install()
    {
        /* carga la configuracion por defecto al instalar el plugin */
        $this->setDebugActive("");
        $this->loadDefaultConfigurationWebpay();
        $this->loadDefaultConfigurationOneclick();

        $result = parent::install();
        /* Se instalan las tablas, si falla se sigue con la instalación */
        $this->installWebpayTable();
        $this->installOneclickTable();
        $this->installTab();

        /* Si algo falla aqui se muestran los errores */
        return  $result &&
            $this->registerHook('paymentOptions') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('displayPayment') &&
            $this->registerHook('displayPaymentReturn') &&
            $this->registerHook('displayAdminOrderLeft') &&
            $this->registerHook($this->getDisplayOrderHookName());
    }

    protected function installWebpayTable()
    {
        $installer = new \PrestaShop\Module\WebpayPlus\Install\WebpayPlusInstaller();
        return $installer->installWebpayOrdersTable();
    }

    private function installOneclickTable()
    {
        $installer = new \PrestaShop\Module\WebpayPlus\Install\OneclickInstaller();
        return $installer->installInscriptionsTable();
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

        $tx = $this->getFormatTransbankWebpayRestTransactionByOrderId($orderId);
        $details = array(
            array(
                'desc' => $this->l('Fecha de Transacción'),
                'data' => $tx['transactionDate'] . " " . $tx['transactionHour'],
            ),
            array(
                'desc' => $this->l('Tipo de Tarjeta'),
                'data' => $tx['paymentType'],
            ),
            array(
                'desc' => $this->l('Tipo de Cuotas'),
                'data' => $tx['installmentType'],
            ),
            array(
                'desc' => $this->l('Cantidad de Cuotas'),
                'data' => $tx['installmentsNumber'],
            ),
            array(
                'desc' => $this->l('Tarjeta'),
                'data' => $tx['cardNumber'],
            ),
            array(
                'desc' => $this->l('Total Cobrado'),
                'data' => "$" . $tx['totalPago'],
            ),
            array(
                'desc' => $this->l('Código de Autorización'),
                'data' => $tx['authorizationCode'],
            ),
            array(
                'desc' => $this->l('Respuesta del Banco'),
                'data' => $tx['status'],
            ),
            array(
                'desc' => $this->l('Orden de Compra'),
                'data' => $tx['buyOrder'],
            ),
            array(
                'desc' => $this->l('Código de Resultado'),
                'data' => $tx['responseCode'],
            ),
            array(
                'desc' => $this->l('Token'),
                'data' => $tx['token'],
            ),
        );

        $this->context->smarty->assign($this->name, array(
            '_path' => $this->_path,
            'title' => $this->displayName,
            'details' => $details,
        ));
        return $this->display(__FILE__, 'views/templates/admin/admin_order.tpl');
    }

    private function getFormatTransbankWebpayRestTransactionByOrderId($orderId){
        $webpayTransaction = $this->getTransbankWebpayRestTransactionByOrderId($orderId);
        if (!$webpayTransaction) {
            $this->logError('Showing confirmation page, but there is no webpayTransaction object, so we cant find an approved transaction for this order.');
        }
        
        if($this->getDebugActive()==1){
            $this->logInfo('D.3. TransbankWebpayRestTransaction obtenida');
            $this->logInfo(isset($webpayTransaction) ? $webpayTransaction->transbank_response : 'No se encontro el registro');
        }

        $transbankResponse = json_decode($webpayTransaction->transbank_response, true);
        $transactionDate = strtotime($transbankResponse['transactionDate']);
        $token = $webpayTransaction->token;

        if ($webpayTransaction->product == TransbankWebpayRestTransaction::PRODUCT_WEBPAY_PLUS){
            $amount = number_format($transbankResponse['amount'], 0, ',', '.');
            $paymentTypeCode = $transbankResponse['paymentTypeCode'];
            $cardNumber = $transbankResponse['cardDetail']['cardNumber'];
            $installmentsNumber = $transbankResponse['installmentsNumber'] ? $transbankResponse['installmentsNumber'] : "0";
            $authorizationCode = $transbankResponse['authorizationCode'];
            $buyOrder = $transbankResponse['buyOrder'];
            $installmentsAmount = $transbankResponse['installmentsAmount'] ? number_format($transbankResponse['installmentsAmount'], 0, ',', '.') : "0";
            $responseCode = $transbankResponse['response_code'];
            $status = $transbankResponse['status'];
        }
        else{
            $cardNumber = $transbankResponse['cardNumber'];

            $detail = $transbankResponse['details'][0];/* Se asume que el valor se extrae del primer elemento del details */
            $amount = number_format($detail['amount'], 0, ',', '.');
            $paymentTypeCode = $detail['paymentTypeCode'];
            $installmentsNumber = $detail['installmentsNumber'] ? $detail['installmentsNumber'] : "0";
            $authorizationCode = $detail['authorizationCode'];
            $buyOrder = $detail['buyOrder'];
            $installmentsAmount = $detail['installmentsAmount'] ? number_format($detail['installmentsAmount'], 0, ',', '.') : "0";
            $responseCode = $detail['responseCode'];
            $status = $detail['status'];
        }
        
        if ($paymentTypeCode == "VD") {
            $paymentType = "Débito";
        } elseif ($paymentTypeCode == "VP") {
            $paymentType = "Prepago";
        } else {
            $paymentType = "Crédito";
        }
        if (in_array($paymentTypeCode, ["SI", "S2", "NC", "VC"])) {
            $installmentType = $this->paymentTypeCodearray[$paymentTypeCode];
        } else {
            $installmentType = "Sin cuotas";
        }

        return [
            'cardNumber' => $cardNumber,
            'paymentType' => $paymentType,
            'totalPago' => $amount,
            'transactionDate' => date("d-m-Y", $transactionDate),
            'transactionHour' => date("H:i:s", $transactionDate),
            'buyOrder' => $buyOrder,
            'authorizationCode' => $authorizationCode,
            'installmentType' => $installmentType,
            'installmentsNumber' => $installmentsNumber,
            'installmentsAmount' => $installmentsAmount,
            'responseCode' => $responseCode,
            'status' => $status,
            'token' => $token
        ];
    }

    public function hookPaymentReturn($params)
    {
        if($this->getDebugActive()==1){
            $this->logInfo('D.1. Retornando (hookPaymentReturn)');
        }
        if (!$this->active) {
            return;
        }

        $nameOrderRef = isset($params['order']) ? 'order' : 'objOrder';
        $orderId = $params[$nameOrderRef]->id;
        
        if($this->getDebugActive()==1){
            $this->logInfo('D.2. Obteniendo TransbankWebpayRestTransaction desde la BD');
            $this->logInfo('nameOrderRef: '.$nameOrderRef.', orderId: '.$orderId);
        }
        
        $tx = $this->getFormatTransbankWebpayRestTransactionByOrderId($orderId);

        $this->smarty->assign(array(
            'shop_name' => $this->context->shop->name,
            'total_to_pay' =>  $params[$nameOrderRef]->getOrdersTotalPaid(),
            'status' => 'ok',
            'id_order' => $orderId,
            'WEBPAY_RESULT_DESC' => "Transacción aprobada",
            'WEBPAY_VOUCHER_NROTARJETA' => $tx['cardNumber'],
            'WEBPAY_VOUCHER_TXDATE_FECHA' => $tx['transactionDate'],
            'WEBPAY_VOUCHER_TXDATE_HORA' => $tx['transactionHour'],
            'WEBPAY_VOUCHER_TOTALPAGO' => $tx['totalPago'],
            'WEBPAY_VOUCHER_ORDENCOMPRA' => $tx['buyOrder'],
            'WEBPAY_VOUCHER_AUTCODE' => $tx['authorizationCode'],
            'WEBPAY_VOUCHER_TIPOCUOTAS' => $tx['installmentType'],
            'WEBPAY_VOUCHER_TIPOPAGO' => $tx['paymentType'],
            'WEBPAY_VOUCHER_NROCUOTAS' => $tx['installmentsNumber'],
            'WEBPAY_VOUCHER_AMOUNT_CUOTAS' => $tx['installmentsAmount'],
            'WEBPAY_RESULT_CODE' => $tx['responseCode'],
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
            'logo' => \Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/oneclick_80px.svg'),
            'title' => $this->title
        ));
        return $this->display(__FILE__, 'views/templates/hook/payment.tpl');
    }

    /*
        Muestra la opciones de pago disponibles
    */
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
        /*Agregamos la opcion de pago Webpay Plus */
        $payment_options = [
            $this->getWebpayPaymentOption($this, $this->context)
        ];
        /*Agregamos la opcion de pago Webpay Oneclick */
        array_push($payment_options, ...$this->getGroupOneclickPaymentOption($this, $this->context));
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
        $route = SymfonyContainer::getInstance()->get('router')->generate('ps_controller_webpay_configure');
        Tools::redirectAdmin($route);

        /*
        $activeShopID = (int)Context::getContext()->shop->id;
        $shopDomainSsl = Tools::getShopDomainSsl(true, true);

        $this->updateSettings();
        $healthcheck = $this->createHealthCheck();

        if ($this->getFormWebpayEnvironment() === 'LIVE') {
            $this->sendMetrics();
        }

        $this->datos_hc = json_decode($healthcheck->printFullResume());

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

        return $this->display($this->name, 'views/templates/admin/config.tpl');*/
    }

    private function pluginValidation()
    {
        $this->_errors = array();
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
        if (isset($this->log))
            $this->log->logError($msg);
    }

    protected function logInfo($msg){
        if (isset($this->log))
            $this->log->logInfo($msg);
    }

    public function updateSettings(){
        $this->oneclickUpdateSettings();
        $this->commonUpdateSettings();
        return $this->webpayUpdateSettings();
    }
    
    public function sendPluginVersion($healthcheck)
    {
        $config = $healthcheck->getConfig();
        $telemetryData = $healthcheck->getPluginInfo($healthcheck->ecommerce);
        (new PluginVersion())->registerVersion(
            $config['COMMERCE_CODE'],
            $telemetryData['current_plugin_version'],
            $telemetryData['ecommerce_version'],
            PluginVersion::ECOMMERCE_PRESTASHOP
        );
    }

    public function createHealthCheck(){
        return new HealthCheck(array(
            'ENVIRONMENT' => $this->getWebpayEnvironment(),
            'COMMERCE_CODE' => $this->getWebpayCommerceCode(),
            'API_KEY_SECRET' => $this->getWebpayApiKey(),
            'ECOMMERCE' => 'prestashop'
        ));
    }

    public function sendMetrics() {
        $healthcheck = $this->createHealthCheck();
        $datos_hc = json_decode($healthcheck->printFullResume());
        //$shops = Shop::getShops();
        return MetricsUtil::sendMetrics(
            $datos_hc->server_resume->php_version->version,//$phpVersion, 
            'prestashop',//$plugin, 
            $datos_hc->server_resume->plugin_info->current_plugin_version,//$pluginVersion, 
            $datos_hc->server_resume->plugin_info->ecommerce_version,//$ecommerceVersion, 
            1,//$ecommerceId, 
            'webpay',//$product, 
            $this->getWebpayEnvironment(), 
            $this->getWebpayCommerceCode(),//$commerceCode
            []
        );
    }
}


