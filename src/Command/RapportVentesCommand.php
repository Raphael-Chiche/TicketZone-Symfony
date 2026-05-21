<?php

namespace App\Command;

use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:rapport-ventes',
    description: 'Affiche un rapport de ventes pour un mois donné.'
)]
class RapportVentesCommand extends Command
{
    public function __construct(
        private readonly ReservationRepository $reservationRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('mois', InputArgument::REQUIRED, 'Mois au format YYYY-MM');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $mois = $input->getArgument('mois');

        $date = \DateTimeImmutable::createFromFormat('Y-m', $mois);
        if (!$date || $date->format('Y-m') !== $mois) {
            $io->error('Le mois doit être au format YYYY-MM.');
            return Command::FAILURE;
        }

        $debut = $date->modify('first day of this month')->setTime(0, 0, 0);
        $fin = $debut->modify('first day of next month');

        $qb = $this->reservationRepository->createQueryBuilder('r')
            ->select('COUNT(r.id) as reservationCount', 'SUM(r.total) as chiffreAffaires')
            ->join('r.evenement', 'e')
            ->andWhere('r.statut = :statut')
            ->andWhere('r.createdAt >= :debut')
            ->andWhere('r.createdAt < :fin')
            ->setParameter('statut', Reservation::STATUT_CONFIRMEE)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin);

        $result = $qb->getQuery()->getSingleResult();
        $reservationCount = (int) $result['reservationCount'];
        $chiffreAffaires = $result['chiffreAffaires'] !== null ? (float) $result['chiffreAffaires'] : 0.0;

        if ($reservationCount === 0) {
            $io->error('Aucune réservation confirmée trouvée pour ce mois.');
            return Command::FAILURE;
        }

        $topQb = $this->reservationRepository->createQueryBuilder('r')
            ->select('e.titre as titre', 'SUM(r.quantite) as placesVendues')
            ->join('r.evenement', 'e')
            ->andWhere('r.statut = :statut')
            ->andWhere('r.createdAt >= :debut')
            ->andWhere('r.createdAt < :fin')
            ->groupBy('e.id')
            ->orderBy('placesVendues', 'DESC')
            ->setMaxResults(3)
            ->setParameter('statut', Reservation::STATUT_CONFIRMEE)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin);

        $topEvents = $topQb->getQuery()->getResult();

        $io->section(sprintf('Rapport des ventes pour %s', $mois));
        $io->writeln(sprintf('Nombre total de réservations confirmées : %d', $reservationCount));
        $io->writeln(sprintf('Chiffre d’affaires total : %s €', number_format($chiffreAffaires, 2, '.', '')));

        if (count($topEvents) > 0) {
            $io->writeln('Top 3 des événements par nombre de places vendues :');
            $io->table(['Événement', 'Places vendues'], array_map(static function ($row) {
                return [$row['titre'], $row['placesVendues']];
            }, $topEvents));
        } else {
            $io->writeln('Aucun événement vendu pour ce mois.');
        }

        return Command::SUCCESS;
    }
}
