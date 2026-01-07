<?php

namespace PrestaShop\Module\WebpayPlus\Grid\OneclickCards;

use Doctrine\DBAL\Connection;
use PrestaShop\PrestaShop\Core\Grid\Query\AbstractDoctrineQueryBuilder;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface;
use PrestaShop\Module\WebpayPlus\Config\OneclickConfig;

final class OneclickCardQueryBuilder extends AbstractDoctrineQueryBuilder
{
    public function __construct(
        Connection $connection,
        string $dbPrefix
    ) {
        parent::__construct($connection, $dbPrefix);
    }

    public function getSearchQueryBuilder(SearchCriteriaInterface $searchCriteria)
    {
        $currentEnvironment = OneclickConfig::getEnvironment();
        $qb = $this->connection->createQueryBuilder();

        $qb
            ->select([
                'ins.id AS id_oneclick_card',
                'ins.user_id AS id_customer',
                'c.email',
                "CONCAT(c.firstname, ' ', c.lastname) AS customer_name",
                'ins.card_type',
                'ins.card_number',
                'ins.environment',
            ])
            ->from($this->dbPrefix . 'transbank_inscriptions', 'ins')
            ->where('ins.finished = 1')
            ->andWhere('ins.environment = :current_environment')
            ->setParameter('current_environment', $currentEnvironment)
            ->leftJoin('ins', $this->dbPrefix . 'customer', 'c', 'c.id_customer = ins.user_id');

        $this->applyFilters($qb, $searchCriteria);

        if ($searchCriteria->getOrderBy()) {
            $orderBy = $searchCriteria->getOrderBy();
            $orderWay = $searchCriteria->getOrderWay();

            $orderByMapping = [
                'id_oneclick_card' => 'ins.id',
                'id_customer' => 'ins.user_id',
                'email' => 'c.email',
                'customer_name' => 'customer_name',
                'card_type' => 'ins.card_type',
                'card_number' => 'ins.card_number',
                'environment' => 'ins.environment',
            ];

            $orderByField = $orderByMapping[$orderBy] ?? 'ins.id';
            $qb->orderBy($orderByField, $orderWay);
        } else {
            $qb->orderBy('ins.id', 'DESC');
        }

        $qb
            ->setFirstResult($searchCriteria->getOffset())
            ->setMaxResults($searchCriteria->getLimit());

        return $qb;
    }

    public function getCountQueryBuilder(SearchCriteriaInterface $searchCriteria)
    {
        $qb = $this->connection->createQueryBuilder();

        $qb
            ->select('COUNT(ins.id)')
            ->from($this->dbPrefix . 'transbank_inscriptions', 'ins')
            ->where('ins.finished = 1')
            ->leftJoin('ins', $this->dbPrefix . 'customer', 'c', 'c.id_customer = ins.user_id');

        $this->applyFilters($qb, $searchCriteria);

        return $qb;
    }

    private function applyFilters($qb, SearchCriteriaInterface $searchCriteria): void
    {
        foreach ($searchCriteria->getFilters() as $filterName => $filterData) {

            if (is_array($filterData) && array_key_exists('value', $filterData)) {
                $value = $filterData['value'];
            } else {
                $value = $filterData;
            }

            if ($value === null || $value === '') {
                continue;
            }

            switch ($filterName) {
                case 'id_customer':
                    $qb
                        ->andWhere('ins.user_id = :id_customer')
                        ->setParameter('id_customer', (int) $value);
                    break;

                case 'id_oneclick_card':
                    $qb
                        ->andWhere('ins.id = :id_oneclick_card')
                        ->setParameter('id_oneclick_card', (int) $value);
                    break;

                case 'email':
                    $qb
                        ->andWhere('c.email LIKE :email')
                        ->setParameter('email', '%' . $value . '%');
                    break;

                case 'customer_name':
                    $qb
                        ->andWhere("CONCAT(c.firstname, ' ', c.lastname) LIKE :customer_name")
                        ->setParameter('customer_name', '%' . $value . '%');
                    break;

                case 'card_type':
                    $qb
                        ->andWhere('ins.card_type LIKE :card_type')
                        ->setParameter('card_type', '%' . $value . '%');
                    break;

                case 'card_number':
                    $qb
                        ->andWhere('ins.card_number LIKE :card_number')
                        ->setParameter('card_number', '%' . $value . '%');
                    break;

                case 'environment':
                    $qb
                        ->andWhere('ins.environment = :environment')
                        ->setParameter('environment', $value);
                    break;
            }
        }
    }
}
