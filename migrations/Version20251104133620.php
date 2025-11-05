<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251104133620 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Supprimer la contrainte FK avant modification
        $this->addSql('ALTER TABLE covoiturage DROP FOREIGN KEY FK_28C79E8985C0B3BE');

        // Modifier la colonne chauffeur_id
        $this->addSql('ALTER TABLE covoiturage CHANGE chauffeur_id chauffeur_id INT NOT NULL');

        // Modifier les colonnes date/heure
        $this->addSql('ALTER TABLE covoiturage CHANGE date_depart date_depart DATE NOT NULL, CHANGE heure_depart heure_depart TIME NOT NULL, CHANGE date_arrivee date_arrivee DATE NOT NULL, CHANGE heure_arrivee heure_arrivee TIME NOT NULL');

        // Ajouter la contrainte FK après modification
        $this->addSql('ALTER TABLE covoiturage ADD CONSTRAINT FK_28C79E8985C0B3BE FOREIGN KEY (chauffeur_id) REFERENCES utilisateur (id)');

        // Ajouter la colonne covoiturage_id dans reservation avec FK et index
        $this->addSql('ALTER TABLE reservation ADD covoiturage_id INT NOT NULL');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C8495562671590 FOREIGN KEY (covoiturage_id) REFERENCES covoiturage (id)');
        $this->addSql('CREATE INDEX IDX_42C8495562671590 ON reservation (covoiturage_id)');

        // Modifier passager_id pour NOT NULL
        $this->addSql('ALTER TABLE reservation CHANGE passager_id passager_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C8495562671590');
        $this->addSql('DROP INDEX IDX_42C8495562671590 ON reservation');
        $this->addSql('ALTER TABLE reservation DROP covoiturage_id, CHANGE passager_id passager_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE covoiturage CHANGE chauffeur_id chauffeur_id INT DEFAULT NULL, CHANGE date_depart date_depart DATETIME NOT NULL, CHANGE heure_depart heure_depart DATETIME NOT NULL, CHANGE date_arrivee date_arrivee DATETIME NOT NULL, CHANGE heure_arrivee heure_arrivee DATETIME NOT NULL');
    }
}
