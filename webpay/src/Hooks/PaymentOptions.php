<?php

namespace PrestaShop\Module\WebpayPlus\Hooks;

use Link;
use Cart;
use Media;
use Context;
use Currency;
use PrestaShop\Module\WebpayPlus\Config\OneclickConfig;
use Transbank\Plugin\Helpers\TbkConstants;
use PrestaShop\Module\WebpayPlus\Config\WebpayConfig;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use PrestaShop\Module\WebpayPlus\Repository\InscriptionRepository;

class PaymentOptions implements HookHandlerInterface
{
    /**
     * @var Context Instance of the ecommerce Context.
     */
    private $context;

    /**
     * @var array Currencies supported for the module.
     */
    private $moduleCurrencies;

    /**
     * @var InscriptionRepository Instance of the inscription repository.
     */
    private $oneclickInscriptionRepository;

    /**
     * Constructor.
     * Initializes the class.
     *
     * @param array $moduleCurrencies Currencies supported for the module.
     */
    public function __construct(array $moduleCurrencies)
    {
        $this->context = Context::getContext();
        $this->moduleCurrencies = $moduleCurrencies;
        $this->oneclickInscriptionRepository = new InscriptionRepository();
    }

    /**
     * Executes the hook logic to display payment details.
     *
     * @param array $params The parameters passed to the hook..
     * @return array Array of payment options.
     */
    public function execute(array $params): array
    {
        $paymentOptions = [];

        if (!$this->checkCurrency($params['cart'])) {
            return $paymentOptions;
        }

        if (WebpayConfig::isConfigOk() && WebpayConfig::isPaymentMethodActive()) {
            $paymentOptions[] = $this->getWebpayPaymentOption();
        }

        if (OneclickConfig::isConfigOk() && OneclickConfig::isPaymentMethodActive() && $this->isCustomerLogged()) {
            array_push($paymentOptions, ...$this->getOneclickPaymentOptions());
        }
        return $paymentOptions;
    }

    /**
     * Check if payment is valid for the current cart currency.
     *
     * @param Cart $cart The current cart.
     * @return bool true if is valid, otherwise false.
     */
    private function checkCurrency(Cart $cart): bool
    {
        $currencyOrder = new Currency($cart->id_currency);
        if (is_array($this->moduleCurrencies)) {
            foreach ($this->moduleCurrencies as $currency_module) {
                if ($currencyOrder->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get the Webpay payment option.
     *
     * @return PaymentOption The payment option.
     */
    private function getWebpayPaymentOption(): PaymentOption
    {
        $WPOption = new PaymentOption();
        $link = new Link();

        $paymentController = $link->getModuleLink(TbkConstants::MODULE_NAME, 'webpaypluspayment', array(), true);
        $message = "Permite el pago de productos y/o servicios, con tarjetas de crédito,
            débito y prepago a través de Webpay Plus";
        $logoPath = _PS_MODULE_DIR_ . TbkConstants::MODULE_NAME . '/views/img/wpplus_small.png';

        return
            $WPOption->setCallToActionText($message)
                ->setAction($paymentController)
                ->setLogo(Media::getMediaPath($logoPath));
    }
    private function getOneclickPaymentOptions(): array
    {
        $paymentOptions = $this->getOneclickCardsPaymentOptions();

        if (count($paymentOptions) > 0) {
            $paymentOptions[] = $this->getOneclickInscriptionOption('Usar un nuevo método de pago');
        } else {
            $paymentOptions[] = $this->getOneclickInscriptionOption();
        }

        return $paymentOptions;
    }

    private function getOneclickCardsPaymentOptions(): array
    {
        $link = new Link();
        $paymentOptions = [];
        $paymentController = $link->getModuleLink(TbkConstants::MODULE_NAME, 'oneclickpaymentvalidate', array(), true);
        $cards = $this->oneclickInscriptionRepository->getCardsByUserId($this->getUserId());
        $logoPath = _PS_MODULE_DIR_ . TbkConstants::MODULE_NAME . '/views/img/oneclick_small.png';

        foreach ($cards as $card) {
            $po = new PaymentOption();
            $cardNumber = $card['card_number'];
            $environment = $card['environment'] == 'TEST' ? '[TEST] ' : '';

            array_push(
                $paymentOptions,
                $po->setCallToActionText($environment . $card['card_type'] . ' terminada en ' . substr($cardNumber, -4, 4))
                    ->setAction($paymentController)
                    ->setLogo(Media::getMediaPath($logoPath))
                    ->setInputs([
                        'token' => [
                            'name' => 'inscriptionId',
                            'type' => 'hidden',
                            'value' => $card['id']
                        ],
                    ])
            );
        }

        return $paymentOptions;
    }

    /**
     * Get the Oneclick inscription payment option.
     *
     * @return PaymentOption The payment option.
     */
    private function getOneclickInscriptionOption($description = null): PaymentOption
    {
        $po = new PaymentOption();
        $link = new Link();

        $defaultDescription = "Inscribe tu tarjeta de crédito, débito o prepago y luego paga con un solo click a través de Webpay Oneclick";
        $logoPath = _PS_MODULE_DIR_ . TbkConstants::MODULE_NAME . '/views/img/oneclick_small.png';
        $controller = $link->getModuleLink(TbkConstants::MODULE_NAME, 'oneclickinscription', array(), true);
        return $po->setCallToActionText($description ?? $defaultDescription)
            ->setAction($controller)
            ->setLogo(Media::getMediaPath($logoPath))
            ->setInputs([
                'token' => [
                    'name' => 'inscriptionId',
                    'type' => 'hidden',
                    'value' => 0
                ],
            ]);
    }

    /**
     * Get the user ID.
     *
     * @return int|null The user ID if the customer is logged, otherwise null.
     */
    private function getUserId(): ?int
    {
        $context = Context::getContext();
        if ($this->isCustomerLogged()) {
            return $context->customer->id;
        }
        return null;
    }

    /**
     * Check if the customer is logged.
     *
     * @return bool true if the customer is logged, otherwise false.
     */
    private function isCustomerLogged(): bool
    {
        return $this->context->customer->isLogged();
    }
}
