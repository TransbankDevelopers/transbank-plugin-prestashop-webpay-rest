<?php

declare(strict_types=1);

namespace PrestaShop\Module\WebpayPlus\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use PrestaShop\PrestaShop\Adapter\Configuration;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Transbank\Webpay\Options;

class OneclickType extends TranslatorAwareType
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
            ->add('form_oneclick_active', SwitchType::class, [
                'label' => $this->trans('Activo', 'Modules.WebpayPlus.Admin'),
                'choices' => [
                    $this->trans('No', 'Modules.WebpayPlus.Admin') => 2,
                    $this->trans('Si', 'Modules.WebpayPlus.Admin') => 1,
                ],
            ])
            ->add('form_oneclick_environment', SwitchType::class, [
                'label' => $this->trans('Producción', 'Modules.WebpayPlus.Admin'),
                'choices' => [
                    $this->trans('No', 'Modules.WebpayPlus.Admin') => Options::DEFAULT_INTEGRATION_TYPE,
                    $this->trans('Si', 'Modules.WebpayPlus.Admin') => Options::ENVIRONMENT_PRODUCTION,
                ],
                'help' => $this->trans('Cuando no está activado el modo producción, se utilizarán las claves predeterminadas del entorno de pruebas.', 'Modules.WebpayPlus.Admin'),
            ])
            ->add('form_oneclick_mall_commerce_code', TextType::class, [
                'label' => $this->trans('Código de Comercio Mall', 'Modules.WebpayPlus.Admin'),
                'error_bubbling' => true,
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 12, 'max' => 12]),
                ]
            ])
            ->add('form_oneclick_child_commerce_code', TextType::class, [
                'label' => $this->trans('Código de Comercio Tienda', 'Modules.WebpayPlus.Admin'),
                'error_bubbling' => true,
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 12, 'max' => 12]),
                ]
            ])
            ->add('form_oneclick_api_key', PasswordType::class, [
                'label' => $this->trans('API Key (llave secreta)', 'Modules.WebpayPlus.Admin'),
                'error_bubbling' => true,
                'required' => false,
                'constraints' => [
                    new Length(['min' => 12]),
                ],
                'help' => $this->trans('Si no deseas cambiar el API Key, deja este campo vacío.', 'Modules.WebpayPlus.Admin'),
            ])
            ->add('form_oneclick_order_after_payment', ChoiceType::class, [
                'label' => $this->trans('Estado Pago Aceptado', 'Modules.WebpayPlus.Admin'),
                'choices' => [
                    $this->trans('Pago aceptado', 'Modules.WebpayPlus.Admin') => $this->configuration->get('PS_OS_PAYMENT'),
                    $this->trans('Preparación en curso', 'Modules.WebpayPlus.Admin') => $this->configuration->get('PS_OS_PREPARATION'),
                ],
            ])
            ->add('oneclick_form_save_button', SubmitType::class, ['label' => $this->trans('Save', 'Modules.WebpayPlus.Admin')])
            ->add('oneclick_form_reset_button', SubmitType::class, ['label' => $this->trans('Reset', 'Modules.WebpayPlus.Admin')])
        ;
    }
}
