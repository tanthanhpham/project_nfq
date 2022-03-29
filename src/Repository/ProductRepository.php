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
     * @param $arrayParams
     * @return float|int|mixed|string
     */
    public function filter($arrayParams)
    {
        $queryBuilder = $this->createQueryBuilder('p');
        if (isset($arrayParams['minPrice']) && $arrayParams['minPrice'] != '') {
            $queryBuilder
                ->andWhere('p.price >= :minPrice')
                ->setParameter('minPrice', $arrayParams['minPrice']);
        }

        if (isset($arrayParams['maxPrice']) && $arrayParams['maxPrice'] != '') {
            $queryBuilder
                ->andWhere('p.price <= :maxPrice')
                ->setParameter('maxPrice', $arrayParams['maxPrice']);
        }

        if (isset($arrayParams['category']) && $arrayParams['category'] != '' && $arrayParams['category'] != 1) {
            $queryBuilder
                ->andWhere('p.category = :category')
                ->setParameter('category', $arrayParams['category']);
        }

        return $queryBuilder
            ->getQuery()
            ->execute();
    }
}
