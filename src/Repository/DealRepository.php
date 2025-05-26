<?php

namespace App\Repository;

use App\Entity\Deal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Deal>
 *
 * @method Deal|null find($id, $lockMode = null, $lockVersion = null)
 * @method Deal|null findOneBy(array $criteria, array $orderBy = null)
 * @method Deal[]    findAll()
 * @method Deal[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DealRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Deal::class);
    }

    public function save(Deal $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Deal $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByFilters(array $filters)
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.property', 'p')
            ->leftJoin('d.client', 'c')
            ->leftJoin('d.agent', 'a');

        if (!empty($filters['type'])) {
            $qb->andWhere('d.dealType = :type')
                ->setParameter('type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $qb->andWhere('d.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['minPrice'])) {
            $qb->andWhere('d.price >= :minPrice')
                ->setParameter('minPrice', $filters['minPrice']);
        }

        if (!empty($filters['maxPrice'])) {
            $qb->andWhere('d.price <= :maxPrice')
                ->setParameter('maxPrice', $filters['maxPrice']);
        }

        if (!empty($filters['client'])) {
            $qb->andWhere('d.client = :client')
                ->setParameter('client', $filters['client']);
        }

        if (!empty($filters['agent'])) {
            $qb->andWhere('d.agent = :agent')
                ->setParameter('agent', $filters['agent']);
        }

        if (!empty($filters['property'])) {
            $qb->andWhere('d.property = :property')
                ->setParameter('property', $filters['property']);
        }

        if (!empty($filters['dateFrom'])) {
            $qb->andWhere('d.createdAt >= :dateFrom')
                ->setParameter('dateFrom', $filters['dateFrom']);
        }

        if (!empty($filters['dateTo'])) {
            $qb->andWhere('d.createdAt <= :dateTo')
                ->setParameter('dateTo', $filters['dateTo']);
        }

        return $qb->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getAgentStats($agent)
    {
        return $this->createQueryBuilder('d')
            ->select('COUNT(d.id) as totalDeals')
            ->addSelect('SUM(d.price) as totalValue')
            ->addSelect('SUM(d.commission) as totalCommission')
            ->where('d.agent = :agent')
            ->andWhere('d.status = :status')
            ->setParameter('agent', $agent)
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getOneOrNullResult();
    }
} 