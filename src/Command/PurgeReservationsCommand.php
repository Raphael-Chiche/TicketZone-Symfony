<?php

namespace App\Command;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:purge-reservations',
    description: 'Annule automatiquement les réservations en attente dépassant le délai de purge configuré.'
)]
class PurgeReservationsCommand extends Command
{
    public function __construct(
        private readonly ReservationRepository $reservationRepository,
        private readonly EntityManagerInterface $em,
        #[Autowire('%app.delai_purge_minutes%')]
        private readonly int $delaiPurgeMinutes,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $title = sprintf('Purge des réservations en attente (seuil : %d min)', $this->delaiPurgeMinutes);
        $io->writeln($title);
        $io->writeln(str_repeat('=', max(0, strlen($title))));

        $threshold = new \DateTimeImmutable(sprintf('-%d minutes', $this->delaiPurgeMinutes));

        $reservations = $this->reservationRepository->createQueryBuilder('r')
            ->join('r.user', 'u')
            ->andWhere('r.statut = :statut')
            ->andWhere('r.createdAt < :threshold')
            ->setParameter('statut', Reservation::STATUT_EN_ATTENTE)
            ->setParameter('threshold', $threshold)
            ->orderBy('r.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        $count = count($reservations);
        $io->writeln(sprintf('%d réservation(s) en attente trouvée(s).', $count));

        if ($count > 0) {
            $rows = [];
            $now = new \DateTimeImmutable();
            foreach ($reservations as $reservation) {
                $createdAt = $reservation->getCreatedAt();
                $minutes = (int) floor(($now->getTimestamp() - $createdAt->getTimestamp()) / 60);
                $rows[] = [
                    $reservation->getReference(),
                    $reservation->getUser()?->getEmail() ?? '—',
                    sprintf('%d min', $minutes),
                ];
            }

            $io->table(['Référence', 'Utilisateur', 'Créée il y a'], $rows);

            foreach ($reservations as $reservation) {
                $reservation->setStatut(Reservation::STATUT_ANNULEE);
                $this->em->persist($reservation);
            }
            $this->em->flush();

            $io->success(sprintf('%d réservation(s) annulée(s).', $count));
        } else {
            $io->success('Aucune réservation en attente à annuler.');
        }

        return Command::SUCCESS;
    }
}
