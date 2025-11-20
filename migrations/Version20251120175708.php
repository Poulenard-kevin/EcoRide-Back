<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251120175708 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE covoiturage CHANGE prix_par_place prix_par_place DOUBLE PRECISION DEFAULT NULL, CHANGE statut statut VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation CHANGE statut statut VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservation CHANGE statut statut VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE covoiturage CHANGE prix_par_place prix_par_place INT NOT NULL, CHANGE statut statut VARCHAR(50) DEFAULT \'active\' NOT NULL');
    }
}
