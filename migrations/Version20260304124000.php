<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260304124000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_core flag to skill';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE skill ADD is_core TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE skill DROP is_core');
    }
}
