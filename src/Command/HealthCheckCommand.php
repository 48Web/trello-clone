<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\AppLogger;
use App\Service\CloudflareR2Client;
use Doctrine\DBAL\Connection;
use Predis\Client;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:health:check',
    description: 'Perform system health checks',
)]
class HealthCheckCommand extends Command
{
    public function __construct(
        private Connection $connection,
        private Client $redisClient,
        private CloudflareR2Client $r2Client,
        private AppLogger $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $startTime = microtime(true);

        $this->logger->schedulerStart('health_check', [
            'command' => 'app:health:check',
        ]);

        $io->title('System Health Check');
        $io->info('Checking system components...');

        $checks = [];
        $allPassed = true;

        // Database check
        $io->section('Database Check');
        try {
            $this->connection->executeQuery('SELECT 1')->fetchOne();
            $io->success('✅ Database connection: OK');
            $checks['database'] = '✅ OK';
            $this->logger->healthCheck('database', 'success');
        } catch (\Exception $e) {
            $io->error("❌ Database connection: FAILED ({$e->getMessage()})");
            $checks['database'] = '❌ FAILED';
            $allPassed = false;
            $this->logger->healthCheck('database', 'error', ['error' => $e->getMessage()]);
        }

        // Redis check
        $io->section('Redis Check');
        try {
            // Uses Predis configured via Symfony's standard REDIS_URL.
            $this->redisClient->ping();
            $this->redisClient->disconnect();
            $io->success('✅ Redis connection: OK (REDIS_URL)');
            $checks['redis'] = '✅ OK';
            $this->logger->healthCheck('redis', 'success', ['connection_type' => 'REDIS_URL']);
        } catch (\Exception $e) {
            $io->error("❌ Redis connection: FAILED ({$e->getMessage()})");
            $checks['redis'] = '❌ FAILED';
            $allPassed = false;
            $this->logger->healthCheck('redis', 'error', ['error' => $e->getMessage()]);
        }

        // R2 Storage check
        $io->section('Cloudflare R2 Check');
        try {
            $filesystem = $this->r2Client->getFilesystem();
            // Try to list contents (will fail gracefully if bucket doesn't exist)
            $filesystem->listContents('', false);
            $io->success('✅ R2 storage connection: OK');
            $checks['r2'] = '✅ OK';
            $this->logger->healthCheck('r2_storage', 'success');
        } catch (\Exception $e) {
            $io->warning("⚠️ R2 storage connection: LIMITED ({$e->getMessage()})");
            $checks['r2'] = '⚠️ LIMITED';
            $this->logger->healthCheck('r2_storage', 'warning', ['error' => $e->getMessage()]);
        }

        // Disk space check
        $io->section('Disk Space Check');
        $freeSpace = disk_free_space('/');
        $totalSpace = disk_total_space('/');
        $freePercent = round(($freeSpace / $totalSpace) * 100, 1);

        if ($freePercent > 10) {
            $io->success("✅ Disk space: {$freePercent}% free");
            $checks['disk'] = "✅ {$freePercent}% free";
        } else {
            $io->error("❌ Disk space: {$freePercent}% free (LOW)");
            $checks['disk'] = "❌ {$freePercent}% free";
            $allPassed = false;
        }

        // Memory check
        $io->section('Memory Check');
        $memoryUsage = memory_get_peak_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));

        if ($memoryLimit > 0) {
            $usagePercent = round(($memoryUsage / $memoryLimit) * 100, 1);
            if ($usagePercent < 80) {
                $io->success("✅ Memory usage: {$usagePercent}% of limit");
                $checks['memory'] = "✅ {$usagePercent}%";
            } else {
                $io->warning("⚠️ Memory usage: {$usagePercent}% of limit (HIGH)");
                $checks['memory'] = "⚠️ {$usagePercent}%";
            }
        } else {
            $io->info('ℹ️ Memory limit: Unlimited');
            $checks['memory'] = 'ℹ️ Unlimited';
        }

        $io->newLine();
        $io->table(
            ['Component', 'Status'],
            array_map(fn($component, $status) => [$component, $status], array_keys($checks), $checks)
        );

        $duration = microtime(true) - $startTime;

        if ($allPassed) {
            $this->logger->schedulerSuccess('health_check', $duration, [
                'checks_passed' => count(array_filter($checks, fn($status) => str_contains($status, '✅'))),
                'total_checks' => count($checks),
            ]);
            $io->success('🎉 All health checks passed! System is healthy.');
            return Command::SUCCESS;
        } else {
            $this->logger->schedulerError('health_check', 'Some health checks failed', [
                'failed_checks' => array_filter($checks, fn($status) => str_contains($status, '❌')),
                'total_checks' => count($checks),
            ]);
            $io->error('⚠️ Some health checks failed. Please review the issues above.');
            return Command::FAILURE;
        }
    }

    private function parseMemoryLimit(string $limit): int
    {
        if ($limit === '-1') {
            return 0; // Unlimited
        }

        $unit = strtolower(substr($limit, -1));
        $value = (int) substr($limit, 0, -1);

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }
}
