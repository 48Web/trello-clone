<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;

class SchedulerDebugController extends AbstractController
{
    public function __construct(
        #[Autowire(service: 'scheduler_debug_cache_pool')]
        private CacheInterface $cache,
    ) {
    }

    #[Route('/scheduler/last-run', name: 'scheduler_last_run', methods: ['GET'])]
    public function lastRun(): JsonResponse
    {
        $payload = $this->cache->get('scheduler_heartbeat_last_run', fn () => null);

        return $this->json([
            'last_run' => $payload,
        ]);
    }
}
