<?php


namespace PrestaShop\Module\WebpayPlus\Grid\OneclickCards;

use PrestaShop\PrestaShop\Core\Grid\Data\Factory\GridDataFactoryInterface;
use PrestaShop\PrestaShop\Core\Grid\Data\GridData;
use PrestaShop\PrestaShop\Core\Grid\Record\RecordCollection;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface;

final class OneclickCardGridDataFactory implements GridDataFactoryInterface
{
    private OneclickCardQueryBuilder $queryBuilder;

    public function __construct(OneclickCardQueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    public function getData(SearchCriteriaInterface $searchCriteria): GridData
    {
        $searchQueryBuilder = $this->queryBuilder->getSearchQueryBuilder($searchCriteria);
        $countQueryBuilder  = $this->queryBuilder->getCountQueryBuilder($searchCriteria);

        $records = $searchQueryBuilder->execute()->fetchAllAssociative();
        $recordsTotal = (int) $countQueryBuilder->execute()->fetchOne();

        return new GridData(
            new RecordCollection($records),
            $recordsTotal,
            $searchCriteria
        );
    }
}
