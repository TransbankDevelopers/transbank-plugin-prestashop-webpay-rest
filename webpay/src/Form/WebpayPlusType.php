<?php

declare(strict_types=1);

namespace PrestaShop\Module\WebpayPlus\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class WebpayPlusType extends TranslatorAwareType
{
    private const PREFIX_WEBPAY_FORM = 'form_webpay';
    private const PREFIX_WEBPAY_BUTTONS = 'webpay_plus_form';

    private CommonWebpayFields $commonFields;

    public function __construct(
        TranslatorInterface $translator,
        array $locales,
        CommonWebpayFields $commonFields
    ) {
        parent::__construct($translator, $locales);
        $this->commonFields = $commonFields;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->commonFields->addHeaderFields($builder, self::PREFIX_WEBPAY_FORM);

        $builder->add(self::PREFIX_WEBPAY_FORM . '_commerce_code', TextType::class, [
            'label' => $this->trans('Código de Comercio', 'Modules.WebpayPlus.Admin'),
            'error_bubbling' => true,
            'constraints' => [
                new NotBlank(),
                new Length(['min' => 12, 'max' => 12]),
            ],
        ]);

        $this->commonFields->addFooterFields($builder, self::PREFIX_WEBPAY_FORM);
        $this->commonFields->addButtons($builder, self::PREFIX_WEBPAY_BUTTONS);
    }
}
