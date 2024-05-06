<?php

namespace PrestaShop\Module\WebpayPlus\Grid;

use PrestaShop\Module\WebpayPlus\Grid\TransactionsGridDefinitionFactory;
use PrestaShop\PrestaShop\Core\Search\Filters;

final class TransactionsFilters extends Filters
{
    protected $filterId = TransactionsGridDefinitionFactory::GRID_ID;

    /**
     * {@inheritdoc}
     */
    public static function getDefaults()
    {
        return [
            'limit' => 10,
            'offset' => 0,
            'orderBy' => 'created_at',
            'sortOrder' => 'asc',
            'filters' => [],
        ];
    }
}
