<?php

namespace App\Controller;

use App\Repository\EvenementRepository;
use App\Repository\UserRepository;
use App\Service\ReservationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/reservations', name: 'api_reservations_')]
class ReservationController extends AbstractController
{
    public function __construct(
        private readonly ReservationService $reservationService,
        private readonly UserRepository $userRepository,
        private readonly EvenementRepository $evenementRepository,
    ) {
    }

    #[Route(methods: 'POST', name: 'create')]
    public function create(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['user_id'], $data['evenement_id'], $data['quantite'])) {
            return new JsonResponse([
                'error' => 'Paramètres manquants (user_id, evenement_id, quantite)',
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->find($data['user_id']);
        $evenement = $this->evenementRepository->find($data['evenement_id']);

        if (!$user || !$evenement) {
            return new JsonResponse([
                'error' => 'User ou Événement non trouvé',
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $reservation = $this->reservationService->reserver($user, $evenement, (int) $data['quantite']);

            return new JsonResponse([
                'id' => $reservation->getId(),
                'reference' => $reservation->getReference(),
                'total' => $reservation->getTotal(),
                'statut' => $reservation->getStatut(),
                'message' => 'Réservation créée et confirmation envoyée.'
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}