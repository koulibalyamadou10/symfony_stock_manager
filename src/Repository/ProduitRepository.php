<?php

namespace App\Repository;

use App\Entity\Produit;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Produit>
 */
class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    /**
     * Trouve tous les produits actifs
     */
    public function findActifs(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.actif = :actif')
            ->setParameter('actif', true)
            ->orderBy('p.dateAjout', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les produits actifs d'un utilisateur spécifique
     */
    public function findActifsByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.actif = :actif')
            ->andWhere('p.utilisateur = :user')
            ->setParameter('actif', true)
            ->setParameter('user', $user)
            ->orderBy('p.dateAjout', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les produits d'un utilisateur (actifs et inactifs)
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.utilisateur = :user')
            ->setParameter('user', $user)
            ->orderBy('p.dateAjout', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les produits en rupture de stock
     */
    public function findRuptureStock(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.quantite <= 0')
            ->orderBy('p.dateAjout', 'DESC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Produit[] Returns an array of Produit objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Produit
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}