<?php

namespace App\Repository;

use App\Entity\Activity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Activity>
 *
 * @method Activity|null find($id, $lockMode = null, $lockVersion = null)
 * @method Activity|null findOneBy(array $criteria, array $orderBy = null)
 * @method Activity[]    findAll()
 * @method Activity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activity::class);
    }

    public function save(Activity $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Activity $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByFilters(array $filters)
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.client', 'c')
            ->leftJoin('a.agent', 'ag')
            ->leftJoin('a.property', 'p');

        if (!empty($filters['type'])) {
            $qb->andWhere('a.type = :type')
                ->setParameter('type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $qb->andWhere('a.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['client'])) {
            $qb->andWhere('a.client = :client')
                ->setParameter('client', $filters['client']);
        }

        if (!empty($filters['agent'])) {
            $qb->andWhere('a.agent = :agent')
                ->setParameter('agent', $filters['agent']);
        }

        if (!empty($filters['property'])) {
            $qb->andWhere('a.property = :property')
                ->setParameter('property', $filters['property']);
        }

        if (!empty($filters['dateFrom'])) {
            $qb->andWhere('a.scheduledAt >= :dateFrom')
                ->setParameter('dateFrom', $filters['dateFrom']);
        }

        if (!empty($filters['dateTo'])) {
            $qb->andWhere('a.scheduledAt <= :dateTo')
                ->setParameter('dateTo', $filters['dateTo']);
        }

        return $qb->orderBy('a.scheduledAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findUpcomingActivities($agent, $limit = 10)
    {
        return $this->createQueryBuilder('a')
            ->where('a.agent = :agent')
            ->andWhere('a.status = :status')
            ->andWhere('a.scheduledAt > :now')
            ->setParameter('agent', $agent)
            ->setParameter('status', 'scheduled')
            ->setParameter('now', new \DateTime())
            ->orderBy('a.scheduledAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findPastActivities($agent, $limit = 10)
    {
        return $this->createQueryBuilder('a')
            ->where('a.agent = :agent')
            ->andWhere('a.status = :status')
            ->andWhere('a.completedAt < :now')
            ->setParameter('agent', $agent)
            ->setParameter('status', 'completed')
            ->setParameter('now', new \DateTime())
            ->orderBy('a.completedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
} 