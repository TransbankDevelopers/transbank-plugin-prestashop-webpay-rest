<?php

namespace PrestaShop\Module\WebpayPlus\Helpers;

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use PrestaShop\Module\WebpayPlus\Model\TransbankInscriptions;
use Transbank\Webpay\Oneclick;
use Transbank\Webpay\Options;
use PrestaShop\Module\WebpayPlus\Helpers\SqlHelper;
use Configuration;
use Context;
use Media;
use Link;
use PrestaShop\Module\WebpayPlus\Config\OneclickConfig;
use PrestaShop\Module\WebpayPlus\Utils\StringUtils;
use Transbank\Plugin\Helpers\TbkConstants;

/**
 * Trait InteractsWithOneclick.
 */
trait InteractsWithOneclick
{
    protected function getGroupOneclickPaymentOption()
    {
        if (!$this->isCustomerLogged() || !$this->isOneclickActivated()) {
            return [];
        }
        if ($this->getCountCardsByUserId($this->getUserIdForOneclick()) > 0) {
            return $this->getOneclickPaymentOption();
        }

        return [$this->getNewOneclickPaymentOption()];
    }

    protected function getOneclickPaymentOption()
    {
        $result = [];
        $link = new Link();
        $paymentController = $link->getModuleLink(TbkConstants::MODULE_NAME, 'oneclickpaymentvalidate', array(), true);
        $cards = $this->getCardsByUserId($this->getUserIdForOneclick());
        $logoPath = _PS_MODULE_DIR_ . TbkConstants::MODULE_NAME . '/views/img/oneclick_small.png';
        foreach ($cards as $card) {
            $po = new PaymentOption();
            $cardNumber = $card['card_number'];
            $environment = $card['environment'] == 'TEST' ? '[TEST] ' : '';
            array_push(
                $result,
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

        array_push($result, $this->getOneclickInscriptionOption('Usar un nuevo método de pago'));
        return $result;
    }

    protected function getNewOneclickPaymentOption()
    {
        $message = "Inscribe tu tarjeta de crédito,
            débito o prepago y luego paga con un solo click a través de Webpay Oneclick";
        return $this->getOneclickInscriptionOption($message);
    }

    protected function getOneclickInscriptionOption($description)
    {
        $po = new PaymentOption();
        $link = new Link();
        $logoPath = _PS_MODULE_DIR_ . TbkConstants::MODULE_NAME . '/views/img/oneclick_small.png';
        $controller = $link->getModuleLink(TbkConstants::MODULE_NAME, 'oneclickinscription', array(), true);
        return $po->setCallToActionText($description)
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

    protected function getCardsByUserId($userId)
    {
        $r = SqlHelper::executeSql('SELECT * FROM ' .
            _DB_PREFIX_ . TransbankInscriptions::TABLE_NAME .
            ' WHERE `status` = "' . TransbankInscriptions::STATUS_COMPLETED . '" and `user_id` = "' . pSQL($userId) . '"');
        if (!isset($r)) {
            return [];
        }
        return $r;
    }

    protected function getCountCardsByUserId($userId)
    {
        return SqlHelper::getValue('SELECT count(1) FROM '
            . _DB_PREFIX_ . TransbankInscriptions::TABLE_NAME . ' WHERE `status` = "'
            . TransbankInscriptions::STATUS_COMPLETED . '" and `user_id` = "' . pSQL($userId) . '"');
    }

    protected function getUserIdForOneclick()
    {
        $context = Context::getContext();
        if ($context->customer->isLogged()) {
            return $context->customer->id;
        }
        return null;
    }

    protected function loadDefaultConfigurationOneclick()
    {
        $oneclickEnviroment = OneclickConfig::getEnvironment();
        /* Si existe configuración de producción se copiara */
        if (isset($oneclickEnviroment) && $oneclickEnviroment == Options::ENVIRONMENT_PRODUCTION) {
            $oneclickActive = OneclickConfig::getPaymentActive();
            $oneclickActive = isset($oneclickActive) ? $oneclickActive : 1;
            $oneclickMallCommerceCode = OneclickConfig::getCommerceCode();
            $oneclickChildCommerceCode = OneclickConfig::getChildCommerceCode();
            $oneclickApikey = OneclickConfig::getApiKey();
            $oneclickDefaultOrderStateIdAfterPayment = OneclickConfig::getOrderStateIdAfterPayment();
            //si alguno de los datos falta se resetea
            if (
                StringUtils::isNotBlankOrNull($oneclickMallCommerceCode)
                && StringUtils::isNotBlankOrNull($oneclickChildCommerceCode)
                && StringUtils::isNotBlankOrNull($oneclickApikey)
                && StringUtils::isNotBlankOrNull($oneclickDefaultOrderStateIdAfterPayment)
            ) {
                OneclickConfig::setPaymentActive($oneclickActive);
                OneclickConfig::setCommerceCode($oneclickMallCommerceCode);
                OneclickConfig::setChildCommerceCode($oneclickChildCommerceCode);
                OneclickConfig::setApiKey($oneclickApikey);
                OneclickConfig::setOrderStateIdAfterPayment($oneclickDefaultOrderStateIdAfterPayment);
                $this->logInfo("Configuración de ONECLICK se cargo de forma correcta =>
                    oneclickMallCommerceCode: {$oneclickMallCommerceCode}
                    , oneclickChildCommerceCode: {$oneclickChildCommerceCode},
                    , oneclickDefaultOrderStateIdAfterPayment: {$oneclickDefaultOrderStateIdAfterPayment}");
            } else {
                OneclickConfig::loadDefaultConfig();
                $this->logInfo("Configuración por defecto de ONECLICK se cargo de
                    forma correcta porque los valores de producción estan incompletos");
            }
        } else {
            OneclickConfig::loadDefaultConfig();
            $this->logInfo("Configuración por defecto de ONECLICK se cargo de forma correcta");
        }
    }

    protected function getOneclickMallCommerceCode()
    {
        return Configuration::get('ONECLICK_MALL_COMMERCE_CODE');
    }

    protected function getOneclickChildCommerceCode()
    {
        return Configuration::get('ONECLICK_CHILD_COMMERCE_CODE');
    }

    protected function getOneclickApiKey()
    {
        return Configuration::get('ONECLICK_API_KEY');
    }

    protected function getOneclickEnvironment()
    {
        return Configuration::get('ONECLICK_ENVIRONMENT');
    }

    protected function getOneclickOrderAfterPayment()
    {
        return Configuration::get('ONECLICK_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT');
    }

    protected function getOneclickActive()
    {
        return Configuration::get('ONECLICK_ACTIVE');
    }

    protected function setOneclickApiKey($value)
    {
        Configuration::updateValue('ONECLICK_API_KEY', $value);
    }

    protected function setOneclickMallCommerceCode($value)
    {
        Configuration::updateValue('ONECLICK_MALL_COMMERCE_CODE', $value);
    }

    protected function setOneclickChildCommerceCode($value)
    {
        Configuration::updateValue('ONECLICK_CHILD_COMMERCE_CODE', $value);
    }

    protected function setOneclickEnvironment($value)
    {
        Configuration::updateValue('ONECLICK_ENVIRONMENT', $value);
    }

    protected function setOneclickOrderAfterPayment($value)
    {
        Configuration::updateValue('ONECLICK_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT', $value);
    }

    protected function setOneclickActive($value)
    {
        Configuration::updateValue('ONECLICK_ACTIVE', $value);
    }

    protected function getDefaultOneclickMallCommerceCode()
    {
        return Oneclick::DEFAULT_COMMERCE_CODE;
    }

    protected function getDefaultOneclickChildCommerceCode()
    {
        return Oneclick::DEFAULT_CHILD_COMMERCE_CODE_1;
    }

    protected function getDefaultOneclickApiKey()
    {
        return Oneclick::DEFAULT_API_KEY;
    }

    protected function getDefaultOneclickEnvironment()
    {
        return Options::DEFAULT_INTEGRATION_TYPE;
    }

    protected function getDefaultOneclickOrderAfterPayment()
    {
        return Configuration::get('PS_OS_PREPARATION');
    }

    protected function getDefaultOneclickActive()
    {
        return 1;
    }

    protected function loadDefaultOneclick()
    {
        $this->setOneclickActive($this->getDefaultOneclickActive());
        $this->setOneclickMallCommerceCode($this->getDefaultOneclickMallCommerceCode());
        $this->setOneclickChildCommerceCode($this->getDefaultOneclickChildCommerceCode());
        $this->setOneclickApiKey($this->getDefaultOneclickApiKey());
        $this->setOneclickEnvironment($this->getDefaultOneclickEnvironment());
        $this->setOneclickOrderAfterPayment($this->getDefaultOneclickOrderAfterPayment());
    }

    protected function getOneclickOkStatus()
    {
        $OKStatus = Configuration::get('ONECLICK_DEFAULT_ORDER_STATE_ID_AFTER_PAYMENT');
        if ($OKStatus === '0') {
            $OKStatus = Configuration::get('PS_OS_PREPARATION');
        }
        return $OKStatus;
    }

    protected function configOneclickIsOk()
    {
        $oneclickEnviroment = OneclickConfig::getEnvironment();
        $oneclickMallCommerceCode = OneclickConfig::getCommerceCode();
        $oneclickChildCommerceCode = OneclickConfig::getChildCommerceCode();
        $oneclickApikey = OneclickConfig::getApiKey();
        $oneclickDefaultOrderStateIdAfterPayment = OneclickConfig::getOrderStateIdAfterPayment();

        if (
            StringUtils::isNotBlankOrNull($oneclickEnviroment)
            && StringUtils::isNotBlankOrNull($oneclickMallCommerceCode)
            && StringUtils::isNotBlankOrNull($oneclickChildCommerceCode)
            && StringUtils::isNotBlankOrNull($oneclickApikey)
            && StringUtils::isNotBlankOrNull($oneclickDefaultOrderStateIdAfterPayment)
        ) {
            return true;
        }
        return false;
    }

    private function isCustomerLogged(): bool
    {
        return Context::getContext()->customer->isLogged();
    }

    private function isOneclickActivated(): bool
    {
        return $this->getOneclickActive() == TbkConstants::ACTIVE_MODULE;
    }
}
