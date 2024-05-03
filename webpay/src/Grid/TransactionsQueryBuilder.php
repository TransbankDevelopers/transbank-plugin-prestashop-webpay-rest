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
     * @param QueryBuilder $qb
     */
    private function applyFilters(QueryBuilder $qb, array $filters)
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
        ];

        foreach ($filters as $filterName => $value) {
            if (!array_key_exists($filterName, $allowedFiltersMap) || empty($value)) {
                continue;
            }

            if ($filterName == "created_at") {
                if (isset($value["from"])) {
                    $qb->andWhere('trx.created_at >= :from')
                        ->setParameter('from', $value["from"]);
                }
                if (isset($value["to"])) {
                    $qb->andWhere('trx.created_at <= :to')
                        ->setParameter('to', $value["to"]);
                }
                continue;
            }

            $qb->andWhere($allowedFiltersMap[$filterName] . ' LIKE :' . $filterName)
                ->setParameter($filterName, '%' . $value . '%');
        }
    }

    public function getSearchQueryBuilder(SearchCriteriaInterface $searchCriteria)
    {
        $qb = $this->getBaseQuery($searchCriteria);
        $caseStatus = '
        CASE
        WHEN status = 1 THEN "Inicializado"
        WHEN status = 2 THEN "Fallo"
        WHEN status = 3 THEN "Abortado por usuario"
        WHEN status = 4 THEN "Aprobado"
        ELSE status
        END';

        $caseColor = '
        CASE
        WHEN status = 1 THEN "#E9DF00"
        WHEN status = 2 THEN "#E50B70"
        WHEN status = 3 THEN "#25B9D7"
        WHEN status = 4 THEN "#16C172"
        ELSE "--"
        END';


        $caseVCI  = "IF(vci IS NULL or vci = '', '--', vci)";

        $qb->select('cart_id, order_id, response_code, (' . $caseVCI . ') as vci, amount, iso_code, card_number, token,
         (' . $caseStatus . ') as status,environment, (' . $caseColor . ') as status_color');

        return $qb;
    }

    public function getCountQueryBuilder(SearchCriteriaInterface $searchCriteria)
    {
        $qb = $this->getBaseQuery($searchCriteria);
        $qb->select('COUNT(*)');

        return $qb;
    }

    private function getBaseQuery(SearchCriteriaInterface $searchCriteria)
    {

        $filters = $searchCriteria->getFilters();
        $qb = $this->connection
            ->createQueryBuilder()
            ->from($this->dbPrefix . TransbankWebpayRestTransaction::TABLE_NAME, 'trx')
            ->leftJoin(
                'trx',
                $this->dbPrefix . 'currency',
                'c',
                'trx.`currency_id` = c.`id_currency`'
            );

        $this->applyFilters($qb, $filters);

        return $qb;
    }
}
