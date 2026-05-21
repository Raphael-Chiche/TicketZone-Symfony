<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Message\RappelEvenementMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/evenements', name: 'api_evenements_')]
class EvenementController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MessageBusInterface $messageBus,
    ) {}

    #[Route(methods: 'POST', name: 'create')]
    public function create(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['titre'], $data['lieu'], $data['dateEvenement'], $data['prixBase'], $data['capacite'])) {
            return new JsonResponse([
                'error' => 'Paramètres manquants',
            ], Response::HTTP_BAD_REQUEST);
        }

        $evenement = new Evenement();
        $evenement
            ->setTitre($data['titre'])
            ->setLieu($data['lieu'])
            ->setDateEvenement(new \DateTimeImmutable($data['dateEvenement']))
            ->setPrixBase($data['prixBase'])
            ->setCapacite((int) $data['capacite'])
            ->setActif($data['actif'] ?? true);

        if (isset($data['description'])) {
            $evenement->setDescription($data['description']);
        }

        $this->em->persist($evenement);
        $this->em->flush();

        $this->messageBus->dispatch(new RappelEvenementMessage($evenement->getId()));

        return new JsonResponse([
            'id' => $evenement->getId(),
            'titre' => $evenement->getTitre(),
            'message' => 'Événement créé avec succès et rappels envoyés aux participants.',
        ], Response::HTTP_CREATED);
    }
}
