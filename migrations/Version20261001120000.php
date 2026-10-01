<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261001120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional private CV metadata to collaborators';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE collaborator ADD cv_filename VARCHAR(255) DEFAULT NULL, ADD cv_original_name VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE collaborator DROP cv_filename, DROP cv_original_name');
    }
}
