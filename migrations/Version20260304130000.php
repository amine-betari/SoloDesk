<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260304130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed generic skills for all companies';
    }

    public function up(Schema $schema): void
    {
        $skills = [
            'Symfony',
            'Laravel',
            'PHP',
            'JavaScript',
            'TypeScript',
            'React',
            'Vue.js',
            'Node.js',
            'HTML/CSS',
            'Tailwind CSS',
            'UI/UX',
            'SEO',
            'Marketing',
            'Design',
            'Comptabilité',
            'Gestion de projet',
            'DevOps',
            'Docker',
            'PostgreSQL',
            'MySQL',
        ];

        foreach ($skills as $name) {
            $this->addSql(
                'INSERT INTO skill (company_id, name) SELECT c.id, :name FROM company c',
                ['name' => $name]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM skill');
    }
}
