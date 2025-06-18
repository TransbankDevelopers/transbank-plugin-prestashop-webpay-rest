<?php

namespace PrestaShop\Module\WebpayPlus\Utils;

use GuzzleHttp\Exception\GuzzleException;
use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;
use Transbank\Plugin\Exceptions\EcommerceException;
use Transbank\Webpay\Options;
use Transbank\Webpay\WebpayPlus;
use Transbank\Webpay\WebpayPlus\Responses\TransactionCommitResponse;
use Transbank\Webpay\WebpayPlus\Transaction;
use Transbank\Webpay\WebpayPlus\Exceptions\TransactionCommitException;
use Transbank\Webpay\WebpayPlus\Exceptions\TransactionCreateException;

/**
 * Class TransbankSdkWebpayRest.
 */
class TransbankSdkWebpay
{
    /**
     * @var Options
     */
    public $options;
    protected $log;

    protected $transaction = null;

    /**
     * TransbankSdkWebpayRest constructor.
     *
     * @param $config
     */
    public function __construct($config)
    {
        $this->log = TbkFactory::createLogger();
        $this->options = new Options(
            WebpayPlus::INTEGRATION_API_KEY,
            WebpayPlus::INTEGRATION_COMMERCE_CODE,
            Options::ENVIRONMENT_INTEGRATION
        );
        $environment = isset($config['ENVIRONMENT']) ? $config['ENVIRONMENT'] : null;
        if (isset($config) && $environment == Options::ENVIRONMENT_PRODUCTION) {
            $this->options = new Options(
                $config['API_KEY_SECRET'],
                $config['COMMERCE_CODE'],
                Options::ENVIRONMENT_PRODUCTION);
        }
        $this->transaction = new Transaction($this->options);
    }

    public function getCommerceCode()
    {
        return $this->options->getCommerceCode();
    }

    public function getEnviroment()
    {
        return $this->options->getIntegrationType();
    }

    /**
     * @param $amount
     * @param $sessionId
     * @param $buyOrder
     * @param $returnUrl
     *
     * @throws EcommerceException
     *
     * @return array
     */
    public function createTransaction($amount, $sessionId, $buyOrder, $returnUrl)
    {
        $result = [];

        try {
            $txDate = date('d-m-Y');
            $txTime = date('H:i:s');
            $this->log->logInfo('createTransaction : amount: ' . $amount . ', sessionId: ' .
                $sessionId . ', buyOrder: ' . $buyOrder . ', txDate: ' . $txDate . ', txTime: ' . $txTime);
            $initResult = $this->transaction->create($buyOrder, $sessionId, $amount, $returnUrl);
            $this->log->logInfo('createTransaction.result: ' . json_encode($initResult));
            if (isset($initResult) && isset($initResult->url) && isset($initResult->token)) {
                $result = [
                    'url' => $initResult->url,
                    'token_ws' => $initResult->token,
                ];
            } else {
                $errorMessage = "Error creando la transacción para => buyOrder: {$buyOrder}, amount: {$amount}";
                throw new EcommerceException($errorMessage);
            }
        } catch (TransactionCreateException $e) {
            $errorMessage = "Error creando la transacción para =>
                buyOrder: {$buyOrder}, amount: {$amount}, error: {$e->getMessage()}";
            throw new EcommerceException($errorMessage, $e);
        }

        return $result;
    }

    /**
     * @param $token
     *
     * @throws \Transbank\Plugin\Exceptions\EcommerceException
     *
     * @return \Transbank\Webpay\WebpayPlus\Responses\TransactionCommitResponse
     */
    public function commitTransaction(string $token): TransactionCommitResponse
    {
        try {
            $this->log->logInfo("commitTransaction : token: {$token}");
            if (!isset($token)) {
                throw new EcommerceException('El token webpay es requerido');
            }

            return $this->transaction->commit($token);
        } catch (TransactionCommitException | \InvalidArgumentException | GuzzleException $e) {
            $errorMessage = "Error confirmando la transacción para el token: {$token}, error: {$e->getMessage()}";
            throw new EcommerceException($errorMessage, $e);
        }
    }
}
