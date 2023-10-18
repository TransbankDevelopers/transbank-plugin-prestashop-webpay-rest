<?php

use PrestaShop\Module\WebpayPlus\Helpers\OneclickFactory;
use PrestaShop\Module\WebpayPlus\Controller\BaseModuleFrontController;
use PrestaShop\Module\WebpayPlus\Model\TransbankInscriptions;
use PrestaShop\Module\WebpayPlus\Helpers\SqlHelper;
use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;

class WebPayOneclickInscriptionValidateModuleFrontController extends BaseModuleFrontController
{
    protected $responseData = [];

    public function initContent()
    {
        parent::initContent();
        $this->logger = TbkFactory::createLogger();
        $method = $_SERVER['REQUEST_METHOD'];
        $data = $method === 'GET' ? $_GET : $_POST;
        $token = isset($data["TBK_TOKEN"]) ? $data['TBK_TOKEN'] : null;
        $tbkSessionId = isset($data["TBK_ID_SESION"]) ? $data['TBK_ID_SESION'] : null;
        $tbkOrdenCompra = isset($data["TBK_ORDEN_COMPRA"]) ? $data['TBK_ORDEN_COMPRA'] : null;

        if ($tbkOrdenCompra && $tbkSessionId && !$token){
            $this->setErrorTemplate(['error' => 'Timeout Error.']);
        }

        //validar si se registro la tarjeta correctamente correctamente
        if (!isset($token)) {
            $this->throwErrorRedirect('No se recibió el token');
        }

        $ins = $this->getInscriptionByToken($token);

        if (isset($tbkOrdenCompra)) {//se abandono la inscripcion al haber presionado la opción 'Abandonar y volver al comercio'
            $ins->status = TransbankInscriptions::STATUS_FAILED;
            $ins->save();
            $this->setErrorTemplate(['error' => 'Inscripción abortada desde el formulario. Puedes reintentar la inscripción. ']);//.', token: '.$token.', tbkSessionId: '.$tbkSessionId.', tbkOrdenCompra: '.$tbkOrdenCompra
        }

        //registro correcto
        //flujo correcto
        $this->finishInscription($ins, $token);

        //return $this->setErrorTemplate(['error' => 'Todo biennnnn']);
        //$cart = new Cart($webpayTransaction->cart_id);
        $this->redirectToOrderConfirmationByCartId($this->context->cart->id);

    }

    private function finishInscription($ins, $token){
        $webpay = OneclickFactory::create();
        $resp = $webpay->finishInscription($token, $ins->username, $ins->email);
        $ins->finished = true;
        $ins->authorization_code = $resp->getAuthorizationCode();
        $ins->tbk_token = $resp->getTbkUser();
        $ins->card_type = $resp->getCardType();
        $ins->card_number = $resp->getCardNumber();
        $ins->transbank_response = json_encode($resp);
        $ins->status = $resp->isApproved() ? TransbankInscriptions::STATUS_COMPLETED : TransbankInscriptions::STATUS_FAILED;
        $ins->save();
    }



    /**
     * @return TransbankInscriptions
     */
    private function getInscriptionByToken($token)
    {
        $sql = 'SELECT * FROM '._DB_PREFIX_.TransbankInscriptions::TABLE_NAME.' WHERE `token` = "'.pSQL($token).'"';
        $result = SqlHelper::getRow($sql);
        if ($result === false) {
            $this->throwErrorRedirect('Oneclick Token '.$token.' was not found on database');
        }
        return new TransbankInscriptions($result['id']);
    }



    /**
     * @param array $result
     */
    protected function setErrorTemplate(array $result)
    {
        $date_tx_hora = date('H:i:s');
        $date_tx_fecha = date('d-m-Y');

        $error = isset($result['error']) ? $result['error'] : '';
        $detail = isset($result['detail']) ? $result['detail'] : '';

        $this->logError($error.' ('.$detail.')');

        Context::getContext()->smarty->assign([
            'WEBPAY_RESULT_CODE'          => 500,
            'WEBPAY_RESULT_DESC'          => $error.' ('.$detail.')',
            'WEBPAY_VOUCHER_ORDENCOMPRA'  => 0,
            'WEBPAY_VOUCHER_TXDATE_HORA'  => $date_tx_hora,
            'WEBPAY_VOUCHER_TXDATE_FECHA' => $date_tx_fecha,
        ]);

        $this->setTemplate('module:webpay/views/templates/front/payment_error.tpl');
    }

    protected function redirectToOrderConfirmationByCartId($cartId)
    {
        Tools::redirect('index.php?controller=order');
    }


}
