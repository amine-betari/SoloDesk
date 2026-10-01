<?php

declare(strict_types=1);

namespace App\Tests\Template;

use PHPUnit\Framework\TestCase;

final class CollaboratorCvListContractTest extends TestCase
{
    public function testListDisplaysAnInlineCvLinkOnlyWhenACvExists(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2).'/templates/collaborator/index.html.twig');

        self::assertNotFalse($contents);
        self::assertStringContainsString('{% if collaborator.cvFilename %}', $contents);
        self::assertStringContainsString("'app_collaborator_cv_download'", $contents);
        self::assertStringContainsString("'inline': 1", $contents);
        self::assertStringContainsString("'collaborator.cv_view'|trans", $contents);
    }
}
