<?php

namespace PrestaShop\Module\WebpayPlus\Utils;

use Exception;
use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;
use Transbank\Plugin\Exceptions\EcommerceException;
use Transbank\Webpay\Options;
use Transbank\Webpay\Oneclick\MallInscription;
use Transbank\Webpay\Oneclick\MallTransaction;

/**
 * Class TransbankSdkOneclick.
 */
class TransbankSdkOneclick
{
    /**
     * @var Options
     */
    public $options;
    protected $log;

    protected $inscription = null;
    protected $transaction = null;
    protected $childCommerceCode = null;

    /**
     * TransbankSdkOneclick constructor.
     *
     * @param $config
     */
    public function __construct($config)
    {
        $this->log = TbkFactory::createLogger();
        $this->options = MallInscription::getDefaultOptions();
        $environment = isset($config['ENVIRONMENT']) ? $config['ENVIRONMENT'] : null;
        if (isset($config) && $environment == Options::ENVIRONMENT_PRODUCTION){
            $this->options = Options::forProduction($config['COMMERCE_CODE'], $config['API_KEY_SECRET']);
        }
        $this->inscription = new MallInscription($this->options);
        $this->transaction = new MallTransaction($this->options);
        $this->childCommerceCode = $config['CHILD_COMMERCE_CODE'];
    }

    public function getCommerceCode(){
        return $this->options->getCommerceCode();
    }

    public function getEnviroment(){
        return $this->options->getIntegrationType();
    }

    public function getChildCommerceCode(){
        return $this->childCommerceCode;
    }

    /**
     * @param $userName
     * @param $email
     * @param $returnUrl
     *
     * @throws Exception
     *
     * @return array
     */
    public function startInscription($userName, $email, $returnUrl)
    {
        $result = [];
        try {
            $txDate = date('d-m-Y');
            $txTime = date('H:i:s');
            $this->log->logInfo('startInscription - userName: ' . $userName . ', email: ' . $email .
                ', txDate: ' . $txDate . ', txTime: ' . $txTime);

            $resp = $this->inscription->start($userName, $email, $returnUrl);
            $this->log->logInfo('startInscription - resp: ' . json_encode($resp));
            if (isset($resp) && isset($resp->urlWebpay) && isset($resp->token)) {
                $result = [
                    'url'      => $resp->urlWebpay,
                    'token' => $resp->token,
                ];
            } else {
                $errorMessage = "Error al iniciar la inscripción para => userName: {$userName}, email: {$email}";
                throw new EcommerceException($errorMessage);
            }
        } catch (Exception $e) {
            $errorMessage = "Error al iniciar la inscripción para =>
                userName: {$userName}, email: {$email}, error: {$e->getMessage()}";
            $this->log->logError($errorMessage);
            throw new EcommerceException($errorMessage, $e);
        }
        return $result;
    }

    /**
     * @param $token
     * @param $userName
     * @param $email
     *
     * @throws Exception
     *
     * @return array|Transbank\Webpay\Oneclick\Responses\InscriptionFinishResponse
     */
    public function finish($token, $userName, $email)
    {
        $result = [];
        try {
            $txDate = date('d-m-Y');
            $txTime = date('H:i:s');
            $this->log->logInfo('finish => token: ' . $token . ' userName: ' . $userName . ', email: ' . $email .
                ', txDate: ' . $txDate . ', txTime: ' . $txTime);
            $resp = $this->inscription->finish($token);
            $this->log->logInfo('finish - resp: ' . json_encode($resp));
            return $resp;
        } catch (Exception $e) {
            $errorMessage = "Error al confirmar la inscripción para =>
                userName: {$userName}, email: {$email}, error: {$e->getMessage()}";
            $this->log->logError($errorMessage);
            throw new EcommerceException($errorMessage, $e);
        }
        return $result;
    }

    /**
     * @param $username
     * @param $tbkUser
     * @param $parentBuyOrder
     * @param $childBuyOrder
     * @param $amount
     *
     * @throws Exception
     *
     * @return array|Transbank\Webpay\Oneclick\Responses\MallTransactionAuthorizeResponse
     */
    public function authorize($username, $tbkUser, $parentBuyOrder, $childBuyOrder, $amount)
    {
        $result = [];
        try {
            $txDate = date('d-m-Y');
            $txTime = date('H:i:s');
            $this->log->logInfo('authorize => username: ' . $username . ' parentBuyOrder: '
                . $parentBuyOrder. ' childBuyOrder: ' . $childBuyOrder . ', amount: ' . $amount .
                ', txDate: ' . $txDate . ', txTime: ' . $txTime);
            $details = [
                [
                    'commerce_code'       => $this->getChildCommerceCode(),
                    'buy_order'           => $childBuyOrder,
                    'amount'              => $amount,
                    'installments_number' => 1,
                ],
            ];
            $resp = $this->transaction->authorize($username, $tbkUser, $parentBuyOrder, $details);
            $this->log->logInfo('authorize - resp: ' . json_encode($resp));
            return $resp;
        } catch (Exception $e) {
            $errorMessage = "Error al autorizar el pago para => userName:
                {$username}, buyOrder: {$parentBuyOrder}, error: {$e->getMessage()}";
            $this->log->logError($errorMessage);
            throw new EcommerceException($errorMessage, $e);
        }
        return $result;
    }

}
