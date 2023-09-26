<?php

use PrestaShop\Module\WebpayPlus\Controller\BaseModuleFrontController;
use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;
use Transbank\Plugin\Exceptions\CreateTransactionDbException;
use Transbank\Plugin\Exceptions\Webpay\CreateTbkWebpayException;
use Transbank\Plugin\Exceptions\Webpay\InvalidCreateWebpayException;

class WebPayWebpayplusPaymentModuleFrontController extends BaseModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        $this->logger = TbkFactory::createLogger();
        try {
            $cart = $this->getCartFromContext();
            $orderId = $cart->id;
            $amount = $this->getOrderTotalRound($cart);
            $returnUrl = Context::getContext()->link->getModuleLink('webpay', 'webpaypluspaymentvalidate', [], true);
            $tbkWebpayplus = TbkFactory::createTbkWebpayplusService($this->getCurrentStoreId());
            $response = $tbkWebpayplus->createTransaction($orderId, $amount, $returnUrl);
            $this->setRedirectionTemplate($response->token, $response->url, $amount);
        } catch (CreateTbkWebpayException $e) {
            $this->setPaymentErrorPage('Error al crear la transacción', $e->getMessage());
        } catch (InvalidCreateWebpayException $e) {
            $this->setPaymentErrorPage('Error al crear la transacción', $e->getMessage());
        } catch (CreateTransactionDbException $e) {
            $this->setPaymentErrorPage('Error al crear la transacción', $e->getMessage());
        } catch (\Exception $e) {
            $this->setPaymentErrorPage('Error al crear la transacción', $e->getMessage());
        }
    }

    /**
     * @param array $result
     * @param $amount
     */
    protected function setRedirectionTemplate($token, $url, $amount)
    {
        Context::getContext()->smarty->assign([
            'url'      => $url,
            'token_ws'    => $token,
            'amount'   => $amount,
        ]);
        $this->setTemplate('module:webpay/views/templates/front/payment_execution.tpl');
    }
    
}
