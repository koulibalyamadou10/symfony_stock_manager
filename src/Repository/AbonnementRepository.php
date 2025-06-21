<?php

namespace App\Repository;

use App\Entity\Abonnement;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Abonnement>
 */
class AbonnementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Abonnement::class);
    }

    /**
     * Trouve l'abonnement actif d'un utilisateur
     */
    public function findAbonnementActif(User $user): ?Abonnement
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.utilisateur = :user')
            ->andWhere('a.estActif = :actif')
            ->andWhere('a.dateFin > :now')
            ->setParameter('user', $user)
            ->setParameter('actif', true)
            ->setParameter('now', new \DateTime())
            ->orderBy('a.dateFin', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Vérifie si un utilisateur a un abonnement valide
     */
    public function hasAbonnementValide(User $user): bool
    {
        return $this->findAbonnementActif($user) !== null;
    }

    /**
     * Trouve les abonnements qui expirent bientôt (dans les 3 jours)
     */
    public function findAbonnementsExpirantBientot(): array
    {
        $dateLimite = new \DateTime('+3 days');
        
        return $this->createQueryBuilder('a')
            ->andWhere('a.estActif = :actif')
            ->andWhere('a.dateFin BETWEEN :now AND :limite')
            ->setParameter('actif', true)
            ->setParameter('now', new \DateTime())
            ->setParameter('limite', $dateLimite)
            ->getQuery()
            ->getResult();
    }

    /**
     * Désactive les abonnements expirés
     */
    public function desactiverAbonnementsExpires(): int
    {
        return $this->createQueryBuilder('a')
            ->update()
            ->set('a.estActif', ':inactif')
            ->andWhere('a.estActif = :actif')
            ->andWhere('a.dateFin < :now')
            ->setParameter('actif', true)
            ->setParameter('inactif', false)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute();
    }
}
