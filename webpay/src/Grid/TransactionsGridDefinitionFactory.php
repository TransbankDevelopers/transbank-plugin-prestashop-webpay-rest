<?php

namespace PrestaShop\Module\WebpayPlus\Grid;

use PrestaShop\Module\WebpayPlus\Core\Grid\Column\Type\CustomLinkColumn;
use PrestaShop\PrestaShop\Core\Grid\Definition\Factory\AbstractFilterableGridDefinitionFactory;
use PrestaShop\PrestaShop\Core\Grid\Column\ColumnCollection;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\DataColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\DateTimeColumn;
use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
use PrestaShop\PrestaShop\Core\Grid\Filter\FilterCollection;
use PrestaShop\PrestaShop\Core\Grid\Filter\FilterCollectionInterface;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use PrestaShopBundle\Form\Admin\Type\DateRangeType;
use PrestaShopBundle\Form\Admin\Type\SearchAndResetType;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\ActionColumn;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\ColorColumn;
use PrestaShop\Module\WebpayPlus\Grid\TransactionsStatusChoiceProvider;


final class TransactionsGridDefinitionFactory extends AbstractFilterableGridDefinitionFactory
{
    const GRID_ID = 'webpay_transactions';

    protected function getId()
    {
        return self::GRID_ID;
    }

    protected function getName()
    {
        return 'Transacciones';
    }

    protected function getColumns()
    {
        $gridColumns = $this->getGridColumnsData();
        $columnCollection = new ColumnCollection();

        $columnActions = [
            'actions' => function ($key, $columnName) use ($columnCollection) {
                $columnCollection->add(
                    (new ActionColumn($key))->setName($columnName)
                );
            },
            'status' => function ($key, $columnName) use ($columnCollection) {
                $columnCollection->add(
                    (new ColorColumn($key))
                        ->setName($columnName)
                        ->setOptions([
                            'field' => $key,
                            'color_field' => 'status_color',
                        ])
                );
            },
            'created_at' => function ($key, $columnName) use ($columnCollection) {
                $columnCollection->add(
                    (new DateTimeColumn($key))
                        ->setName($columnName)
                        ->setOptions(['field' => $key])
                );
            },
            'order_id' => function ($key, $columnName) use ($columnCollection) {
                $columnCollection->add(
                    (new CustomLinkColumn($key))
                        ->setName($columnName)
                        ->setOptions([
                            'field' => $key,
                            'route' => 'admin_orders_view',
                            'route_param_name' => 'orderId',
                            'route_param_field' => $key,
                        ])
                );
            }
        ];

        foreach ($gridColumns as $key => $columnName) {
            if (isset($columnActions[$key])) {
                $columnActions[$key]($key, $columnName);
            } else {

                $columnCollection->add((new DataColumn($key))
                        ->setName($columnName)
                        ->setOptions(['field' => $key])
                );
            }
        }

        return $columnCollection;
    }

    protected function getFilters(): FilterCollectionInterface
    {
        $gridColumns = $this->getGridColumnsData();
        $filterCollection = new FilterCollection();

        $filterActions = [
            'amount' => function ($key) use ($filterCollection) {
                $filterCollection->add(
                    (new Filter($key, NumberType::class))
                        ->setAssociatedColumn($key)
                        ->setTypeOptions(['required' => false])
                );
            },
            'status' => function ($key) use ($filterCollection) {
                $filterCollection->add(
                    (new Filter($key, ChoiceType::class))
                        ->setAssociatedColumn($key)
                        ->setTypeOptions([
                            'choices' => (new TransactionsStatusChoiceProvider())->getChoices(),
                            'required' => false
                        ])
                );
            },
            'environment' => function ($key) use ($filterCollection) {
                $filterCollection->add(
                    (new Filter($key, ChoiceType::class))
                        ->setAssociatedColumn($key)
                        ->setTypeOptions([
                            'choices' => (new TransactionsEnvironmentChoiceProvider())->getChoices(),
                            'required' => false
                        ])
                );
            },
            'product' => function ($key) use ($filterCollection) {
                $filterCollection->add(
                    (new Filter($key, ChoiceType::class))
                        ->setAssociatedColumn($key)
                        ->setTypeOptions([
                            'choices' => (new TransactionsProductChoiceProvider())->getChoices(),
                            'required' => false
                        ])
                );
            },
            'created_at' => function ($key) use ($filterCollection) {
                $filterCollection->add(
                    (new Filter($key, DateRangeType::class))
                        ->setAssociatedColumn($key)
                        ->setTypeOptions(['required' => false])
                );
            },
            'actions' => function ($key) use ($filterCollection) {
                $filterCollection->add(
                    (new Filter($key, SearchAndResetType::class))
                        ->setAssociatedColumn($key)
                        ->setTypeOptions([
                            'reset_route' => 'admin_common_reset_search_by_filter_id',
                            'reset_route_params' => [
                                'filterId' => self::GRID_ID,
                            ],
                            'redirect_route' => 'ps_controller_webpay_transaction_list',
                        ])
                );
            }
        ];

        foreach ($gridColumns as $key => $columnName) {
            if (isset($filterActions[$key])) {
                $filterActions[$key]($key);
            } else {
                $filterCollection->add(
                    (new Filter($key, TextType::class))
                        ->setAssociatedColumn($key)
                        ->setTypeOptions(['required' => false])
                );
            }
        }

        return $filterCollection;
    }

    private function getGridColumnsData(): array
    {
        return [
            'order_id' => 'Id del pedido',
            'response_code' => 'Código de respuesta',
            'vci' => 'VCI',
            'amount' => 'Monto',
            'iso_code' => 'Moneda',
            'card_number' => '4 últimos dígitos tarjeta',
            'token' => 'Token',
            'status' => 'Estado',
            'environment' => 'Ambiente',
            'product' => 'Producto',
            'created_at' => 'Fecha transacción',
            'actions' => 'Acciones',
        ];
    }
}
