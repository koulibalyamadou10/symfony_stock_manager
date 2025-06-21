<?php

namespace App\Repository;

use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subscription>
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    /**
     * Trouve l'abonnement actif d'un utilisateur
     */
    public function findActiveByUser(User $user): ?Subscription
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.user = :user')
            ->andWhere('s.isActive = :active')
            ->andWhere('s.endDate > :now')
            ->setParameter('user', $user)
            ->setParameter('active', true)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve tous les abonnements expirés
     */
    public function findExpired(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.endDate < :now')
            ->andWhere('s.isActive = :active')
            ->setParameter('now', new \DateTime())
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les abonnements qui expirent bientôt (dans les 7 jours)
     */
    public function findExpiringSoon(): array
    {
        $sevenDaysFromNow = new \DateTime('+7 days');
        
        return $this->createQueryBuilder('s')
            ->andWhere('s.endDate <= :sevenDays')
            ->andWhere('s.endDate > :now')
            ->andWhere('s.isActive = :active')
            ->setParameter('sevenDays', $sevenDaysFromNow)
            ->setParameter('now', new \DateTime())
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un abonnement par payment ID
     */
    public function findByPaymentId(string $paymentId): ?Subscription
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.paymentId = :paymentId')
            ->setParameter('paymentId', $paymentId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Compte les abonnements actifs
     */
    public function countActive(): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.isActive = :active')
            ->andWhere('s.endDate > :now')
            ->setParameter('active', true)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Calcule le revenu mensuel
     */
    public function getMonthlyRevenue(): float
    {
        $startOfMonth = new \DateTime('first day of this month 00:00:00');
        $endOfMonth = new \DateTime('last day of this month 23:59:59');

        $result = $this->createQueryBuilder('s')
            ->select('SUM(s.amount)')
            ->andWhere('s.paidAt >= :start')
            ->andWhere('s.paidAt <= :end')
            ->andWhere('s.status = :status')
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->setParameter('status', 'active')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }
}