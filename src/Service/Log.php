<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;

/**
 * Laravel-style Log facade for Symfony
 */
class Log
{
    private static LoggerInterface $logger;

    public static function setLogger(LoggerInterface $logger): void
    {
        self::$logger = $logger;
    }

    public static function info(string $message, array $context = []): void
    {
        self::$logger->info($message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::$logger->warning($message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::$logger->error($message, $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        self::$logger->debug($message, $context);
    }
}