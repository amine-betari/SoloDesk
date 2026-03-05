<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260304111500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add company RC number';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE company ADD rc_number VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE company DROP rc_number');
    }
}
