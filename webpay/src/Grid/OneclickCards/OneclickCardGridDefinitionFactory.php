<?php

namespace PrestaShop\Module\WebpayPlus\Grid\OneclickCards;

use PrestaShop\PrestaShop\Core\Grid\Definition\Factory\AbstractFilterableGridDefinitionFactory;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\DataColumn;
use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use PrestaShop\PrestaShop\Core\Grid\Column\ColumnCollection;
use PrestaShop\PrestaShop\Core\Grid\Filter\FilterCollection;
use PrestaShop\PrestaShop\Core\Grid\Filter\FilterCollectionInterface;
use PrestaShopBundle\Form\Admin\Type\SearchAndResetType;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\ActionColumn;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\RowActionCollection;
use PrestaShop\PrestaShop\Core\Grid\Action\Row\Type\SubmitRowAction;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\LinkColumn;

final class OneclickCardGridDefinitionFactory extends AbstractFilterableGridDefinitionFactory
{
    public const GRID_ID = 'oneclick_card_list';
    protected function getId(): string
    {
        return self::GRID_ID;
    }

    protected function getName(): string
    {
        return $this->trans(
            'Tarjetas Oneclick',
            [],
            'Modules.WebpayPlus.Admin'
        );
    }

    protected function getColumns(): ColumnCollection
    {
        $columns = new ColumnCollection();

        $columns
            ->add(
                (new LinkColumn('id_customer'))
                    ->setName($this->trans('ID Usuario', [], 'Modules.WebpayPlus.Admin'))
                    ->setOptions([
                        'field' => 'id_customer',
                        'route' => 'admin_customers_view',
                        'route_param_name' => 'customerId',
                        'route_param_field' => 'id_customer',
                    ])
            )
            ->add(
                (new DataColumn('id_oneclick_card'))
                    ->setName($this->trans('ID Inscripción', [], 'Modules.WebpayPlus.Admin'))
                    ->setOptions([
                        'field' => 'id_oneclick_card',
                        'sortable' => true
                    ])
            )
            ->add(
                (new DataColumn('email'))
                    ->setName($this->trans('Email', [], 'Modules.WebpayPlus.Admin'))
                    ->setOptions([
                        'field' => 'email',
                        'sortable' => true
                    ])
            )
            ->add(
                (new DataColumn('customer_name'))
                    ->setName($this->trans('Nombre', [], 'Modules.WebpayPlus.Admin'))
                    ->setOptions([
                        'field' => 'customer_name',
                        'sortable' => true
                    ])
            )
            ->add(
                (new DataColumn('card_type'))
                    ->setName($this->trans('Tipo de tarjeta', [], 'Modules.WebpayPlus.Admin'))
                    ->setOptions([
                        'field' => 'card_type',
                        'sortable' => true
                    ])
            )
            ->add(
                (new DataColumn('card_number'))
                    ->setName($this->trans('Número de tarjeta', [], 'Modules.WebpayPlus.Admin'))
                    ->setOptions([
                        'field' => 'card_number',
                        'sortable' => true
                    ])
            )
            ->add(
                (new DataColumn('environment'))
                    ->setName($this->trans('Ambiente', [], 'Modules.WebpayPlus.Admin'))
                    ->setOptions([
                        'field' => 'environment',
                        'sortable' => true
                    ])
            )
            ->add(
                (new ActionColumn('actions'))
                    ->setName($this->trans('Acciones', [], 'Modules.WebpayPlus.Admin'))
                    ->setOptions([
                        'actions' => (new RowActionCollection())
                            ->add(
                                (new SubmitRowAction('delete'))
                                    ->setName($this->trans('Eliminar', [], 'Modules.WebpayPlus.Admin'))
                                    ->setIcon('delete')
                                    ->setOptions([
                                        'method' => 'POST',
                                        'route' => 'ps_controller_webpay_oneclick_card_delete',
                                        'route_param_name' => 'cardId',
                                        'route_param_field' => 'id_oneclick_card',
                                        'extra_route_params' => [
                                            'customerId' => 'id_customer',
                                        ],
                                        'confirm_message' => $this->trans(
                                            '¿Está seguro/a que deseas eliminar esta inscripción?',
                                            [],
                                            'Modules.WebpayPlus.Admin'
                                        ),
                                    ])
                            )
                    ])
            );

        return $columns;
    }

    protected function getFilters(): FilterCollectionInterface
    {
        $filters = new FilterCollection();

        $filters->add(
            (new Filter('id_customer', TextType::class))
                ->setAssociatedColumn('id_customer')
                ->setTypeOptions(['required' => false])
        );

        $filters->add(
            (new Filter('id_oneclick_card', TextType::class))
                ->setAssociatedColumn('id_oneclick_card')
                ->setTypeOptions(['required' => false])
        );

        $filters->add(
            (new Filter('email', TextType::class))
                ->setAssociatedColumn('email')
                ->setTypeOptions(['required' => false])
        );

        $filters->add(
            (new Filter('customer_name', TextType::class))
                ->setAssociatedColumn('customer_name')
                ->setTypeOptions(['required' => false])
        );

        $filters->add(
            (new Filter('card_type', TextType::class))
                ->setAssociatedColumn('card_type')
                ->setTypeOptions(['required' => false])
        );

        $filters->add(
            (new Filter('card_number', TextType::class))
                ->setAssociatedColumn('card_number')
                ->setTypeOptions(['required' => false])
        );

        $filters->add(
            (new Filter('actions', SearchAndResetType::class))
                ->setAssociatedColumn('actions')
                ->setTypeOptions([
                    'reset_route' => 'admin_common_reset_search_by_filter_id',
                    'reset_route_params' => [
                        'filterId' => self::GRID_ID,
                    ],
                    'redirect_route' => 'ps_controller_webpay_oneclick_cards_list',
                ])
        );

        return $filters;
    }
}
