<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Product $entity, bool $flush = true): void
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
    public function remove(Product $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @param $criteria
     * @param $orderBy
     * @param $limit
     * @param $offset
     * @return array
     */
    public function filter($criteria, $orderBy, $limit, $offset): array
    {
        $queryBuilder = $this->createQueryBuilder('p');
        if (isset($criteria['keyword']) && $criteria['keyword'] != '') {
            $queryBuilder->select('p')
                ->orWhere('p.name LIKE :key')
                ->setParameter('key', $criteria['keyword'])
                ->orWhere('p.description LIKE :key')
                ->setParameter('key', $criteria['keyword'])
                ->orWhere('c.name LIKE :key')
                ->andWhere('p.deletedAt is NULL')
                ->setParameter('key', $criteria['keyword'])
                ->innerJoin('p.category', 'c');
        }

        if (isset($criteria['minPrice']) && $criteria['minPrice'] != '') {
            $queryBuilder
                ->andWhere('p.price >= :minPrice')
                ->setParameter('minPrice', $criteria['minPrice']);
        }

        if (isset($criteria['maxPrice']) && $criteria['maxPrice'] != '') {
            $queryBuilder
                ->andWhere('p.price <= :maxPrice')
                ->setParameter('maxPrice', $criteria['maxPrice']);
        }

        if (isset($criteria['category']) && $criteria['category'] != '' && $criteria['category'] != 1) {
            $queryBuilder
                ->andWhere('p.category = :category')
                ->setParameter('category', $criteria['category']);
        }

        if (!empty($orderBy)) {
            $arrayKeyOrder = array_keys($orderBy);
            $sort = 'p.' . $arrayKeyOrder[0];
            $order = $orderBy[$arrayKeyOrder[0]];
            $queryBuilder
                ->orderBy($sort, $order);
        }

        $products = $queryBuilder->getQuery()->getScalarResult();

        $productPerPage = $queryBuilder
            ->andWhere('p.deletedAt is NULL')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->execute();

        return ['data' => $productPerPage, 'total' => count($products)];
    }

    /**
     * @param array $param
     * @param $limit
     * @param $offset
     * @param $orderBy
     * @return array
     */
    public function findByConditions(array $param, $orderBy, ?int $limit = null, ?int $offset = null): array
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->andWhere('p.deletedAt IS NULL');

        if (isset($param['priceFrom']) && $param['priceFrom'] != '') {
            $queryBuilder
                ->andWhere('p.price >= :minPrice')
                ->setParameter('minPrice', $param['priceFrom']);
        }

        if (isset($param['priceTo']) && $param['priceTo'] != '') {
            $queryBuilder
                ->andWhere('p.price <= :maxPrice')
                ->setParameter('maxPrice', $param['priceTo']);
        }

        if (isset($param['category']) && $param['category'] != 0) {
            $queryBuilder
                ->andWhere('p.category = :categoryId')
                ->setParameter('categoryId', $param['category']);
        }


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

    public function findBestSelling()
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('p')
            ->select('p.id', 'SUM(oDetail.amount) as totalAmount')
            ->where('p.deletedAt IS NULL')
            ->innerJoin('p.productItems', 'pItems')
            ->innerJoin('pItems.orderDetails', 'oDetail')
            ->innerJoin('oDetail.purchaseOrder', 'o')
            ->groupBy('p.id')
            ->setFirstResult(0)
            ->setMaxResults(7)
            ->addOrderBy('totalAmount', 'DESC');

        return $queryBuilder->getQuery()->getResult();
    }

    public function search(string $key, $limit, $offset)
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('p')
            ->orWhere('p.name LIKE :key')
            ->setParameter('key', $key)
            ->orWhere('p.description LIKE :key')
            ->setParameter('key', $key)
            ->orWhere('c.name LIKE :key')
            ->andWhere('p.deletedAt is NULL')
            ->setParameter('key', $key)
            ->innerJoin('p.category', 'c');

        $products = $queryBuilder->getQuery()->getScalarResult();
        $productPerPage = $queryBuilder
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->addOrderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->execute();

        return ['data' => $productPerPage, 'total' => count($products)];
    }
}
