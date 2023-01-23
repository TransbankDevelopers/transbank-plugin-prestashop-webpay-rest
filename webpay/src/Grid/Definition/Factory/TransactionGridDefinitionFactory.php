<?php

declare(strict_types=1);

namespace PrestaShop\Module\WebpayPlus\Grid\Definition\Factory;

use PrestaShop\PrestaShop\Core\Grid\Column\ColumnCollection;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\ActionColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn;
use PrestaShop\PrestaShop\Core\Grid\Definition\Factory\AbstractGridDefinitionFactory;
use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
use PrestaShop\PrestaShop\Core\Grid\Filter\FilterCollection;
use PrestaShopBundle\Form\Admin\Type\SearchAndResetType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class TransactionGridDefinitionFactory extends AbstractGridDefinitionFactory
{
    const GRID_ID = 'transaction';

    /**
     * {@inheritdoc}
     */
    protected function getId()
    {
        return self::GRID_ID;
    }

    /**
     * {@inheritdoc}
     */
    protected function getName()
    {
        return $this->trans('Transactions', [], 'Modules.WebpayPlus.Admin');
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumns()
    {
        return (new ColumnCollection())
            ->add(
                (new DataColumn('id'))
                    ->setOptions([
                        'field' => 'id',
                    ])
            )
            ->add(
                (new DataColumn('client'))
                    ->setName($this->trans('Client', [], 'Modules.WebpayPlus.Admin'))
                    ->setOptions([
                        'field' => 'client',
                    ])
            )
            ->add(
                (new DataColumn('order_status'))
                    ->setName($this->trans('Order Status', [], 'Modules.WebpayPlus.Admin'))
                    ->setOptions([
                        'field' => 'order_status',
                    ])
            )
            ->add(
                (new DataColumn('amount'))
                    ->setName($this->trans('Amount', [], 'Modules.WebpayPlus.Admin'))
                    ->setOptions([
                        'field' => 'amount',
                    ])
            )
            ->add(
                (new DataColumn('buy_order'))
                    ->setName($this->trans('Buy Order', [], 'Modules.WebpayPlus.Admin'))
                    ->setOptions([
                        'field' => 'buy_order',
                    ])
            )
            ->add(
                (new DataColumn('product'))
                    ->setName($this->trans('Product', [], 'Modules.WebpayPlus.Admin'))
                    ->setOptions([
                        'field' => 'product',
                    ])
            )
            ->add(
                (new ActionColumn('actions'))
                    ->setName($this->trans('Actions', [], 'Admin.Actions'))
            );
    }


    /**
     * {@inheritdoc}
     */
    protected function getFilters()
    {
        return (new FilterCollection())
            ->add(
                (new Filter('id', TextType::class))
                    ->setTypeOptions([
                        'required' => false,
                        'attr' => [
                            'placeholder' => $this->trans('ID', [], 'Admin.Global'),
                        ],
                    ])
                    ->setAssociatedColumn('id')
            )
        ;
    }
}
