<?php

declare(strict_types=1);

namespace PrestaShop\Module\WebpayPlus\Grid\Query;

use PrestaShop\Module\WebpayPlus\Model\TransbankWebpayRestTransaction;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShop\PrestaShop\Core\Grid\Query\AbstractDoctrineQueryBuilder;
use PrestaShop\PrestaShop\Core\Grid\Query\DoctrineSearchCriteriaApplicatorInterface;
use PrestaShop\PrestaShop\Core\Grid\Query\Filter\DoctrineFilterApplicatorInterface;
use PrestaShop\PrestaShop\Core\Grid\Query\Filter\SqlFilters;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface;

/**
 * Defines all required sql statements to render transactions list.
 */
class TransactionQueryBuilder extends AbstractDoctrineQueryBuilder
{
    /**
     * @var DoctrineSearchCriteriaApplicatorInterface
     */
    private $searchCriteriaApplicator;

    /**
     * @var int
     */
    private $contextLanguageId;

    /**
     * @var int
     */
    private $contextShopId;

    /**
     * @var bool
     */
    private $isStockSharingBetweenShopGroupEnabled;

    /**
     * @var int
     */
    private $contextShopGroupId;

    /**
     * @var DoctrineFilterApplicatorInterface
     */
    private $filterApplicator;

    /**
     * @var Configuration
     */
    private $configuration;

    public function __construct(
        Connection $connection,
        string $dbPrefix,
        DoctrineSearchCriteriaApplicatorInterface $searchCriteriaApplicator,
        int $contextLanguageId,
        int $contextShopId,
        int $contextShopGroupId,
        bool $isStockSharingBetweenShopGroupEnabled,
        DoctrineFilterApplicatorInterface $filterApplicator,
        Configuration $configuration
    ) {
        parent::__construct($connection, $dbPrefix);
        $this->searchCriteriaApplicator = $searchCriteriaApplicator;
        $this->contextLanguageId = $contextLanguageId;
        $this->contextShopId = $contextShopId;
        $this->isStockSharingBetweenShopGroupEnabled = $isStockSharingBetweenShopGroupEnabled;
        $this->contextShopGroupId = $contextShopGroupId;
        $this->filterApplicator = $filterApplicator;
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchQueryBuilder(SearchCriteriaInterface $searchCriteria): QueryBuilder
    {
        $qb = $this->getQueryBuilder($searchCriteria->getFilters());
        $qb
            ->select('t.`id`, t.`cart_id`, t.`order_id`, t.`amount`, t.`buy_order`, t.`token`, t.`transbank_response`, t.`response_code`, t.`commerce_code`, t.`child_commerce_code`, t.`product`, t.`environment`, t.`created_at`')
            ->addSelect('CONCAT(cu.`firstname`, " ", cu.`lastname`) AS `client`, cu.`email`')
            ->addSelect('o.`reference`, o.`current_state` AS `order_status_id`, o.`total_paid`, o.`invoice_date`')
            ->addSelect('os.`name` AS `order_status`')
        ;

        $this->searchCriteriaApplicator
            ->applyPagination($searchCriteria, $qb)
            ->applySorting($searchCriteria, $qb)
        ;

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    public function getCountQueryBuilder(SearchCriteriaInterface $searchCriteria): QueryBuilder
    {
        $qb = $this->getQueryBuilder($searchCriteria->getFilters());
        $qb->select('COUNT(t.`id`)');

        return $qb;
    }

    /**
     * Gets query builder.
     *
     * @param array $filterValues
     *
     * @return QueryBuilder
     */
    private function getQueryBuilder(array $filterValues): QueryBuilder
    {
        $qb = $this->connection
        ->createQueryBuilder()
        ->from($this->dbPrefix . TransbankWebpayRestTransaction::TABLE_NAME, 't')
        ->leftJoin(
            't',
            $this->dbPrefix . 'orders',
            'o',
            'o.`id_order` = t.`order_id` AND o.`module` = "webpay"',  
        )
        ->leftJoin(
            'o',
            $this->dbPrefix . 'customer',
            'cu',
            'cu.`id_customer` = o.`id_customer`'
        )
        ->leftJoin(
            'o',
            $this->dbPrefix . 'order_state_lang',
            'os',
            'os.`id_order_state` = o.`current_state` AND os.`id_lang` = o.`id_lang`'
        );

        return $qb;
    }
}
