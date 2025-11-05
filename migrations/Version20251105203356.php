<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251105203356 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE avis (
            id INT AUTO_INCREMENT NOT NULL,
            auteur_id INT DEFAULT NULL,
            covoiturage_id INT DEFAULT NULL,
            cible_id INT DEFAULT NULL,
            note INT NOT NULL,
            commentaire VARCHAR(500) DEFAULT NULL,
            date DATETIME NOT NULL,
            INDEX IDX_8F91ABF060BB6FE6 (auteur_id),
            INDEX IDX_8F91ABF062671590 (covoiturage_id),
            INDEX IDX_8F91ABF0A96E5E09 (cible_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE covoiturage (
            id INT AUTO_INCREMENT NOT NULL,
            chauffeur_id INT DEFAULT NULL,
            voiture_id INT DEFAULT NULL,
            date_depart DATE NOT NULL,
            heure_depart TIME NOT NULL,
            lieu_depart VARCHAR(255) NOT NULL,
            date_arrivee DATE NOT NULL,
            heure_arrivee TIME NOT NULL,
            lieu_arrivee VARCHAR(255) NOT NULL,
            prix_par_place DOUBLE PRECISION NOT NULL,
            nb_places_total INT NOT NULL,
            nb_places_dispo INT NOT NULL,
            INDEX IDX_28C79E8985C0B3BE (chauffeur_id),
            INDEX IDX_28C79E89181A8BA (voiture_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE reservation (
            id INT AUTO_INCREMENT NOT NULL,
            passager_id INT DEFAULT NULL,
            covoiturage_id INT DEFAULT NULL,
            date_reservation DATETIME NOT NULL,
            nb_places_reservees INT NOT NULL,
            statut VARCHAR(50) NOT NULL,
            INDEX IDX_42C8495571A51189 (passager_id),
            INDEX IDX_42C8495562671590 (covoiturage_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE utilisateur (
            id INT AUTO_INCREMENT NOT NULL,
            nom VARCHAR(100) NOT NULL,
            prenom VARCHAR(100) NOT NULL,
            email VARCHAR(180) NOT NULL,
            mot_de_passe VARCHAR(255) NOT NULL,
            role VARCHAR(50) NOT NULL,
            note_moyenne DOUBLE PRECISION DEFAULT NULL,
            a_propos LONGTEXT DEFAULT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE voiture (
            id INT AUTO_INCREMENT NOT NULL,
            proprietaire_id INT DEFAULT NULL,
            marque VARCHAR(50) NOT NULL,
            modele VARCHAR(50) NOT NULL,
            couleur VARCHAR(30) NOT NULL,
            energie VARCHAR(30) NOT NULL,
            immatriculation VARCHAR(20) NOT NULL,
            nb_places INT NOT NULL,
            preferences_chauffeur JSON DEFAULT NULL,
            autres_preferences VARCHAR(255) DEFAULT NULL,
            INDEX IDX_E9E2810F76C50E4A (proprietaire_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF060BB6FE6 FOREIGN KEY (auteur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF062671590 FOREIGN KEY (covoiturage_id) REFERENCES covoiturage (id)');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF0A96E5E09 FOREIGN KEY (cible_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE covoiturage ADD CONSTRAINT FK_28C79E8985C0B3BE FOREIGN KEY (chauffeur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE covoiturage ADD CONSTRAINT FK_28C79E89181A8BA FOREIGN KEY (voiture_id) REFERENCES voiture (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C8495571A51189 FOREIGN KEY (passager_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C8495562671590 FOREIGN KEY (covoiturage_id) REFERENCES covoiturage (id)');
        $this->addSql('ALTER TABLE voiture ADD CONSTRAINT FK_E9E2810F76C50E4A FOREIGN KEY (proprietaire_id) REFERENCES utilisateur (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF060BB6FE6');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF062671590');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF0A96E5E09');
        $this->addSql('ALTER TABLE covoiturage DROP FOREIGN KEY FK_28C79E8985C0B3BE');
        $this->addSql('ALTER TABLE covoiturage DROP FOREIGN KEY FK_28C79E89181A8BA');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C8495571A51189');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C8495562671590');
        $this->addSql('ALTER TABLE voiture DROP FOREIGN KEY FK_E9E2810F76C50E4A');
        $this->addSql('DROP TABLE avis');
        $this->addSql('DROP TABLE covoiturage');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('DROP TABLE voiture');
    }
}