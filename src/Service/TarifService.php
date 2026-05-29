<?php

namespace App\Service;

use App\Entity\Evenement;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class TarifService
{
    public function __construct(
        #[Autowire('%app.tva_rate%')]
        private float|string $tvaRate,
        #[Autowire('%app.reduction_groupe_taux%')]
        private float|string $reductionGroupeTaux,
        #[Autowire('%app.reduction_groupe_seuil%')]
        private int|string $reductionGroupeSeuil,
    ) {
        $this->tvaRate = (float) $this->tvaRate;
        $this->reductionGroupeTaux = (float) $this->reductionGroupeTaux;
        $this->reductionGroupeSeuil = (int) $this->reductionGroupeSeuil;
    }

    public function calculerTotal(Evenement $evenement, int $quantite): float
    {
        $prixBase = (float) $evenement->getPrixBase();
        $totalHT = $prixBase * $quantite;

        if ($quantite >= $this->reductionGroupeSeuil) {
            $totalHT = $totalHT * (1 - $this->reductionGroupeTaux);
        }

        $totalTTC = $totalHT * (1 + $this->tvaRate);

        return round($totalTTC, 2);
    }

    public function getTauxTva(): float
    {
        return $this->tvaRate;
    }

    public function getTauxReductionGroupe(): float
    {
        return $this->reductionGroupeTaux;
    }
}
