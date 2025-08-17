<?php
// src/Entity/Traits/HasStatus.php
namespace App\Entity;

use Symfony\Contracts\Translation\TranslatorInterface;

trait HasStatus
{
    protected ?string $status = null;

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    // Fonction utilitaire pour récupérer le label traduit
    public function getStatusLabel(TranslatorInterface $translator, string $domain = 'messages'): string
    {
        return $translator->trans('status.' . $this->status, [], $domain);
    }
}
