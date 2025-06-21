<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour créer la table des abonnements
 */
final class Version20250103000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table abonnement pour gérer les abonnements utilisateurs';
    }

    public function up(Schema $schema): void
    {
        // Créer la table abonnement
        $this->addSql('CREATE TABLE abonnement (
            id INT AUTO_INCREMENT NOT NULL, 
            utilisateur_id INT NOT NULL, 
            date_debut DATETIME NOT NULL, 
            date_fin DATETIME NOT NULL, 
            montant INT NOT NULL, 
            est_actif TINYINT(1) NOT NULL, 
            transaction_id VARCHAR(255) DEFAULT NULL, 
            date_creation DATETIME NOT NULL, 
            INDEX IDX_351268BBFB88E14F (utilisateur_id), 
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Ajouter la contrainte de clé étrangère
        $this->addSql('ALTER TABLE abonnement ADD CONSTRAINT FK_351268BBFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // Supprimer la contrainte de clé étrangère
        $this->addSql('ALTER TABLE abonnement DROP FOREIGN KEY FK_351268BBFB88E14F');
        
        // Supprimer la table
        $this->addSql('DROP TABLE abonnement');
    }
}
