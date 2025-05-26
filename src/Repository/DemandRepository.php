<?php

namespace App\Repository;

use App\Entity\Demand;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Demand>
 *
 * @method Demand|null find($id, $lockMode = null, $lockVersion = null)
 * @method Demand|null findOneBy(array $criteria, array $orderBy = null)
 * @method Demand[]    findAll()
 * @method Demand[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DemandRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Demand::class);
    }

    public function save(Demand $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Demand $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByFilters(array $filters)
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.client', 'c');

        if (!empty($filters['type'])) {
            $qb->andWhere('d.type = :type')
                ->setParameter('type', $filters['type']);
        }

        if (!empty($filters['propertyType'])) {
            $qb->andWhere('d.propertyType = :propertyType')
                ->setParameter('propertyType', $filters['propertyType']);
        }

        if (!empty($filters['minPrice'])) {
            $qb->andWhere('d.minPrice >= :minPrice')
                ->setParameter('minPrice', $filters['minPrice']);
        }

        if (!empty($filters['maxPrice'])) {
            $qb->andWhere('d.maxPrice <= :maxPrice')
                ->setParameter('maxPrice', $filters['maxPrice']);
        }

        if (!empty($filters['location'])) {
            $qb->andWhere('d.location LIKE :location')
                ->setParameter('location', '%' . $filters['location'] . '%');
        }

        if (!empty($filters['minBedrooms'])) {
            $qb->andWhere('d.minBedrooms >= :minBedrooms')
                ->setParameter('minBedrooms', $filters['minBedrooms']);
        }

        if (!empty($filters['minBathrooms'])) {
            $qb->andWhere('d.minBathrooms >= :minBathrooms')
                ->setParameter('minBathrooms', $filters['minBathrooms']);
        }

        if (!empty($filters['minSquareFootage'])) {
            $qb->andWhere('d.minSquareFootage >= :minSquareFootage')
                ->setParameter('minSquareFootage', $filters['minSquareFootage']);
        }

        if (!empty($filters['status'])) {
            $qb->andWhere('d.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['client'])) {
            $qb->andWhere('d.client = :client')
                ->setParameter('client', $filters['client']);
        }

        return $qb->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findMatchingProperties(Demand $demand)
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('p')
            ->from('App\Entity\Property', 'p')
            ->where('p.status = :status')
            ->setParameter('status', 'active');

        if ($demand->getPropertyType()) {
            $qb->andWhere('p.propertyType = :propertyType')
                ->setParameter('propertyType', $demand->getPropertyType());
        }

        if ($demand->getMinPrice()) {
            $qb->andWhere('p.price >= :minPrice')
                ->setParameter('minPrice', $demand->getMinPrice());
        }

        if ($demand->getMaxPrice()) {
            $qb->andWhere('p.price <= :maxPrice')
                ->setParameter('maxPrice', $demand->getMaxPrice());
        }

        if ($demand->getLocation()) {
            $qb->andWhere('p.city LIKE :location OR p.address LIKE :location')
                ->setParameter('location', '%' . $demand->getLocation() . '%');
        }

        if ($demand->getMinBedrooms()) {
            $qb->andWhere('p.bedrooms >= :minBedrooms')
                ->setParameter('minBedrooms', $demand->getMinBedrooms());
        }

        if ($demand->getMinBathrooms()) {
            $qb->andWhere('p.bathrooms >= :minBathrooms')
                ->setParameter('minBathrooms', $demand->getMinBathrooms());
        }

        if ($demand->getMinSquareFootage()) {
            $qb->andWhere('p.squareFootage >= :minSquareFootage')
                ->setParameter('minSquareFootage', $demand->getMinSquareFootage());
        }

        if (!empty($demand->getFeatures())) {
            foreach ($demand->getFeatures() as $feature) {
                $qb->andWhere('JSON_CONTAINS(p.features, :feature) = 1')
                    ->setParameter('feature', json_encode($feature));
            }
        }

        return $qb->getQuery()->getResult();
    }
} 