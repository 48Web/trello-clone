<?php

declare(strict_types=1);

namespace App\Command;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:scheduler:heartbeat',
    description: 'Emit a log line for scheduler heartbeat testing',
)]
class SchedulerHeartbeatCommand extends Command
{
    public function __construct(
        #[Autowire(service: 'monolog.logger.scheduler')]
        private LoggerInterface $logger,
        #[Autowire(service: 'scheduler_debug_cache_pool')]
        private CacheInterface $cache,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('message', null, InputOption::VALUE_OPTIONAL, 'Message to log', 'scheduler heartbeat');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $message = (string) $input->getOption('message');

        $payload = [
            'message' => $message,
            'ran_at' => date('c'),
        ];

        $this->logger->info($message, [
            'job_name' => 'scheduler_heartbeat',
        ]);

        // Store last run in cache so we can verify scheduling without logs.
        $this->cache->delete('scheduler_heartbeat_last_run');
        $this->cache->get('scheduler_heartbeat_last_run', function (ItemInterface $item) use ($payload): array {
            $item->expiresAfter(86400);

            return $payload;
        });

        $io->success('Scheduler heartbeat logged.');

        return Command::SUCCESS;
    }
}
