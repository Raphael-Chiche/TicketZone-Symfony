<?php

namespace App\DataFixtures;

use App\Entity\Evenement;
use App\Entity\Reservation;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin
            ->setEmail('admin@ticketzone.test')
            ->setRoles(['ROLE_ADMIN'])
            ->setPrenom('Alice')
            ->setNom('Martin');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'password123'));

        $client = new User();
        $client
            ->setEmail('client@ticketzone.test')
            ->setRoles(['ROLE_USER'])
            ->setPrenom('Lucas')
            ->setNom('Bernard');
        $client->setPassword($this->passwordHasher->hashPassword($client, 'password123'));

        $eventPast = new Evenement();
        $eventPast
            ->setTitre('Concert Jazz - Edition 2026')
            ->setDescription('Soirée jazz en centre-ville.')
            ->setLieu('Lyon')
            ->setDateEvenement(new \DateTimeImmutable('-15 days'))
            ->setPrixBase('45.00')
            ->setCapacite(120)
            ->setActif(false)
            ->setCreatedAt(new \DateTimeImmutable('-40 days'));

        $eventFuture1 = new Evenement();
        $eventFuture1
            ->setTitre('Conférence Tech TicketZone')
            ->setDescription('Conférence sur le web et Symfony.')
            ->setLieu('Paris')
            ->setDateEvenement(new \DateTimeImmutable('+20 days'))
            ->setPrixBase('89.90')
            ->setCapacite(300)
            ->setActif(true)
            ->setCreatedAt(new \DateTimeImmutable('-5 days'));

        $eventFuture2 = new Evenement();
        $eventFuture2
            ->setTitre('Salon des Créateurs')
            ->setDescription('Rencontre avec des créateurs locaux.')
            ->setLieu('Marseille')
            ->setDateEvenement(new \DateTimeImmutable('+45 days'))
            ->setPrixBase('25.00')
            ->setCapacite(200)
            ->setActif(true)
            ->setCreatedAt(new \DateTimeImmutable('-2 days'));

        $manager->persist($admin);
        $manager->persist($client);
        $manager->persist($eventPast);
        $manager->persist($eventFuture1);
        $manager->persist($eventFuture2);

        $reservation1 = $this->createReservation(
            $admin,
            $eventFuture1,
            2,
            Reservation::STATUT_CONFIRMEE,
            'TZ-RES-001',
            new \DateTimeImmutable('-3 days')
        );

        $reservation2 = $this->createReservation(
            $admin,
            $eventFuture2,
            1,
            Reservation::STATUT_EN_ATTENTE,
            'TZ-RES-002',
            new \DateTimeImmutable('-2 days')
        );

        $reservation3 = $this->createReservation(
            $client,
            $eventPast,
            3,
            Reservation::STATUT_ANNULEE,
            'TZ-RES-003',
            new \DateTimeImmutable('-10 days')
        );

        $reservation4 = $this->createReservation(
            $client,
            $eventFuture1,
            4,
            Reservation::STATUT_CONFIRMEE,
            'TZ-RES-004',
            new \DateTimeImmutable('-1 day')
        );

        foreach ([$reservation1, $reservation2, $reservation3, $reservation4] as $reservation) {
            $manager->persist($reservation);
        }

        $manager->flush();
    }

    private function createReservation(
        User $user,
        Evenement $evenement,
        int $quantite,
        string $statut,
        string $reference,
        \DateTimeImmutable $createdAt
    ): Reservation {
        $reservation = new Reservation();
        $reservation
            ->setUser($user)
            ->setEvenement($evenement)
            ->setQuantite($quantite)
            ->setStatut($statut)
            ->setReference($reference)
            ->setTotal(number_format(((float) $evenement->getPrixBase()) * $quantite, 2, '.', ''))
            ->setCreatedAt($createdAt);

        return $reservation;
    }
}