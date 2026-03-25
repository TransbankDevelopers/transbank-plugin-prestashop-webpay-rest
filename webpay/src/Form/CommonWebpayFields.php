<?php

declare(strict_types=1);

namespace PrestaShop\Module\WebpayPlus\Form;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use PrestaShop\PrestaShop\Adapter\Configuration;
use Symfony\Component\Validator\Constraints\Length;
use Transbank\Webpay\Options;


class CommonWebpayFields
{
    private TranslatorInterface $translator;
    private Configuration $configuration;
    
    public function __construct(
        TranslatorInterface $translator,
        Configuration $configuration
    ) {
        $this->translator = $translator;
        $this->configuration = $configuration;
    }

    /**
     * Adds shared header fields: active + environment.
     * Must be called BEFORE the specific commerce code fields.
     */
    public function addHeaderFields(FormBuilderInterface $builder, string $prefix): void
    {
        $builder
            ->add("{$prefix}_active", SwitchType::class, [
                'label' => $this->translator->trans('Activo', [], 'Modules.WebpayPlus.Admin'),
                'choices' => [
                    $this->translator->trans('No', [], 'Modules.WebpayPlus.Admin') => 2,
                    $this->translator->trans('Si', [], 'Modules.WebpayPlus.Admin') => 1,
                ],
            ])
            ->add("{$prefix}_environment", SwitchType::class, [
                'label' => $this->translator->trans('Producción', [], 'Modules.WebpayPlus.Admin'),
                'choices' => [
                    $this->translator->trans('No', [], 'Modules.WebpayPlus.Admin') => Options::ENVIRONMENT_INTEGRATION,
                    $this->translator->trans('Si', [], 'Modules.WebpayPlus.Admin') => Options::ENVIRONMENT_PRODUCTION,
                ],
                'help' => $this->translator->trans(
                    'Cuando no está activado el modo producción, se utilizarán las claves predeterminadas del entorno de pruebas.',
                    [],
                    'Modules.WebpayPlus.Admin'
                ),
            ]);
    }

    /**
     * Adds shared footer fields: api_key + order_after_payment.
     * Must be called AFTER the specific commerce code fields.
     */
    public function addFooterFields(FormBuilderInterface $builder, string $prefix): void
    {
        $builder
            ->add("{$prefix}_api_key", PasswordType::class, [
                'label' => $this->translator->trans('API Key (llave secreta)', [], 'Modules.WebpayPlus.Admin'),
                'error_bubbling' => true,
                'required' => false,
                'constraints' => [
                    new Length(['min' => 12]),
                ],
                'help' => $this->translator->trans(
                    'Si no deseas cambiar el API Key, deja este campo vacío.',
                    [],
                    'Modules.WebpayPlus.Admin'
                ),
            ])
            ->add("{$prefix}_order_after_payment", ChoiceType::class, [
                'label' => $this->translator->trans('Estado Pago Aceptado', [], 'Modules.WebpayPlus.Admin'),
                'choices' => [
                    $this->translator->trans('Pago aceptado', [], 'Modules.WebpayPlus.Admin') => $this->configuration->get('PS_OS_PAYMENT'),
                    $this->translator->trans('Preparación en curso', [], 'Modules.WebpayPlus.Admin') => $this->configuration->get('PS_OS_PREPARATION'),
                ],
            ]);
    }

    /**
     * Adds Save and Reset submit buttons.
     */
    public function addButtons(FormBuilderInterface $builder, string $buttonPrefix): void
    {
        $builder
            ->add("{$buttonPrefix}_save_button", SubmitType::class, [
                'label' => $this->translator->trans('Save', [], 'Modules.WebpayPlus.Admin'),
            ])
            ->add("{$buttonPrefix}_reset_button", SubmitType::class, [
                'label' => $this->translator->trans('Reset', [], 'Modules.WebpayPlus.Admin'),
            ]);
    }
}
