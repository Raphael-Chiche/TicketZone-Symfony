<?php

namespace App\Service;

use App\Entity\Evenement;
use App\Entity\Reservation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ReservationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TarifService $tarifService,
    ) {}

    public function reserver(User $user, Evenement $evenement, int $quantite): Reservation
    {
        if ($quantite <= 0) {
            throw new \InvalidArgumentException('La quantité doit être supérieure à 0.');
        }

        $places = $this->getPlacesDisponibles($evenement);
        if ($quantite > $places) {
            throw new \RuntimeException('Pas assez de places disponibles.');
        }

        $reservation = new Reservation();
        $reservation
            ->setUser($user)
            ->setEvenement($evenement)
            ->setQuantite($quantite)
            ->setStatut(Reservation::STATUT_EN_ATTENTE)
            ->setTotal(number_format($this->tarifService->calculerTotal($evenement, $quantite), 2, '.', ''));

        $this->em->persist($reservation);
        $this->em->flush();

        return $reservation;
    }

    public function confirmer(Reservation $reservation): void
    {
        $reservation->setStatut(Reservation::STATUT_CONFIRMEE);
        $this->em->persist($reservation);
        $this->em->flush();
    }

    public function annuler(Reservation $reservation): void
    {
        $reservation->setStatut(Reservation::STATUT_ANNULEE);
        $this->em->persist($reservation);
        $this->em->flush();
    }

    public function getPlacesDisponibles(Evenement $evenement): int
    {
        $capacite = (int) $evenement->getCapacite();
        $reservees = 0;

        foreach ($evenement->getReservations() as $r) {
            if ($r instanceof Reservation) {
                if ($r->getStatut() !== Reservation::STATUT_ANNULEE) {
                    $reservees += (int) $r->getQuantite();
                }
            }
        }

        $disponibles = $capacite - $reservees;

        return $disponibles < 0 ? 0 : $disponibles;
    }
}
