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
        if (isset($config) && isset($config['ENVIRONMENT']) && $config['ENVIRONMENT'] == Options::ENVIRONMENT_PRODUCTION){
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
                throw new EcommerceException('No se ha iniciado la inscripción para => userName: ' . $userName . ', email: ' . $email);
            }
        } catch (Exception $e) {
            $result = [
                'error'  => 'Error al iniciar la inscripción',
                'detail' => $e->getMessage().$returnUrl,
            ];
            $this->log->logError(json_encode($result));
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
    public function finishInscription($token, $userName, $email)
    {
        $result = [];

        try {
            $txDate = date('d-m-Y');
            $txTime = date('H:i:s');
            $this->log->logInfo('finishInscription => token: ' . $token . ' userName: ' . $userName . ', email: ' . $email .
                ', txDate: ' . $txDate . ', txTime: ' . $txTime);

            $resp = $this->inscription->finish($token);

            $this->log->logInfo('finishInscription - resp: ' . json_encode($resp));
            return $resp;
        } catch (Exception $e) {
            $result = [
                'error'  => 'Error al confirmar la inscripción',
                'detail' => $e->getMessage(),
            ];
            $this->log->logError(json_encode($result));
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
    public function authorizeTransaction($username, $tbkUser, $parentBuyOrder, $childBuyOrder, $amount)
    {
        $result = [];
        try {
            $txDate = date('d-m-Y');
            $txTime = date('H:i:s');
            $this->log->logInfo('authorizeTransaction => username: ' . $username . ' parentBuyOrder: ' . $parentBuyOrder. ' childBuyOrder: ' . $childBuyOrder . ', amount: ' . $amount .
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
            $this->log->logInfo('authorizeTransaction - resp: ' . json_encode($resp));
            return $resp;
        } catch (Exception $e) {
            $result = [
                'error'  => 'Error al autorizar el pago',
                'detail' => $e->getMessage(),
            ];
            $this->log->logError(json_encode($result));
        }

        return $result;
    }


}
