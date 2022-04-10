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
     * @param $limit
     * @param $offset
     * @param $orderBy
     * @return array
     */
    public function findByConditions(array $param, $orderBy, $limit, $offset): array
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->andWhere('p.deletedAt IS NULL');

        if (isset($orderBy['createdAt'])) {
            $queryBuilder
                ->addOrderBy('p.createdAt', $orderBy['createdAt']);
        }

        if (!empty($orderBy)) {
            $keyOrderList = array_keys($orderBy);
            $column = 'p.' . $keyOrderList[0];
            $valueSort = $orderBy[$keyOrderList[0]];
            $queryBuilder
                ->addOrderBy($column, $valueSort);
        }

        $products = $queryBuilder->getQuery()->getScalarResult();

        $productPerPage = $queryBuilder
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->execute();

        return ['data' => $productPerPage, 'total' => count($products)];
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

    public function getRevenue(?\DateTime $fromDate = null, ?\DateTime $toDate = null)
    {
        $queryBuilder = $this->createQueryBuilder('o')
            ->select('SUM(o.totalPrice) as total')
            ->where('o.status = 4');

        if ($fromDate != null) {
            $queryBuilder->andWhere('o.createdAt >= :fromDate')
                ->setParameter('formDate', $fromDate);
        }

        if ($toDate != null) {
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
                ORDER BY YEAR(created_at) DESC, MONTH(created_at) DESC
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
