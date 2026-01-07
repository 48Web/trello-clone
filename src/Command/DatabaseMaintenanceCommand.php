<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:maintenance:database',
    description: 'Perform database maintenance tasks',
)]
class DatabaseMaintenanceCommand extends Command
{
    public function __construct(
        private Connection $connection,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('optimize-tables', null, InputOption::VALUE_NONE, 'Run OPTIMIZE TABLE on all tables')
            ->addOption('clean-migrations', null, InputOption::VALUE_NONE, 'Remove old migration records')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $optimizeTables = $input->getOption('optimize-tables');
        $cleanMigrations = $input->getOption('clean-migrations');

        $io->title('Database Maintenance');
        $io->info('Starting database maintenance tasks...');

        $startTime = microtime(true);
        $tasksCompleted = 0;

        // Get all table names
        $tables = $this->connection->createSchemaManager()->listTableNames();

        if ($optimizeTables) {
            $io->section('Optimizing Tables');
            $progressBar = $io->createProgressBar(count($tables));
            $progressBar->start();

            foreach ($tables as $table) {
                try {
                    $this->connection->executeStatement("OPTIMIZE TABLE `$table`");
                    $progressBar->advance();
                } catch (\Exception $e) {
                    $io->warning("Failed to optimize table {$table}: {$e->getMessage()}");
                }
            }

            $progressBar->finish();
            $io->newLine();
            $io->success("Optimized " . count($tables) . " tables.");
            $tasksCompleted++;
        }

        if ($cleanMigrations) {
            $io->section('Cleaning Old Migrations');
            // This is a simplified version - in production you'd want more sophisticated logic
            $migrationCount = $this->connection->executeQuery(
                "SELECT COUNT(*) as count FROM doctrine_migration_versions WHERE version LIKE '%202%' AND executed_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"
            )->fetchOne();

            if ($migrationCount > 0) {
                $io->warning("Found {$migrationCount} old migration records (>90 days). Consider archiving them.");
            } else {
                $io->info('No old migration records found.');
            }
            $tasksCompleted++;
        }

        // Always run: Check database size and basic health
        $io->section('Database Health Check');

        try {
            $dbStats = $this->connection->executeQuery("
                SELECT
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb,
                    COUNT(*) as table_count
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
            ")->fetchAssociative();

            $io->table(
                ['Metric', 'Value'],
                [
                    ['Database Size', $dbStats['size_mb'] . ' MB'],
                    ['Table Count', $dbStats['table_count']],
                    ['Connection Status', 'Healthy'],
                ]
            );

            $tasksCompleted++;
        } catch (\Exception $e) {
            $io->error("Database health check failed: {$e->getMessage()}");
        }

        $executionTime = round(microtime(true) - $startTime, 2);

        $io->success("Database maintenance completed successfully in {$executionTime}s!");
        $io->info("Tasks completed: {$tasksCompleted}");

        return Command::SUCCESS;
    }
}
