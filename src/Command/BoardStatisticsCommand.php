<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\BoardRepository;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Cache\ItemInterface;

#[AsCommand(
    name: 'app:stats:cache',
    description: 'Update board statistics and cache metrics',
)]
class BoardStatisticsCommand extends Command
{
    public function __construct(
        private BoardRepository $boardRepository,
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

        // Try to cache the statistics in Redis
        try {
            $cache = RedisAdapter::createConnection($_ENV['REDIS_URL'] ?? 'redis://127.0.0.1:6379');
            $redis = new \Redis();
            $redis->connect(parse_url($cache, PHP_URL_HOST), parse_url($cache, PHP_URL_PORT));

            $redis->set('trello:stats:last_update', time());
            $redis->set('trello:stats:data', json_encode($stats));

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
