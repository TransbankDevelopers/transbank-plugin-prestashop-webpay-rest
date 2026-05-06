<?php

declare(strict_types=1);

namespace PrestaShop\Module\WebpayPlus\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class OneclickType extends TranslatorAwareType
{
    private const PREFIX_ONE_CLICK_FORM = 'form_oneclick';
    private const PREFIX_ONE_CLICK_BUTTONS = 'oneclick_form';

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
        $this->commonFields->addHeaderFields($builder, self::PREFIX_ONE_CLICK_FORM);

        $builder
            ->add(self::PREFIX_ONE_CLICK_FORM . '_mall_commerce_code', TextType::class, [
                'label' => $this->trans('Código de Comercio Mall', 'Modules.WebpayPlus.Admin'),
                'error_bubbling' => true,
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 12, 'max' => 12]),
                ],
            ])
            ->add(self::PREFIX_ONE_CLICK_FORM . '_child_commerce_code', TextType::class, [
                'label' => $this->trans('Código de Comercio Tienda', 'Modules.WebpayPlus.Admin'),
                'error_bubbling' => true,
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 12, 'max' => 12]),
                ],
            ]);

        $this->commonFields->addFooterFields($builder, self::PREFIX_ONE_CLICK_FORM);
        $this->commonFields->addButtons($builder, self::PREFIX_ONE_CLICK_BUTTONS);
    }
}
