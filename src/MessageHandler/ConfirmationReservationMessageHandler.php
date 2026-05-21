<?php

namespace App\MessageHandler;

use App\Entity\Reservation;
use App\Message\ConfirmationReservationMessage;
use App\Repository\ReservationRepository;
use App\Service\ReservationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ConfirmationReservationMessageHandler
{
    public function __construct(
        private readonly ReservationRepository $reservationRepository,
        private readonly ReservationService $reservationService,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(ConfirmationReservationMessage $message): void
    {
        $reservation = $this->reservationRepository->find($message->reservationId);

        if (!$reservation) {
            $this->logger->warning(sprintf(
                'Réservation introuvable : %d',
                $message->reservationId
            ));
            return;
        }

        $user = $reservation->getUser();
        $userEmail = $user?->getEmail() ?? 'unknown';

        $this->logger->info(sprintf(
            '[EMAIL] Confirmation de réservation envoyée à %s - Référence: %s',
            $userEmail,
            $reservation->getReference()
        ));

        $this->reservationService->confirmer($reservation);
    }
}
