<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class CollaboratorCvStorage
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/var/uploads/collaborator-cvs')]
        private string $storageDirectory,
    ) {
    }

    public function store(UploadedFile $file): string
    {
        if (!is_dir($this->storageDirectory)
            && !mkdir($this->storageDirectory, 0770, true)
            && !is_dir($this->storageDirectory)) {
            throw new \RuntimeException(sprintf('Impossible de créer le dossier de stockage des CV "%s".', $this->storageDirectory));
        }
        $filename = bin2hex(random_bytes(16)).'.pdf';
        $file->move($this->storageDirectory, $filename);

        return $filename;
    }

    public function path(string $filename): string
    {
        return $this->storageDirectory.'/'.$filename;
    }

    public function originalName(UploadedFile $file): string
    {
        $name = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        $asciiName = preg_replace('/[^A-Za-z0-9._ -]/', '-', $name) ?: 'cv.pdf';

        if (strlen($asciiName) <= 255) {
            return $asciiName;
        }

        return substr($asciiName, 0, 251).'.pdf';
    }

    public function remove(?string $filename): void
    {
        if ($filename !== null) {
            $path = $this->path($filename);
            if (is_file($path) && !unlink($path)) {
                throw new \RuntimeException(sprintf('Impossible de supprimer le CV "%s".', $filename));
            }
        }
    }
}
