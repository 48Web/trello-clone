<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\BoardRepository;
use Predis\Client;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:stats:cache',
    description: 'Update board statistics and cache metrics',
)]
class BoardStatisticsCommand extends Command
{
    public function __construct(
        private BoardRepository $boardRepository,
        private Client $redisClient,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Board Statistics Cache Update');
        $io->info('Updating board statistics and caching metrics...');

        $boards = $this->boardRepository->findAll();
        $stats = [
            'total_boards' => count($boards),
            'boards_with_lists' => 0,
            'total_lists' => 0,
            'total_cards' => 0,
            'board_stats' => [],
        ];

        $progressBar = $io->createProgressBar(count($boards));
        $progressBar->start();

        foreach ($boards as $board) {
            $boardStats = [
                'id' => $board->getId(),
                'title' => $board->getTitle(),
                'lists_count' => $board->getLists()->count(),
                'cards_count' => 0,
                'last_activity' => $board->getCreatedAt()->format('Y-m-d H:i:s'),
            ];

            if ($board->getLists()->count() > 0) {
                $stats['boards_with_lists']++;
            }

            $stats['total_lists'] += $board->getLists()->count();

            // Count cards in all lists
            foreach ($board->getLists() as $list) {
                $boardStats['cards_count'] += $list->getCards()->count();
            }

            $stats['total_cards'] += $boardStats['cards_count'];
            $stats['board_stats'][] = $boardStats;

            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);

        // Try to cache the statistics using Symfony cache
        try {
            $this->redisClient->set('trello:stats:last_update', time());
            $this->redisClient->set('trello:stats:data', json_encode($stats));
            $this->redisClient->disconnect();

            $io->success('Statistics cached successfully in Redis.');
        } catch (\Exception $e) {
            $io->warning('Redis caching failed: ' . $e->getMessage());
        }

        $io->table(
            ['Metric', 'Value'],
            [
                ['Total Boards', $stats['total_boards']],
                ['Boards with Lists', $stats['boards_with_lists']],
                ['Total Lists', $stats['total_lists']],
                ['Total Cards', $stats['total_cards']],
            ]
        );

        $io->success('Board statistics update completed successfully!');

        return Command::SUCCESS;
    }
}
