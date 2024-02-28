<?php

declare(strict_types=1);

namespace PrestaShop\Module\WebpayPlus\Form;

use PrestaShopBundle\Form\Admin\Type\SwitchType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;
use PrestaShop\PrestaShop\Adapter\Configuration;

class DiagnosisType extends TranslatorAwareType
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
            ->add('form_debug_active', SwitchType::class, [
                'label' => $this->trans('Log Detallado', 'Modules.WebpayPlus.Admin'),
                'choices' => [
                    $this->trans('No', 'Modules.WebpayPlus.Admin') => DiagnosisDataConfiguration::IS_INACTIVE,
                    $this->trans('Si', 'Modules.WebpayPlus.Admin') => DiagnosisDataConfiguration::IS_ACTIVE,
                ],
            ]);
    }
}
