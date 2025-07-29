<?php

namespace App\Booking\Infrastructure\Command;

use App\Booking\Domain\Entity\Booking;
use App\Booking\Infrastructure\Redis\BookingRedisWriter;
use App\Booking\Infrastructure\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:redis:index-bookings',
    description: 'Indexes all existing bookings from the database into Redis.',
)]
class RedisIndexBookingsCommand extends Command
{
    private const BATCH_SIZE = 100;

    public function __construct(
        private readonly BookingRepository $bookingRepository,
        private readonly BookingRedisWriter $writer,
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '1024M'); // Increase memory limit
        $io = new SymfonyStyle($input, $output);
        $io->title('Starting booking indexation into Redis');

        $totalBookings = $this->bookingRepository->count([]);

        if ($totalBookings === 0) {
            $io->success('No bookings found to index.');
            return Command::SUCCESS;
        }

        $io->comment(sprintf('Found %d bookings to index.', $totalBookings));

        $progressBar = new ProgressBar($output, $totalBookings);
        $progressBar->start();

        $offset = 0;
        while ($bookings = $this->bookingRepository->findBy([], ['created_at' => 'ASC'], self::BATCH_SIZE, $offset)) {
            /** @var Booking $booking */
            foreach ($bookings as $booking) {
                $this->writer->save($booking);
                $progressBar->advance();
            }

            $this->entityManager->clear();
            $offset += self::BATCH_SIZE;
        }

        $progressBar->finish();
        $io->newLine(2);
        $io->success(sprintf('Successfully indexed %d bookings into Redis.', $totalBookings));

        return Command::SUCCESS;
    }
}

