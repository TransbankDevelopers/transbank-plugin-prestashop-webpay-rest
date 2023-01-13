<?php

declare(strict_types=1);

namespace PrestaShop\Module\WebpayPlus\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;
use PrestaShop\PrestaShop\Adapter\Configuration;
use Transbank\Webpay\Options;

class WebpayPlusType extends TranslatorAwareType
{

    /**
     * @var Configuration
     */
    private $configuration;

    public function __construct(
        TranslatorInterface $translator, 
        array $locales,
        Configuration $configuration
    ) {
        parent::__construct($translator, $locales);
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('form_webpay_environment', SwitchType::class, [
                'label' => $this->trans('Producción', 'Modules.WebpayPlus.Admin'),
                'choices' => [
                    $this->trans('No', 'Modules.WebpayPlus.Admin') => Options::DEFAULT_INTEGRATION_TYPE,
                    $this->trans('Si', 'Modules.WebpayPlus.Admin') => Options::ENVIRONMENT_PRODUCTION,
                ],
            ])
            ->add('form_webpay_commerce_code', TextType::class, [
                'label' => $this->trans('Código de Comercio', 'Modules.WebpayPlus.Admin'),
            ])
            ->add('form_webpay_api_key', TextType::class, [
                'label' => $this->trans('API Key (llave secreta)', 'Modules.WebpayPlus.Admin'),
            ])
            ->add('form_webpay_order_after_payment', ChoiceType::class, [
                'label' => $this->trans('Estado Pago Aceptado', 'Modules.WebpayPlus.Admin'),
                'choices' => [
                    $this->trans('Pago aceptado', 'Modules.WebpayPlus.Admin') => $this->configuration->get('PS_OS_PAYMENT'),
                    $this->trans('Preparación en curso', 'Modules.WebpayPlus.Admin') => $this->configuration->get('PS_OS_PREPARATION'),
                ],
            ]);
    }
}
