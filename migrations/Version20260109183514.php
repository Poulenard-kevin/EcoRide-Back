<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260109183514 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initialise note_moyenne à 5 pour les enregistrements existants et définit DEFAULT 5 sur la colonne';
    }

    public function up(Schema $schema): void
    {
        // 1) mettre à 5 les valeurs NULL existantes
        $this->addSql("UPDATE utilisateur SET note_moyenne = 5 WHERE note_moyenne IS NULL");

        // 2) définir la colonne avec DEFAULT 5 et NOT NULL
        // Attention : adapter le type si nécessaire selon ta base (DOUBLE PRECISION, FLOAT, etc.).
        $this->addSql("ALTER TABLE utilisateur CHANGE note_moyenne note_moyenne DOUBLE PRECISION DEFAULT 5 NOT NULL");
    }

    public function down(Schema $schema): void
    {
        // Revenir à l'état précédent : permettre NULL et retirer la valeur par défaut
        $this->addSql("ALTER TABLE utilisateur CHANGE note_moyenne note_moyenne DOUBLE PRECISION DEFAULT NULL");
    }
}