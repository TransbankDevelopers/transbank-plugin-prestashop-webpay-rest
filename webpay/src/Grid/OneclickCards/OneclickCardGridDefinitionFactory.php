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

        $columns->add(
            (new LinkColumn('id_customer'))
                ->setName($this->trans('ID Usuario', [], 'Modules.WebpayPlus.Admin'))
                ->setOptions([
                    'field' => 'id_customer',
                    'route' => 'admin_customers_view',
                    'route_param_name' => 'customerId',
                    'route_param_field' => 'id_customer',
                ])
        );

        $this->addDataColumn($columns, 'id_oneclick_card', 'ID Inscripción');
        $this->addDataColumn($columns, 'email', 'Email');
        $this->addDataColumn($columns, 'customer_name', 'Nombre');
        $this->addDataColumn($columns, 'card_type', 'Tipo de tarjeta');
        $this->addDataColumn($columns, 'card_number', 'Número de tarjeta');
        $this->addDataColumn($columns, 'environment', 'Ambiente');

        $columns->add(
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

        $this->addTextFilter($filters, 'id_customer');
        $this->addTextFilter($filters, 'id_oneclick_card');
        $this->addTextFilter($filters, 'email');
        $this->addTextFilter($filters, 'customer_name');
        $this->addTextFilter($filters, 'card_type');
        $this->addTextFilter($filters, 'card_number');

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

    private function addDataColumn(ColumnCollection $columns, string $field, string $label, bool $isSortable = true): void
    {
        $columns->add(
            (new DataColumn($field))
                ->setName($this->trans($label, [], 'Modules.WebpayPlus.Admin'))
                ->setOptions([
                    'field' => $field,
                    'sortable' => $isSortable,
                ])
        );
    }

    private function addTextFilter(FilterCollection $filters, string $field, bool $required = false): void
    {
        $filters->add(
            (new Filter($field, TextType::class))
                ->setAssociatedColumn($field)
                ->setTypeOptions(['required' => $required])
        );
    }
}
