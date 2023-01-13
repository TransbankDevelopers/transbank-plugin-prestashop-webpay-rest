<?php

declare(strict_types=1);

namespace PrestaShop\Module\WebpayPlus\Grid\Filters;

use PrestaShop\Module\WebpayPlus\Grid\Definition\Factory\TransactionGridDefinitionFactory;
use PrestaShop\PrestaShop\Core\Search\Filters;

class TransactionFilters extends Filters
{
    protected $filterId = TransactionGridDefinitionFactory::GRID_ID;

    /**
     * {@inheritdoc}
     */
    public static function getDefaults()
    {
        return [
            'limit' => 10,
            'offset' => 0,
            'orderBy' => 'id',
            'sortOrder' => 'asc',
            'filters' => [],
        ];
    }
}
