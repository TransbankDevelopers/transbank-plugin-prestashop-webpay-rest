<?php

namespace PrestaShop\Module\WebpayPlus\Utils;

use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;
use Transbank\Plugin\Exceptions\EcommerceException;
use Transbank\Webpay\Oneclick;
use Transbank\Webpay\Oneclick\Exceptions\InscriptionDeleteException;
use Transbank\Webpay\Options;
use Transbank\Webpay\Oneclick\MallInscription;
use Transbank\Webpay\Oneclick\MallTransaction;
use Transbank\Webpay\Oneclick\Exceptions\MallTransactionAuthorizeException;
use Transbank\Webpay\Oneclick\Exceptions\InscriptionStartException;
use Transbank\Webpay\Oneclick\Exceptions\InscriptionFinishException;

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

        if($config['ENVIRONMENT'] == Options::ENVIRONMENT_PRODUCTION) {
            $this->options = new Options(
                $config['API_KEY_SECRET'],
                $config['COMMERCE_CODE'],
                $config['ENVIRONMENT']
            );
        } else {
            $this->options = new Options(
                Oneclick::DEFAULT_API_KEY,
                Oneclick::DEFAULT_COMMERCE_CODE,
                $config['ENVIRONMENT']
            );
        }

        $this->inscription = new MallInscription($this->options);
        $this->transaction = new MallTransaction($this->options);
        $this->childCommerceCode = $config['CHILD_COMMERCE_CODE'];
    }

    public function getCommerceCode(){
        return $this->options->getCommerceCode();
    }

    public function getEnvironment()
    {
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
     * @throws EcommerceException
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
        } catch (InscriptionStartException $e) {
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
     * @throws EcommerceException
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
        } catch (InscriptionFinishException $e) {
            $errorMessage = "Error al confirmar la inscripción para =>
                userName: {$userName}, email: {$email}, error: {$e->getMessage()}";
            $this->log->logError($errorMessage);
            throw new EcommerceException($errorMessage, $e);
        }
        return $result;
    }

    /**
     * @param $userName
     * @param $tbkUser
     *
     * @throws EcommerceException
     *
     * @return bool
     */
    public function delete(string $tbkUser, string $userName): bool
    {
        try {
            $txDate = date('d-m-Y');
            $txTime = date('H:i:s');
            $this->log->logInfo('delete => userName: ' . $userName . ', tbkUser: ' . $tbkUser .
                ', txDate: ' . $txDate . ', txTime: ' . $txTime);
            $resp = $this->inscription->delete($tbkUser, $userName);
            $this->log->logInfo('delete - resp: ' . json_encode($resp));
            return $resp;
        } catch (InscriptionDeleteException $e) {
            $errorMessage = "Error al eliminar la inscripción para =>
                userName: {$userName}, tbkUser: {$tbkUser}, error: {$e->getMessage()}";
            $this->log->logError($errorMessage);
            throw new EcommerceException($errorMessage, $e);
        }
    }

    /**
     * @param $username
     * @param $tbkUser
     * @param $parentBuyOrder
     * @param $childBuyOrder
     * @param $amount
     *
     * @throws EcommerceException
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
        } catch (MallTransactionAuthorizeException $e) {
            $errorMessage = "Error al autorizar el pago para => userName:
                {$username}, buyOrder: {$parentBuyOrder}, error: {$e->getMessage()}";
            $this->log->logError($errorMessage);
            throw new EcommerceException($errorMessage, $e);
        }
        return $result;
    }

}
