<?php

use PrestaShop\Module\WebpayPlus\Controller\BaseModuleFrontController;
use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;
use Transbank\Plugin\Exceptions\Oneclick\StartTbkOneclickException;
use Transbank\Plugin\Exceptions\CreateInscriptionDbException;

class WebPayOneclickInscriptionModuleFrontController extends BaseModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        $errorTitle = 'Error al crear la inscripción';
        $this->logger = TbkFactory::createLogger();
        try {
            $cart = $this->getCartFromContext();
            $orderId = $cart->id;
            $customer = $this->getCustomerFromContext();
            $tbkOneclick = TbkFactory::createTbkOneclickService($this->getCurrentStoreId());
            $returnUrl = Context::getContext()->link->getModuleLink('webpay',
                'oneclickinscriptionvalidate', [], true);
            $response = $tbkOneclick->startInscription($orderId, $customer->id,
                $customer->email, $returnUrl, 'checkout');
            $this->setRedirectionTemplate($response->token, $response->urlWebpay,
                $this->getOrderTotalRound($cart));
        } catch (StartTbkOneclickException $e) {
            $this->setPaymentErrorPage($errorTitle, $e->getMessage());
        } catch (CreateInscriptionDbException $e) {
            $this->setPaymentErrorPage($errorTitle, $e->getMessage());
        } catch (\Exception $e) {
            $this->setPaymentErrorPage($errorTitle, $e->getMessage());
        }
    }

    private function setRedirectionTemplate($token, $url, $amount)
    {
        Context::getContext()->smarty->assign([
            'url'      => $url,
            'token_ws' => $token,
            'amount'   => $amount
        ]);

        $this->setTemplate('module:webpay/views/templates/front/oneclick_inscription_execution.tpl');
    }

}
