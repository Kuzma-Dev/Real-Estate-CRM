<?php

namespace App\Repository;

use App\Entity\Property;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Property>
 *
 * @method Property|null find($id, $lockMode = null, $lockVersion = null)
 * @method Property|null findOneBy(array $criteria, array $orderBy = null)
 * @method Property[]    findAll()
 * @method Property[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PropertyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Property::class);
    }

    public function save(Property $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Property $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByFilters(array $filters)
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->setParameter('status', 'active');

        if (!empty($filters['type'])) {
            $qb->andWhere('p.propertyType = :type')
                ->setParameter('type', $filters['type']);
        }

        if (!empty($filters['minPrice'])) {
            $qb->andWhere('p.price >= :minPrice')
                ->setParameter('minPrice', $filters['minPrice']);
        }

        if (!empty($filters['maxPrice'])) {
            $qb->andWhere('p.price <= :maxPrice')
                ->setParameter('maxPrice', $filters['maxPrice']);
        }

        if (!empty($filters['location'])) {
            $qb->andWhere('p.city LIKE :location OR p.address LIKE :location')
                ->setParameter('location', '%' . $filters['location'] . '%');
        }

        if (!empty($filters['bedrooms'])) {
            $qb->andWhere('p.bedrooms >= :bedrooms')
                ->setParameter('bedrooms', $filters['bedrooms']);
        }

        if (!empty($filters['bathrooms'])) {
            $qb->andWhere('p.bathrooms >= :bathrooms')
                ->setParameter('bathrooms', $filters['bathrooms']);
        }

        if (!empty($filters['features'])) {
            foreach ($filters['features'] as $feature) {
                $qb->andWhere('JSON_CONTAINS(p.features, :feature) = 1')
                    ->setParameter('feature', json_encode($feature));
            }
        }

        return $qb->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
} 