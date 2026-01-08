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

#[AsCommand(
    name: 'app:log:test',
    description: 'Test logging functionality at all levels',
)]
class LogTestCommand extends Command
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('level', null, InputOption::VALUE_OPTIONAL, 'Specific log level to test', 'all')
            ->addOption('count', null, InputOption::VALUE_OPTIONAL, 'Number of log entries to generate', '1')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $level = $input->getOption('level');
        $count = (int) $input->getOption('count');

        $io->title('Log Test Command');
        $io->info("Testing logging functionality...");

        $levels = ['debug', 'info', 'warning', 'error'];
        if ($level !== 'all' && in_array($level, $levels)) {
            $levels = [$level];
        }

        $generated = 0;
        for ($i = 0; $i < $count; $i++) {
            foreach ($levels as $logLevel) {
                $message = "TEST LOG [{$logLevel}]: Entry #{$i} - " . date('Y-m-d H:i:s');
                $context = [
                    'test_run' => $i,
                    'log_level' => $logLevel,
                    'timestamp' => time(),
                    'command' => 'app:log:test'
                ];

                match ($logLevel) {
                    'debug' => $this->logger->debug($message, $context),
                    'info' => $this->logger->info($message, $context),
                    'warning' => $this->logger->warning($message, $context),
                    'error' => $this->logger->error($message, $context),
                };

                $generated++;
            }
        }

        // Also test error_log for Laravel Cloud compatibility
        error_log('PHP_ERROR_LOG_TEST: Direct error_log call for Laravel Cloud compatibility');

        $io->success("Generated {$generated} log entries across " . count($levels) . " levels");
        $io->info("Check var/log/app.log for the log entries");
        $io->info("In Laravel Cloud, check the logs dashboard");

        return Command::SUCCESS;
    }
}
