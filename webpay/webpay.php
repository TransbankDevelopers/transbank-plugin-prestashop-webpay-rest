<?php

if (!defined('_PS_VERSION_'))
    exit;

require_once('libwebpay/HealthCheck.php');
require_once('libwebpay/LogHandler.php');
require_once('libwebpay/Utils.php');

class WebPay extends PaymentModule {

    protected $_errors = array();
	var $healthcheck;
	var $log;

    public function __construct() {

        $this->name = 'webpay';
        $this->tab = 'payments_gateways';
        $this->version = '3.0.6';
        $this->author = 'Transbank';
        $this->need_instance = 1;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = 'Webpay Plus Rest';
        $this->description = 'Recibe pagos en linea con Tarjetas de Credito y Redcompra en tu Prestashop a traves de Webpay Plus Rest';
        $this->tab = 'payments_gateways';
        $this->controllers = array('payment', 'validate');

        Context::getContext()->cookie->__set('WEBPAY_TITLE', "Pago con Tarjetas de Credito o Redcompra");
        Context::getContext()->cookie->__set('WEBPAY_BUTTON_TITLE', "Pago electronico con Tarjetas de Credito o Redcompra a traves de Webpay Plus Rest");

        $this->loadIntegrationKeys();

        $this->pluginValidation();
        $this->loadPluginConfiguration();

		$config = array(
            'MODO' => $this->ambient,
            'COMMERCE_CODE' => $this->storeID,
            'API_KEY' => $this->apiKey,
            'ECOMMERCE' => 'prestashop'
        );

        $this->healthcheck = new HealthCheck($config);
		$this->datos_hc = json_decode($this->healthcheck->printFullResume());
		$this->log = new LogHandler();
    }

    public function install() {
		$this->setupPlugin();
        return parent::install() &&
        $this->registerHook('header') &&
        $this->registerHook('paymentOptions') &&
        $this->registerHook('paymentReturn') &&
        $this->registerHook('displayPayment') &&
        $this->registerHook('displayPaymentReturn');
    }

    public function uninstall() {
        if (!parent::uninstall() || !Configuration::deleteByName("WEBPAY"))
            return false;
        return true;
    }

    public function hookPaymentReturn($params) {

        if (!$this->active)
            return;

        $nameOrderRef = isset($params['order']) ? 'order' : 'objOrder';

        $this->smarty->assign(array(
            'shop_name' => $this->context->shop->name,
            'total_to_pay' =>  $params[$nameOrderRef]->getOrdersTotalPaid(),
            'status' => 'ok',
            'id_order' => $params[$nameOrderRef]->id,
            'WEBPAY_RESULT_DESC' => Context::getContext()->cookie->WEBPAY_RESULT_DESC,
            'WEBPAY_VOUCHER_NROTARJETA' => Context::getContext()->cookie->WEBPAY_VOUCHER_NROTARJETA,
            'WEBPAY_VOUCHER_TXDATE_FECHA' => Context::getContext()->cookie->WEBPAY_VOUCHER_TXDATE_FECHA,
            'WEBPAY_VOUCHER_TXDATE_HORA' => Context::getContext()->cookie->WEBPAY_VOUCHER_TXDATE_HORA,
            'WEBPAY_VOUCHER_TOTALPAGO' => Context::getContext()->cookie->WEBPAY_VOUCHER_TOTALPAGO,
            'WEBPAY_VOUCHER_ORDENCOMPRA' => Context::getContext()->cookie->WEBPAY_VOUCHER_ORDENCOMPRA,
            'WEBPAY_VOUCHER_AUTCODE' => Context::getContext()->cookie->WEBPAY_VOUCHER_AUTCODE,
            'WEBPAY_VOUCHER_TIPOCUOTAS' => Context::getContext()->cookie->WEBPAY_VOUCHER_TIPOCUOTAS,
            'WEBPAY_VOUCHER_TIPOPAGO' => Context::getContext()->cookie->WEBPAY_VOUCHER_TIPOPAGO,
            'WEBPAY_VOUCHER_NROCUOTAS' => Context::getContext()->cookie->WEBPAY_VOUCHER_NROCUOTAS,
            'WEBPAY_RESULT_CODE' => Context::getContext()->cookie->WEBPAY_RESULT_CODE
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
        $title = Context::getContext()->cookie->WEBPAY_TITLE;
        Context::getContext()->smarty->assign(array(
            'logo' => "https://www.transbank.cl/public/img/LogoWebpay.png",
            'title' => $title
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
            $this->getWPPaymentOption()
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

    public function getWPPaymentOption() {
       $WPOption = new PrestaShop\PrestaShop\Core\Payment\PaymentOption();
       $paymentController = $this->context->link->getModuleLink($this->name,'payment',array(),true);
       $WPOption->setCallToActionText($this->l('Pago con Tarjetas de Credito o Redcompra'))->setAction($paymentController);
       return $WPOption;
    }

    public function getContent() {

        $activeShopID = (int)Context::getContext()->shop->id;
        $shopDomainSsl = Tools::getShopDomainSsl(true, true);
        $change=false;

        if (Tools::getIsset('webpay_updateSettings')) {

            if (Tools::getValue('ambient') !=  Configuration::get('WEBPAY_AMBIENT')) {
                $change=true;
            }

            Configuration::updateValue('WEBPAY_STOREID', trim(Tools::getValue('storeID')));
            Configuration::updateValue('WEBPAY_APIKEY', trim(Tools::getValue('apiKey')));
            Configuration::updateValue('WEBPAY_AMBIENT', Tools::getValue('ambient'));

            $this->loadPluginConfiguration();
            $this->pluginValidation();

        } else {
            $this->loadPluginConfiguration();
        }

        $config = array(
            'MODO' => $this->ambient,
            'COMMERCE_CODE' => $this->storeID,
            'API_KEY' => $this->apiKey,
            'ECOMMERCE' => 'prestashop'
        );

        $this->healthcheck = new HealthCheck($config);
        if ($change) {
            $rs = $this->healthcheck->getpostinstallinfo();
        }

        $this->datos_hc = json_decode($this->healthcheck->printFullResume());

        Context::getContext()->smarty->assign(
            array(
                'errors' => $this->_errors,
                'post_url' => $_SERVER['REQUEST_URI'],
                'data_storeid_init' => $this->storeID_init,
                'data_apikey_init' => $this->apiKey_init,
                'data_storeid' => $this->storeID,
                'data_apikey' => $this->apiKey,
                'data_ambient' => $this->ambient,
                'data_title' => $this->title,
                'version' => $this->version,
                'api_version' => '1.0',
                'img_icono' => "https://www.transbank.cl/public/img/LogoWebpay.png",
                'commerce_code_validate' =>$this->datos_hc->validate_certificates->consistency->commerce_code_validate,
                'subject_commerce_code' =>$this->datos_hc->validate_certificates->cert_info->subject_commerce_code,
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

    private function adminValidation() {
        $this->_errors = array();
    }

    private function loadPluginConfiguration() {
        $this->storeID = Configuration::get('WEBPAY_STOREID');
        $this->apiKey = Configuration::get('WEBPAY_APIKEY');
        $this->ambient = Configuration::get('WEBPAY_AMBIENT');
        $this->title = Context::getContext()->cookie->WEBPAY_TITLE;
    }

    private function setupPlugin() {
        $this->loadIntegrationKeys();
        Configuration::updateValue('WEBPAY_STOREID', $this->storeID_init);
        Configuration::updateValue('WEBPAY_APIKEY',  $this->apiKey_init);
        Configuration::updateValue('WEBPAY_AMBIENT', "TEST");
    }

    private function loadIntegrationKeys() {
        $keys = include 'libwebpay/IntegrationKeys.php';
        $this->storeID_init = $keys['commerce_code'];
        $this->apiKey_init = $keys['private_key'];
        $this->ambient = Configuration::get('WEBPAY_AMBIENT');
        $this->title = Context::getContext()->cookie->WEBPAY_TITLE;
    }
}
