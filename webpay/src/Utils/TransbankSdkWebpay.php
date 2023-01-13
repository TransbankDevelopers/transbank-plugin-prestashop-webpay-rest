<?php

namespace PrestaShop\Module\WebpayPlus\Utils;

use Exception;
use Transbank\Webpay\Options;
use Transbank\Webpay\WebpayPlus\Transaction;
use Transbank\Webpay\WebpayPlus\Exceptions\TransactionCommitException;
use Transbank\Webpay\WebpayPlus;
use PrestaShop\Module\WebpayPlus\Utils\LogHandler;

/**
 * Class TransbankSdkWebpayRest.
 */
class TransbankSdkWebpay
{
    /**
     * @var Options
     */
    public $options;
    /**
     * @var LogHandler
     */
    protected $log;

    protected $transaction = null;

    /**
     * TransbankSdkWebpayRest constructor.
     *
     * @param $config
     */
    public function __construct($config)
    {
        $this->log = new LogHandler();
        $this->options = Transaction::getDefaultOptions();
        if (isset($config) && isset($config['ENVIRONMENT']) && $config['ENVIRONMENT'] == Options::ENVIRONMENT_PRODUCTION){
            $this->options = Options::forProduction($config['COMMERCE_CODE'], $config['API_KEY_SECRET']);
        }
        $this->transaction = new Transaction($this->options);
    }

    public function getCommerceCode(){
        return $this->options->getCommerceCode();
    }

    public function getEnviroment(){
        return $this->options->getIntegrationType();
    }

    /**
     * @param $amount
     * @param $sessionId
     * @param $buyOrder
     * @param $returnUrl
     *
     * @throws Exception
     *
     * @return array
     */
    public function createTransaction($amount, $sessionId, $buyOrder, $returnUrl)
    {
        $result = [];

        try {
            $txDate = date('d-m-Y');
            $txTime = date('H:i:s');
            $this->log->logInfo('initTransaction - amount: ' . $amount . ', sessionId: ' . $sessionId .
                ', buyOrder: ' . $buyOrder . ', txDate: ' . $txDate . ', txTime: ' . $txTime);

            $initResult = $this->transaction->create($buyOrder, $sessionId, $amount, $returnUrl);

            $this->log->logInfo('initTransaction - initResult: ' . json_encode($initResult));
            if (isset($initResult) && isset($initResult->url) && isset($initResult->token)) {
                $result = [
                    'url'      => $initResult->url,
                    'token_ws' => $initResult->token,
                ];
            } else {
                throw new Exception('No se ha creado la transacción para, amount: ' . $amount . ', sessionId: ' . $sessionId . ', buyOrder: ' . $buyOrder);
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

    /**
     * @param $tokenWs
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws Exception
     *
     * @return array|WebpayPlus\TransactionCommitResponse
     */
    public function commitTransaction($tokenWs)
    {
        $result = [];

        try {
            $this->log->logInfo('getTransactionResult - tokenWs: ' . $tokenWs);
            if ($tokenWs == null) {
                throw new Exception('El token webpay es requerido');
            }

            return $this->transaction->commit($tokenWs);
        } catch (TransactionCommitException $e) {
            $result = [
                'error'  => 'Error al confirmar la transacción',
                'detail' => $e->getMessage(),
            ];
            $this->log->logError(json_encode($result));
        }

        return $result;
    }
}
