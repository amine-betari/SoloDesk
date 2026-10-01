<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\CollaboratorCvStorage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class CollaboratorCvStorageTest extends TestCase
{
    private string $storageDirectory;

    protected function setUp(): void
    {
        $this->storageDirectory = sys_get_temp_dir().'/solodesk-cv-storage-'.bin2hex(random_bytes(8));
    }

    protected function tearDown(): void
    {
        if (is_dir($this->storageDirectory)) {
            rmdir($this->storageDirectory);
        }
    }

    public function testStoresAndRemovesPdfWithAnOpaqueFilename(): void
    {
        $source = tempnam(sys_get_temp_dir(), 'solodesk-cv-');
        self::assertIsString($source);
        file_put_contents($source, "%PDF-1.4\n%%EOF");

        $storage = new CollaboratorCvStorage($this->storageDirectory);
        $upload = new UploadedFile($source, 'CV Jean Dupont.pdf', 'application/pdf', null, true);
        $filename = $storage->store($upload);

        self::assertMatchesRegularExpression('/^[a-f0-9]{32}\.pdf$/', $filename);
        self::assertFileExists($storage->path($filename));
        self::assertSame('CV Jean Dupont.pdf', $storage->originalName($upload));

        $storage->remove($filename);

        self::assertFileDoesNotExist($storage->path($filename));
    }

    public function testNormalizesAnUnsafeOriginalFilename(): void
    {
        $source = tempnam(sys_get_temp_dir(), 'solodesk-cv-');
        self::assertIsString($source);
        file_put_contents($source, "%PDF-1.4\n%%EOF");

        $storage = new CollaboratorCvStorage($this->storageDirectory);
        $upload = new UploadedFile($source, '../CV échantillon.pdf', 'application/pdf', null, true);

        self::assertSame('CV --chantillon.pdf', $storage->originalName($upload));

        unlink($source);
    }
}
