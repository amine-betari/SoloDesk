<?php

namespace App\Services;

use App\Entity\Document;
use App\Entity\Estimate;
use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class DocumentManager
{
    private string $documentsDirectory;
    private EntityManagerInterface $entityManager;

    public function __construct(
        #[Autowire('%documents_directory%')] string $documentsDirectory,
        EntityManagerInterface $entityManager)
    {
        $this->documentsDirectory = $documentsDirectory;
        $this->entityManager = $entityManager;
    }

    public function removeDocuments(array $documentIds, object $relatedEntity): void
    {
        $entityClassName = (new \ReflectionClass($relatedEntity))->getShortName(); // "Estimate" ou "Project"
        $methodName = 'get' . $entityClassName;

        foreach ($documentIds as $id) {
            $document = $this->entityManager->getRepository(Document::class)->find($id);

          //  if ($document && $document->getEstimate() === $relatedEntity) { // Ou getProject(), à adapter
            if ($document && method_exists($document, $methodName) && $document->$methodName() === $relatedEntity) {

                $filePath = $this->documentsDirectory . '/' . $document->getFilename();
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                $this->entityManager->remove($document);
            }
        }
    }

    public function uploadDocuments(array $files, object $relatedEntity, $slugger): void
    {
        foreach ($files as $file) {
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

            $file->move($this->documentsDirectory, $newFilename);

            $document = new Document();
            $document->setFilename($newFilename);
            $document->setUploadedAt(new \DateTimeImmutable());

            // Liaison dynamique
            if (method_exists($document, 'setEstimate') && get_class($relatedEntity) === Estimate::class) {
                $document->setEstimate($relatedEntity);
            } elseif (method_exists($document, 'setProject') && get_class($relatedEntity) === Project::class) {
                $document->setProject($relatedEntity);
            }

            $this->entityManager->persist($document);
        }
    }
}
