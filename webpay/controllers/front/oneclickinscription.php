<?php

use PrestaShop\Module\WebpayPlus\Controller\BaseModuleFrontController;
use PrestaShop\Module\WebpayPlus\Helpers\OneclickFactory;
use PrestaShop\Module\WebpayPlus\Helpers\TbkFactory;
use PrestaShop\Module\WebpayPlus\Model\TransbankInscriptions;
use PrestaShop\Module\WebpayPlus\Repository\InscriptionRepository;
use PrestaShop\Module\WebpayPlus\Service\OneclickInscriptionService;
use PrestaShop\Module\WebpayPlus\Utils\TransbankSdkOneclick;
use PrestaShop\Module\WebpayPlus\Utils\Utils;

class WebPayOneclickInscriptionModuleFrontController extends BaseModuleFrontController
{
    /** @var OneclickInscriptionService */
    private $inscriptionService;

    public function initContent()
    {
        parent::initContent();
        $this->logger = TbkFactory::createLogger();
        $this->logInfo('B.1. Iniciando medio de pago Oneclick');

        $cart = $this->getCartFromContext();
        $customer = $this->context->customer;

        if (!$customer->id || !$customer->isLogged()) {
            $this->setPaymentErrorPage('Debes iniciar sesión para inscribir una tarjeta.');
            return;
        }

        $webpay = OneclickFactory::create();
        $this->inscriptionService = new OneclickInscriptionService(
            new InscriptionRepository(),
            $this->logger,
            $webpay->getEnvironment(),
            $webpay->getCommerceCode()
        );

        $userId = $customer->id;
        $userName = Utils::generateOneclickUsername((int) $userId);
        $userEmail = $customer->email;
        $returnUrl = Context::getContext()->link->getModuleLink('webpay', 'oneclickinscriptionvalidate', [], true);
        $resp = $this->startAndSaveInscription($webpay, $userName, $userEmail, $returnUrl, (int) $userId);

        if ($resp === null) {
            return;
        }

        $this->setRedirectionTemplate($resp, $this->getOrderTotalRound($cart));
    }

    /**
     * Starts a Oneclick inscription with Transbank and persists the local record.
     * On failure, marks a FAILED record with the real token if one was already issued,
     * or the placeholder token otherwise, then renders the payment error page.
     *
     * @param TransbankSdkOneclick $webpay Oneclick SDK client used to start the inscription
     * @param string $userName Oneclick username for the inscription
     * @param string $userEmail Customer email associated with the inscription
     * @param string $returnUrl URL Transbank redirects to after the inscription form
     * @param int $userId Customer ID associated with the inscription
     * @return array|null Inscription response returned by Transbank, or null when the flow failed
     */
    private function startAndSaveInscription(TransbankSdkOneclick $webpay, string $userName, string $userEmail, string $returnUrl, int $userId): ?array
    {
        $token = TransbankInscriptions::NO_TOKEN_PLACEHOLDER;
        $orderId = (int) $this->module->currentOrder;

        try {
            $resp = $webpay->startInscription($userName, $userEmail, $returnUrl);
            $token = $resp['token'];
            $this->inscriptionService->save($userName, $userEmail, $userId, $token, TransbankInscriptions::STATUS_INITIALIZED, 'checkout', $orderId);
        } catch (\Exception $e) {
            $this->inscriptionService->markAsFailed($userName, $userEmail, $userId, $token, 'checkout', $orderId);
            $this->setPaymentErrorPage($e->getMessage());
            return null;
        }

        return $resp;
    }

    /**
     * @param array $result
     * @param $amount
     */
    protected function setRedirectionTemplate(array $result, $amount)
    {
        Context::getContext()->smarty->assign([
            'url'      => isset($result['url']) ? $result['url'] : '',
            'token_ws' => $result['token'],
            'amount'   => round($amount),
            'redirectType' => 'oneclick-inscription'
        ]);
        $this->setTemplate('module:webpay/views/templates/front/redirect_to_payment_form.tpl');
    }
}
