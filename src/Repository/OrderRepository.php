<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order[]    findAll()
 * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Order $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Order $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @param array $param
     * @param $orderBy
     * @param $limit
     * @param $offset
     * @return array
     */
    public function findByConditions(array $param,?array $orderBy = [], ?int $limit = null, ?int $offset = null): array
    {
        $queryBuilder = $this->createQueryBuilder('o')
            ->andWhere('o.deletedAt IS NULL');

        if (isset($param['customer']) && $param['customer'] != '') {
            $queryBuilder
                ->andWhere('o.customer = :customerId')
                ->setParameter('customerId', $param['customer']);
        }

        if (isset($param['status']) && $param['status'] != 0) {
            $queryBuilder
                ->andWhere('o.status = :status')
                ->setParameter('status', $param['status']);
        }

        if (isset($param['fromDate']) && $param['fromDate'] != '') {
            $queryBuilder
                ->andWhere('o.createdAt >= :fromDate')
                ->setParameter('fromDate', $param['fromDate']);
        }

        if (isset($param['toDate']) && $param['toDate'] != '') {
            $queryBuilder
                ->andWhere('o.createdAt <= :toDate')
                ->setParameter('toDate', $param['toDate']);
        }
        if (!empty($orderBy)) {
            $keyOrderList = array_keys($orderBy);
            foreach ($keyOrderList as $keyOrder) {
                $column = 'o.' . $keyOrder;
                $valueSort = $orderBy[$keyOrder];
                $queryBuilder
                    ->addOrderBy($column, $valueSort);
            }
        }
        $purchaseOrders = $queryBuilder->getQuery()->getScalarResult();

        $purchaseOrdersPerPage = $queryBuilder
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->execute();

        return ['data' => $purchaseOrdersPerPage, 'total' => count($purchaseOrders)];
    }

    /**
     * @param array $param
     * @return array
     */
    public function getDataForReportCommand(array $param): array
    {
        $queryBuilder = $this->createQueryBuilder('o')
            ->andWhere('o.deletedAt IS NULL')
            ->orderBy('o.createdAt', 'ASC');

        if (isset($param['status']) && !empty($param['status'])) {
            $queryBuilder->andWhere('o.status = :status')
                ->setParameter('status', $param['status']);
        }

        if (isset($param['fromDate']) && !empty($param['fromDate'])) {
            $queryBuilder->andWhere('o.createdAt >= :fromDate')
                ->setParameter('fromDate', $param['fromDate'] . ' 00:00:00');
        }

        if (isset($param['toDate']) && !empty($param['toDate'])) {
            $queryBuilder->andWhere('o.createdAt <= :toDate')
                ->setParameter('toDate', $param['toDate'] . ' 23:59:59');
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function getRevenue(?\DateTime $fromDate, ?\DateTime $toDate)
    {
        $queryBuilder = $this->createQueryBuilder('o')
            ->select('SUM(o.totalPrice) as total')
            ->andWhere('o.status = 1')
            ->andWhere('o.paymentMethod = :paymentMethod')
            ->orWhere('o.status = 4')
            ->andWhere('o.deletedAt is NULL')
            ->setParameter('paymentMethod', 'paypal');

        if ($fromDate != '') {
            $queryBuilder->andWhere('o.createdAt >= :fromDate')
                ->setParameter('fromDate', $fromDate);
        }

        if ($toDate != '') {
            $queryBuilder->andWhere('o.createdAt <= :toDate')
                ->setParameter('toDate', $toDate);
        }

        return $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function getChart()
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "SELECT MONTH(created_at) as month, YEAR(created_at) as year ,SUM(total_price) as revenue
                FROM `order`
                WHERE deleted_at IS NULL
                GROUP BY MONTH(created_at), YEAR(created_at)
                ORDER BY YEAR(created_at) ASC, MONTH(created_at) ASC
                LIMIT 12;";
        $stmt = $conn->prepare($sql);

        return $stmt->executeQuery()->fetchAllAssociative();
    }

    // /**
    //  * @return Order[] Returns an array of Order objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Order
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
