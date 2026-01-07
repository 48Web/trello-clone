<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FrontendController extends AbstractController
{
    #[Route('/', name: 'dashboard')]
    public function dashboard(): Response
    {
        return $this->render('dashboard.html.twig');
    }

    #[Route('/boards/{id}', name: 'board_view')]
    public function board(int $id): Response
    {
        return $this->render('board.html.twig', [
            'boardId' => $id,
        ]);
    }
}
