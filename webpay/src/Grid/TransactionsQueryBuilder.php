<?php

namespace PrestaShop\Module\WebpayPlus\Grid;

use PrestaShop\PrestaShop\Core\Grid\Query\AbstractDoctrineQueryBuilder;
use Doctrine\DBAL\Connection;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface;
use PrestaShop\Module\WebpayPlus\Model\TransbankWebpayRestTransaction;
use Doctrine\DBAL\Query\QueryBuilder;

final class TransactionsQueryBuilder extends AbstractDoctrineQueryBuilder
{
    /**
     * @var int
     */
    private $contextLangId;

    /**
     * @var int
     */
    private $contextShopId;

    /**
     * @param Connection $connection
     * @param string $dbPrefix
     * @param int $contextLangId
     * @param int $contextShopId
     */
    public function __construct(Connection $connection, $dbPrefix, $contextLangId, $contextShopId)
    {
        parent::__construct($connection, $dbPrefix);

        $this->contextLangId = $contextLangId;
        $this->contextShopId = $contextShopId;
    }

    /**
     * Apply filters to address query builder.
     *
     * @param array $filters
     * @param QueryBuilder $queryBuilder
     */
    private function applyFilters(QueryBuilder $queryBuilder, array $filters)
    {
        $allowedFiltersMap = [
            'cart_id' => 'trx.cart_id',
            'order_id' => 'trx.order_id',
            'buy_order' => 'trx.buy_order',
            'response_code' => 'trx.response_code',
            'vci' => 'trx.vci',
            'amount' => 'trx.amount',
            'iso_code' => 'c.iso_code',
            'card_number' => 'trx.card_number',
            'status' => 'trx.status',
            'created_at' => 'trx.created_at',
            'token' => 'trx.token',
            'environment' => 'trx.environment',
            'product' => 'trx.product'
        ];

        foreach ($filters as $filterName => $value) {
            if (array_key_exists($filterName, $allowedFiltersMap) && !empty($value)) {
                if ($filterName == "created_at") {
                    if (isset($value["from"])) {
                        $queryBuilder->andWhere('trx.created_at >= :from')
                            ->setParameter('from', $value["from"]);
                    }
                    if (isset($value["to"])) {
                        $queryBuilder->andWhere('trx.created_at <= :to')
                            ->setParameter('to', $value["to"]);
                    }
                    continue;
                }

                $queryBuilder->andWhere($allowedFiltersMap[$filterName] . ' LIKE :' . $filterName)
                    ->setParameter($filterName, '%' . $value . '%');
            }
        }
    }

    public function getSearchQueryBuilder(SearchCriteriaInterface $searchCriteria)
    {
        $queryBuilder = $this->getBaseQuery($searchCriteria);
        $caseStatus = '
        CASE
        WHEN status = ' . TransbankWebpayRestTransaction::STATUS_INITIALIZED . ' THEN "Inicializada"
        WHEN status = ' . TransbankWebpayRestTransaction::STATUS_FAILED . ' THEN "Fallida"
        WHEN status = ' . TransbankWebpayRestTransaction::STATUS_ABORTED_BY_USER . ' THEN "Cancelada por el usuario"
        WHEN status = ' . TransbankWebpayRestTransaction::STATUS_APPROVED . ' THEN "Aprobada"
        WHEN status = ' . TransbankWebpayRestTransaction::STATUS_ERROR . ' THEN "Error en formulario"
        WHEN status = ' . TransbankWebpayRestTransaction::STATUS_TIMEOUT . ' THEN "Timeout"
        ELSE status
        END';

        $caseColor = '
        CASE
        WHEN status = ' . TransbankWebpayRestTransaction::STATUS_INITIALIZED . ' THEN "#F4B400"
        WHEN status = ' . TransbankWebpayRestTransaction::STATUS_FAILED . ' THEN "#D32F2F"
        WHEN status = ' . TransbankWebpayRestTransaction::STATUS_ABORTED_BY_USER . ' THEN "#F57C00"
        WHEN status = ' . TransbankWebpayRestTransaction::STATUS_APPROVED . ' THEN "#2E7D32"
        WHEN status = ' . TransbankWebpayRestTransaction::STATUS_ERROR . ' THEN "#FF1744"
        WHEN status = ' . TransbankWebpayRestTransaction::STATUS_TIMEOUT . ' THEN "#6D4C41"
        ELSE "#808080"
        END';

        $caseProduct = '
        CASE
        WHEN product = "' . TransbankWebpayRestTransaction::PRODUCT_WEBPAY_PLUS . '" THEN  "Webpay Plus"
        WHEN product = "' . TransbankWebpayRestTransaction::PRODUCT_WEBPAY_ONECLICK . '" THEN "Webpay Oneclick"
        ELSE product
        END';

        $caseEnvironment = '
        CASE
        WHEN environment = "TEST" THEN "Integración"
        WHEN environment = "LIVE" THEN "Producción"
        ELSE environment
        END';


        $caseVCI = "IF(vci IS NULL or vci = '', '--', vci)";

        $queryBuilder->select('order_id, response_code, (' . $caseVCI . ') as vci, amount, iso_code, card_number, token,
         (' . $caseStatus . ') as status, (' . $caseEnvironment . ') as environment,
         (' . $caseColor . ') as status_color, (' . $caseProduct . ') as product, created_at');

        return $queryBuilder;
    }

    public function getCountQueryBuilder(SearchCriteriaInterface $searchCriteria)
    {
        $queryBuilder = $this->getBaseQuery($searchCriteria);
        $queryBuilder->select('COUNT(*)');

        return $queryBuilder;
    }

    private function getBaseQuery(SearchCriteriaInterface $searchCriteria)
    {

        $filters = $searchCriteria->getFilters();
        $queryBuilder = $this->connection
            ->createQueryBuilder()
            ->from($this->dbPrefix . TransbankWebpayRestTransaction::TABLE_NAME, 'trx')
            ->leftJoin(
                'trx',
                $this->dbPrefix . 'currency',
                'c',
                'trx.`currency_id` = c.`id_currency`'
            );

        $this->applyFilters($queryBuilder, $filters);

        return $queryBuilder;
    }
}
