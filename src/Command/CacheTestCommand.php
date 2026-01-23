<?php

declare(strict_types=1);

namespace App\Command;

use Predis\Client;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\Cache\CacheItemInterface;

#[AsCommand(
    name: 'app:cache:test',
    description: 'Test cache read/write and Redis metrics',
)]
class CacheTestCommand extends Command
{
    public function __construct(
        #[Autowire(service: 'cache.app')]
        private CacheItemPoolInterface $cache,
        private Client $redisClient,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Cache Test');

        $key = 'cache_test_' . bin2hex(random_bytes(8));
        $payload = [
            'timestamp' => date('c'),
            'random' => bin2hex(random_bytes(4)),
        ];

        $io->section('Cache write/read');
        $this->cache->deleteItem($key);

        $item = $this->cache->getItem($key);
        $item->expiresAfter(60);
        $item->set($payload);
        $this->cache->save($item);

        $loadedItem = $this->cache->getItem($key);
        $hit = $loadedItem->isHit();
        $value = $loadedItem->get();

        if ($hit && $value === $payload) {
            $io->success('Cache read/write OK');
        } else {
            $io->error('Cache read/write FAILED');
            $io->text([
                'Hit: ' . ($hit ? 'true' : 'false'),
                'Expected: ' . json_encode($payload, JSON_THROW_ON_ERROR),
                'Actual: ' . json_encode($value, JSON_THROW_ON_ERROR),
            ]);
            return Command::FAILURE;
        }

        $this->cache->deleteItem($key);

        $io->section('Redis metrics');
        try {
            $ping = $this->redisClient->ping();
            $info = $this->redisClient->info();
            $dbSize = $this->redisClient->dbsize();

            $memory = $info['Memory'] ?? $info['memory'] ?? [];
            $stats = $info['Stats'] ?? $info['stats'] ?? [];
            $keyspace = $info['Keyspace'] ?? $info['keyspace'] ?? [];

            $io->table(
                ['Metric', 'Value'],
                [
                    ['Ping', (string) $ping],
                    ['DB Size', (string) $dbSize],
                    ['Memory Used', (string) ($memory['used_memory_human'] ?? 'n/a')],
                    ['Peak Memory', (string) ($memory['used_memory_peak_human'] ?? 'n/a')],
                    ['Total Commands', (string) ($stats['total_commands_processed'] ?? 'n/a')],
                    ['Keyspace', is_array($keyspace) ? json_encode($keyspace, JSON_THROW_ON_ERROR) : (string) $keyspace],
                ]
            );
        } catch (\Throwable $exception) {
            $io->warning('Redis metrics unavailable');
            $io->text($exception->getMessage());
        }

        $io->success('Cache test completed.');

        return Command::SUCCESS;
    }
}
