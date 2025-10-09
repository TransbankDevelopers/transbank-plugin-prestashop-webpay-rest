<?php

use PrestaShop\Module\WebpayPlus\Helpers\OneclickFactory;
use PrestaShop\Module\WebpayPlus\Controller\BaseModuleFrontController;
use PrestaShop\Module\WebpayPlus\Model\TransbankInscriptions;
use PrestaShop\Module\WebpayPlus\Helpers\SqlHelper;
use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;
use PrestaShop\Module\WebpayPlus\Repository\InscriptionRepository;

class WebPayOneclickInscriptionValidateModuleFrontController extends BaseModuleFrontController
{
    protected $responseData = [];

    /** @var InscriptionRepository */
    private $inscriptionRepository;

    public function initContent()
    {
        parent::initContent();
        $this->logger = TbkFactory::createLogger();
        $method = $_SERVER['REQUEST_METHOD'];
        $data = $method === 'GET' ? $_GET : $_POST;
        $token = isset($data["TBK_TOKEN"]) ? $data['TBK_TOKEN'] : null;
        $tbkSessionId = isset($data["TBK_ID_SESION"]) ? $data['TBK_ID_SESION'] : null;
        $tbkOrdenCompra = isset($data["TBK_ORDEN_COMPRA"]) ? $data['TBK_ORDEN_COMPRA'] : null;
        $this->inscriptionRepository = new InscriptionRepository();

        if ($tbkOrdenCompra && $tbkSessionId && !$token) {
            $this->setPaymentErrorPage('Timeout Error.');
        }

        //validar si se registro la tarjeta correctamente correctamente
        if (!isset($token)) {
            $this->throwErrorRedirect('No se recibió el token');
        }

        $ins = $this->inscriptionRepository->getInscriptionByToken($token);

        if (isset($tbkOrdenCompra)) { //se abandono la inscripcion al haber presionado la opción 'Abandonar y volver al comercio'
            $this->inscriptionRepository->updateById($ins['id'], ['status' => TransbankInscriptions::STATUS_FAILED]);
            $this->setPaymentErrorPage('Inscripción abortada desde el formulario. Puedes reintentar la inscripción. ');
        }

        //registro correcto
        //flujo correcto
        $this->finishInscription($ins, $token);
        Tools::redirect('index.php?controller=order');
    }

    private function finishInscription($ins, $token){
        $webpay = OneclickFactory::create();
        try {
            $resp = $webpay->finish($token, $ins->username, $ins->email);
        } catch (\Exception $e) {
            $this->setPaymentErrorPage($e->getMessage());
        }
        $ins->finished = true;
        $ins->authorization_code = $resp->getAuthorizationCode();
        $ins->tbk_token = $resp->getTbkUser();
        $ins->card_type = $resp->getCardType();
        $ins->card_number = $resp->getCardNumber();
        $ins->transbank_response = json_encode($resp);
        $ins->status = $resp->isApproved() ? TransbankInscriptions::STATUS_COMPLETED : TransbankInscriptions::STATUS_FAILED;
        $ins->save();
    }
}
