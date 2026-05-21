<?php

namespace App\Service;

use App\Entity\Evenement;
use App\Repository\EvenementRepository;

class EvenementService
{
    public function __construct(
        private readonly EvenementRepository $repository,
    ) {}

    /**
     * @return Evenement[]
     */
    public function findActifs(): array
    {
        return $this->repository->findBy(['actif' => true]);
    }
}
