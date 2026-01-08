<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FrontendController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    #[Route('/', name: 'dashboard')]
    public function dashboard(): Response
    {
        // Multiple logging methods for Laravel Cloud testing
        $this->logger->info('Dashboard accessed');
        error_log('Dashboard: Page loaded');

        return $this->render('dashboard.html.twig');
    }

    #[Route('/boards/{id}', name: 'board_view')]
    public function board(int $id): Response
    {
        // Standard Symfony logging
        $this->logger->info('Board view accessed', ['board_id' => $id]);
        error_log('Board view: Loading board ' . $id);

        return $this->render('board.html.twig', [
            'boardId' => $id,
        ]);
    }

    #[Route('/test-log', name: 'test_log')]
    public function testLog(): Response
    {
        // Multiple logging methods for Laravel Cloud testing
        $this->logger->info('HTTP LOG TEST: Manual test triggered');
        $this->logger->warning('HTTP LOG TEST: Warning level');
        error_log('HTTP LOG TEST: Direct error_log call');

        return $this->json([
            'message' => 'Log test completed',
            'timestamp' => (new \DateTime())->format('c'),
            'methods_tested' => ['logger', 'error_log']
        ]);
    }
}
