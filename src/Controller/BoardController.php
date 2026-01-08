<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Board;
use App\Entity\BoardList;
use App\Entity\User;
use App\Repository\BoardRepository;
use App\Service\AppLogger;
use App\Service\Log;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/boards')]
final class BoardController extends AbstractController
{
    public function __construct(
        private BoardRepository $boardRepository,
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private AppLogger $logger,
    ) {}

    #[Route('', name: 'board_list', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $boards = $this->boardRepository->findAllOrdered();

        return $this->json($boards, Response::HTTP_OK, [], [
            'groups' => ['board:read']
        ]);
    }

    #[Route('', name: 'board_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $startTime = microtime(true);

        // Laravel-style logging
        Log::info('Board creation request received');
        $this->logger->info('Board creation request received');

        $data = json_decode($request->getContent(), true);

        $this->logger->apiRequest('POST', '/api/boards', [
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
        ]);

        if (!isset($data['title'])) {
            $this->logger->apiResponse('POST', '/api/boards', Response::HTTP_BAD_REQUEST, [
                'error' => 'Title is required',
            ]);
            return $this->json(['error' => 'Title is required'], Response::HTTP_BAD_REQUEST);
        }

        // For simplicity, create a default user. In a real app, this would come from authentication
        $user = $this->entityManager->getRepository(User::class)->find(1);
        if (!$user) {
            $user = new User();
            $user->setName('Default User');
            $user->setEmail('user@example.com');
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        $board = new Board();
        $board->setTitle($data['title']);
        $board->setDescription($data['description'] ?? null);
        $board->setUser($user);

        // Set position as the next available position
        $maxPosition = $this->boardRepository->createQueryBuilder('b')
            ->select('MAX(b.position)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
        $board->setPosition($maxPosition + 1);

        $this->entityManager->persist($board);

        // Create a default list for the new board
        $defaultList = new BoardList();
        $defaultList->setTitle('My First List');
        $defaultList->setBoard($board);
        $defaultList->setPosition(1);

        $this->entityManager->persist($defaultList);
        $this->entityManager->flush();

        // Refresh the board to load the new list relationship
        $this->entityManager->refresh($board);

        $duration = microtime(true) - $startTime;
        $this->logger->performance('board_create', $duration, [
            'board_id' => $board->getId(),
            'title' => $board->getTitle(),
        ]);

        $this->logger->userAction('board_created', null, [
            'board_id' => $board->getId(),
            'board_title' => $board->getTitle(),
        ]);

        $this->logger->apiResponse('POST', '/api/boards', Response::HTTP_CREATED, [
            'board_id' => $board->getId(),
            'lists_count' => $board->getLists()->count(),
        ]);

        return $this->json($board, Response::HTTP_CREATED, [], [
            'groups' => ['board:read']
        ]);
    }

    #[Route('/{id}', name: 'board_show', methods: ['GET'])]
    public function show(Board $board): JsonResponse
    {
        return $this->json($board, Response::HTTP_OK, [], [
            'groups' => ['board:read', 'board:detail']
        ]);
    }

    #[Route('/{id}', name: 'board_update', methods: ['PUT'])]
    public function update(Request $request, Board $board): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $board->setTitle($data['title']);
        }

        if (isset($data['description'])) {
            $board->setDescription($data['description']);
        }

        $this->entityManager->flush();

        return $this->json($board, Response::HTTP_OK, [], [
            'groups' => ['board:read']
        ]);
    }

    #[Route('/{id}', name: 'board_delete', methods: ['DELETE'])]
    public function delete(Board $board): JsonResponse
    {
        $this->entityManager->remove($board);
        $this->entityManager->flush();

        return $this->json(['message' => 'Board deleted successfully'], Response::HTTP_OK);
    }
}
