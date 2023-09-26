<?php

use PrestaShop\Module\WebpayPlus\Controller\BaseModuleFrontController;
use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;
use Transbank\Plugin\Exceptions\Oneclick\WithoutTokenInscriptionOneclickException;
use Transbank\Plugin\Exceptions\Oneclick\TimeoutInscriptionOneclickException;
use Transbank\Plugin\Exceptions\Oneclick\UserCancelInscriptionOneclickException;
use Transbank\Plugin\Exceptions\Oneclick\InvalidStatusInscriptionOneclickException;
use Transbank\Plugin\Exceptions\Oneclick\RejectedInscriptionOneclickException;
use Transbank\Plugin\Exceptions\GetInscriptionDbException;
use Transbank\Plugin\Exceptions\Oneclick\FinishTbkOneclickException;
      

class WebPayOneclickInscriptionValidateModuleFrontController extends BaseModuleFrontController
{
    protected $responseData = [];

    public function initContent()
    {
        parent::initContent();
        $errorTitle = 'Error al confirmar la inscripción';
        $this->logger = TbkFactory::createLogger();
        try {
            $tbkOneclick = TbkFactory::createTbkOneclickService($this->getCurrentStoreId());
            $tbkOneclick->processTbkReturnAndFinishInscription($_SERVER, $_GET, $_POST);
            $this->redirectToOrderConfirmationByCartId($this->context->cart->id);
        } catch (WithoutTokenInscriptionOneclickException $e) {
            $this->setPaymentErrorPage($errorTitle, $e->getMessage());
        } catch (TimeoutInscriptionOneclickException $e) {
            $this->setPaymentErrorPage($errorTitle, $e->getMessage());
        } catch (UserCancelInscriptionOneclickException $e) {
            $this->setPaymentErrorPage('Inscripción abortada desde el formulario. Puedes reintentar la inscripción. ');
        } catch (InvalidStatusInscriptionOneclickException $e) {
            $this->setPaymentErrorPage($errorTitle, $e->getMessage());
        } catch (RejectedInscriptionOneclickException $e) {
            $this->setPaymentErrorPage($errorTitle, $e->getMessage());
        } catch (GetInscriptionDbException $e) {
            $this->setPaymentErrorPage($errorTitle, $e->getMessage());
        } catch (FinishTbkOneclickException $e) {
            $this->setPaymentErrorPage($errorTitle, $e->getMessage());
        } catch (Exception $e) {
            $this->setPaymentErrorPage($errorTitle, $e->getMessage());
        }
    }
    private function redirectToOrderConfirmationByCartId($cartId)
    {
        Tools::redirect('index.php?controller=order');
    }
}
