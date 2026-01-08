<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Log;
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
        // Laravel-style logging
        Log::info('Dashboard accessed');
        $this->logger->info('Dashboard accessed');

        return $this->render('dashboard.html.twig');
    }

    #[Route('/boards/{id}', name: 'board_view')]
    public function board(int $id): Response
    {
        // Laravel-style logging
        Log::info('Board view accessed', ['board_id' => $id]);
        $this->logger->info('Board view accessed', ['board_id' => $id]);

        return $this->render('board.html.twig', [
            'boardId' => $id,
        ]);
    }
}
