<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251105110040 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des colonnes auteur_id et covoiturage_id avec leurs contraintes FK sur avis, et covoiturage_id sur reservation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE avis ADD auteur_id INT NOT NULL');
        $this->addSql('ALTER TABLE avis ADD covoiturage_id INT NOT NULL');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF060BB6FE6 FOREIGN KEY (auteur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF062671590 FOREIGN KEY (covoiturage_id) REFERENCES covoiturage (id)');
        $this->addSql('CREATE INDEX IDX_8F91ABF060BB6FE6 ON avis (auteur_id)');
        $this->addSql('CREATE INDEX IDX_8F91ABF062671590 ON avis (covoiturage_id)');
        $this->addSql('ALTER TABLE reservation ADD covoiturage_id INT NOT NULL');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C8495562671590 FOREIGN KEY (covoiturage_id) REFERENCES covoiturage (id)');
        $this->addSql('CREATE INDEX IDX_42C8495562671590 ON reservation (covoiturage_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF060BB6FE6');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF062671590');
        $this->addSql('DROP INDEX IDX_8F91ABF060BB6FE6 ON avis');
        $this->addSql('DROP INDEX IDX_8F91ABF062671590 ON avis');
        $this->addSql('ALTER TABLE avis DROP auteur_id');
        $this->addSql('ALTER TABLE avis DROP covoiturage_id');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C8495562671590');
        $this->addSql('DROP INDEX IDX_42C8495562671590 ON reservation');
        $this->addSql('ALTER TABLE reservation DROP covoiturage_id');
    }
}
