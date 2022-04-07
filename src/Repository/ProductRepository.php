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

        return $queryBuilder
            ->andWhere('p.deletedAt is NULL')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->execute();
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
}
