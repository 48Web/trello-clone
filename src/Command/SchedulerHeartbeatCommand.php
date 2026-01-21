<?php

declare(strict_types=1);

namespace App\Command;

use Psr\Log\LoggerInterface;
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

        $this->logger->info($message, [
            'job_name' => 'scheduler_heartbeat',
        ]);

        $io->success('Scheduler heartbeat logged.');

        return Command::SUCCESS;
    }
}
