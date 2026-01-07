<?php

namespace PrestaShop\Module\WebpayPlus\Grid\OneclickCards;

use PrestaShop\Module\WebpayPlus\Grid\OneclickCards\OneclickCardGridDefinitionFactory;
use PrestaShop\PrestaShop\Core\Search\Filters;

final class OneclickCardsFilters extends Filters
{
    protected $filterId = OneclickCardGridDefinitionFactory::GRID_ID;

    /**
     * {@inheritdoc}
     */
    public static function getDefaults()
    {
        return [
            'limit' => 10,
            'offset' => 0,
            'orderBy' => 'id_customer',
            'sortOrder' => 'desc',
            'filters' => [],
        ];
    }
}
