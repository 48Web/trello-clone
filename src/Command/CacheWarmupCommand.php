<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\BoardRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Cache\CacheInterface;

#[AsCommand(
    name: 'app:cache:warmup',
    description: 'Warm up application caches for better performance',
)]
class CacheWarmupCommand extends Command
{
    public function __construct(
        private BoardRepository $boardRepository,
        private CacheInterface $cache,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Cache Warmup');
        $io->info('Warming up application caches...');

        $startTime = microtime(true);
        $itemsWarmed = 0;

        // Warm up board data
        $io->section('Warming Board Cache');
        $boards = $this->boardRepository->findAll();

        $progressBar = $io->createProgressBar(count($boards));
        $progressBar->start();

        foreach ($boards as $board) {
            $cacheKey = "board_{$board->getId()}_data";

            $this->cache->get($cacheKey, function () use ($board) {
                return [
                    'id' => $board->getId(),
                    'title' => $board->getTitle(),
                    'description' => $board->getDescription(),
                    'lists_count' => $board->getLists()->count(),
                    'created_at' => $board->getCreatedAt()->format('Y-m-d H:i:s'),
                ];
            });

            $itemsWarmed++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine();

        // Warm up popular queries
        $io->section('Warming Popular Queries');

        // Cache board count
        $this->cache->get('boards_total_count', function () {
            return $this->boardRepository->createQueryBuilder('b')
                ->select('COUNT(b.id)')
                ->getQuery()
                ->getSingleScalarResult();
        });
        $itemsWarmed++;

        // Cache recent boards
        $this->cache->get('boards_recent', function () {
            return $this->boardRepository->createQueryBuilder('b')
                ->orderBy('b.createdAt', 'DESC')
                ->setMaxResults(5)
                ->getQuery()
                ->getResult();
        });
        $itemsWarmed++;

        $executionTime = round(microtime(true) - $startTime, 2);

        $io->success("Cache warmup completed successfully in {$executionTime}s!");
        $io->info("Cache items warmed: {$itemsWarmed}");

        return Command::SUCCESS;
    }
}
