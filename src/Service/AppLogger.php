<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;

class AppLogger
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    // Application-level logging
    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $this->enrichContext($context));
    }

    public function warning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $this->enrichContext($context));
    }

    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $this->enrichContext($context));
    }

    // API-specific logging
    public function apiRequest(string $method, string $endpoint, array $context = []): void
    {
        $context['api_method'] = $method;
        $context['api_endpoint'] = $endpoint;
        $this->logger->info("API Request: {$method} {$endpoint}", $this->enrichContext($context));
    }

    public function apiResponse(string $method, string $endpoint, int $statusCode, array $context = []): void
    {
        $context['api_method'] = $method;
        $context['api_endpoint'] = $endpoint;
        $context['status_code'] = $statusCode;
        $level = $statusCode >= 400 ? 'warning' : 'info';
        $this->logger->log($level, "API Response: {$method} {$endpoint} - {$statusCode}", $this->enrichContext($context));
    }

    // Scheduler job logging
    public function schedulerStart(string $jobName, array $context = []): void
    {
        $context['job_name'] = $jobName;
        $this->logger->info("Scheduler: Starting job '{$jobName}'", $this->enrichContext($context));
    }

    public function schedulerSuccess(string $jobName, float $duration, array $context = []): void
    {
        $context['job_name'] = $jobName;
        $context['duration_seconds'] = round($duration, 2);
        $this->logger->info("Scheduler: Completed job '{$jobName}' in {$context['duration_seconds']}s", $this->enrichContext($context));
    }

    public function schedulerError(string $jobName, string $error, array $context = []): void
    {
        $context['job_name'] = $jobName;
        $context['error'] = $error;
        $this->logger->error("Scheduler: Failed job '{$jobName}' - {$error}", $this->enrichContext($context));
    }

    // Cleanup operation logging
    public function cleanupStart(string $operation, array $context = []): void
    {
        $context['operation'] = $operation;
        $this->logger->info("Cleanup: Starting '{$operation}'", $this->enrichContext($context));
    }

    public function cleanupComplete(string $operation, int $processed, int $errors, array $context = []): void
    {
        $context['operation'] = $operation;
        $context['processed_count'] = $processed;
        $context['error_count'] = $errors;
        $level = $errors > 0 ? 'warning' : 'info';
        $this->logger->log($level, "Cleanup: Completed '{$operation}' - {$processed} processed, {$errors} errors", $this->enrichContext($context));
    }

    // Security logging
    public function securityEvent(string $event, string $ip = null, array $context = []): void
    {
        $context['security_event'] = $event;
        $context['ip_address'] = $ip ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $this->logger->warning("Security: {$event}", $this->enrichContext($context));
    }

    // User action logging
    public function userAction(string $action, int $userId = null, array $context = []): void
    {
        $context['user_action'] = $action;
        $context['user_id'] = $userId;
        $context['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $this->logger->info("User Action: {$action}", $this->enrichContext($context));
    }

    // Performance logging
    public function performance(string $operation, float $duration, array $context = []): void
    {
        $context['operation'] = $operation;
        $context['duration_seconds'] = round($duration, 3);
        $this->logger->info("Performance: {$operation} took {$context['duration_seconds']}s", $this->enrichContext($context));
    }

    // System health logging
    public function healthCheck(string $component, string $status, array $context = []): void
    {
        $context['component'] = $component;
        $context['status'] = $status;
        $level = in_array($status, ['error', 'failed']) ? 'error' : 'info';
        $this->logger->log($level, "Health Check: {$component} - {$status}", $this->enrichContext($context));
    }

    private function enrichContext(array $context): array
    {
        return array_merge($context, [
            'timestamp' => date('c'),
            'request_id' => $_SERVER['REQUEST_ID'] ?? uniqid('req_', true),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'url' => $_SERVER['REQUEST_URI'] ?? 'cli',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
        ]);
    }
}