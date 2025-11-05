<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration adaptée pour phase de test : colonnes nullable et valeur par défaut pour statut.
 */
final class Version20251105220345 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rendre les colonnes FK nullable et ajouter statut avec valeur par défaut dans covoiturage pour phase de test';
    }

    public function up(Schema $schema): void
    {
        // Rendre les colonnes FK nullable pour éviter erreurs sur données existantes
        $this->addSql('ALTER TABLE avis CHANGE auteur_id auteur_id INT DEFAULT NULL, CHANGE covoiturage_id covoiturage_id INT DEFAULT NULL, CHANGE cible_id cible_id INT DEFAULT NULL');
        $this->addSql("ALTER TABLE covoiturage ADD statut VARCHAR(50) NOT NULL DEFAULT 'active'");
        $this->addSql('ALTER TABLE covoiturage CHANGE chauffeur_id chauffeur_id INT DEFAULT NULL, CHANGE voiture_id voiture_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation CHANGE passager_id passager_id INT DEFAULT NULL, CHANGE covoiturage_id covoiturage_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE voiture CHANGE proprietaire_id proprietaire_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Revenir aux colonnes NOT NULL et suppression de la colonne statut
        $this->addSql('ALTER TABLE reservation CHANGE passager_id passager_id INT NOT NULL, CHANGE covoiturage_id covoiturage_id INT NOT NULL');
        $this->addSql('ALTER TABLE avis CHANGE auteur_id auteur_id INT NOT NULL, CHANGE covoiturage_id covoiturage_id INT NOT NULL, CHANGE cible_id cible_id INT NOT NULL');
        $this->addSql('ALTER TABLE voiture CHANGE proprietaire_id proprietaire_id INT NOT NULL');
        $this->addSql('ALTER TABLE covoiturage DROP statut, CHANGE chauffeur_id chauffeur_id INT NOT NULL, CHANGE voiture_id voiture_id INT NOT NULL');
    }
}