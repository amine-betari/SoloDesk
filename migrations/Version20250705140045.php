<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250705140045 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D2EA46079D3C8144 ON estimate (estimate_number)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2FB3D0EE8134F41E ON project (project_number)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_2FB3D0EE8134F41E ON project');
        $this->addSql('DROP INDEX UNIQ_D2EA46079D3C8144 ON estimate');
    }
}
