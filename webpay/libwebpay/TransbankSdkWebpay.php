<?php

require_once _PS_MODULE_DIR_.'webpay/vendor/autoload.php';
require_once 'LogHandler.php';

use Transbank\Webpay\Options;
use Transbank\Webpay\WebpayPlus;

class TransbankSdkWebpay
{
    public $options;

    public function __construct($config)
    {
        $this->log = new LogHandler();
        if (isset($config)) {
            $environment = isset($config['ENVIRONMENT']) ? $config['ENVIRONMENT'] : 'TEST';
            $this->options = ($environment != 'TEST') ? new Options($config['API_KEY_SECRET'], $config['COMMERCE_CODE']) : Options::defaultConfig();
            $this->options->setIntegrationType($environment);
        }
    }

    public function createTransaction($amount, $sessionId, $buyOrder, $returnUrl)
    {
        $result = [];

        try {
            $txDate = date('d-m-Y');
            $txTime = date('H:i:s');
            $this->log->logInfo('initTransaction - amount: '.$amount.', sessionId: '.$sessionId.
                ', buyOrder: '.$buyOrder.', txDate: '.$txDate.', txTime: '.$txTime);
            $initResult = WebpayPlus\Transaction::create($buyOrder, $sessionId, $amount, $returnUrl, $this->options);
            $this->log->logInfo('initTransaction - initResult: '.json_encode($initResult));
            if (isset($initResult) && isset($initResult->url) && isset($initResult->token)) {
                $result = [
                    'url'      => $initResult->url,
                    'token_ws' => $initResult->token,
                ];
            } else {
                throw new Exception('No se ha creado la transacción para, amount: '.$amount.', sessionId: '.$sessionId.', buyOrder: '.$buyOrder);
            }
        } catch (Exception $e) {
            $result = [
                'error'  => 'Error al crear la transacción',
                'detail' => $e->getMessage(),
            ];
            $this->log->logError(json_encode($result));
        }

        return $result;
    }

    public function commitTransaction($tokenWs)
    {
        $result = [];

        try {
            $this->log->logInfo('getTransactionResult - tokenWs: '.$tokenWs);
            if ($tokenWs == null) {
                throw new Exception('El token webpay es requerido');
            }

            return WebpayPlus\Transaction::commit($tokenWs, $this->options);
        } catch (Exception $e) {
            $result = [
                'error'  => 'Error al confirmar la transacción',
                'detail' => $e->getMessage(),
            ];
            $this->log->logError(json_encode($result));
        }

        return $result;
    }
}
