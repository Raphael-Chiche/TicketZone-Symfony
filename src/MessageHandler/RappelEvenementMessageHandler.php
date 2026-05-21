<?php

namespace App\MessageHandler;

use App\Entity\Reservation;
use App\Message\RappelEvenementMessage;
use App\Repository\EvenementRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class RappelEvenementMessageHandler
{
    public function __construct(
        private readonly EvenementRepository $evenementRepository,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(RappelEvenementMessage $message): void
    {
        $evenement = $this->evenementRepository->find($message->evenementId);

        if (!$evenement) {
            $this->logger->warning(sprintf(
                'Événement introuvable : %d',
                $message->evenementId
            ));
            return;
        }

        $reservations = $evenement->getReservations();
        $count = 0;

        foreach ($reservations as $reservation) {
            if ($reservation->getStatut() === Reservation::STATUT_CONFIRMEE) {
                $user = $reservation->getUser();
                $userEmail = $user?->getEmail() ?? 'unknown';

                $this->logger->info(sprintf(
                    '[EMAIL] Rappel d\'événement envoyé à %s - Événement: %s (%s)',
                    $userEmail,
                    $evenement->getTitre(),
                    $evenement->getDateEvenement()?->format('d/m/Y H:i') ?? 'date inconnue'
                ));
                $count++;
            }
        }

        $this->logger->info(sprintf(
            'Rappels d\'événement envoyés : %d participants notifiés pour "%s"',
            $count,
            $evenement->getTitre()
        ));
    }
}
