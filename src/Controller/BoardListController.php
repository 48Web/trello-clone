<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Board;
use App\Entity\BoardList;
use App\Repository\BoardListRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class BoardListController extends AbstractController
{
    public function __construct(
        private BoardListRepository $boardListRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    #[Route('/boards/{boardId}/lists', name: 'board_list_create', methods: ['POST'])]
    public function create(Request $request, int $boardId): JsonResponse
    {
        $board = $this->entityManager->getRepository(Board::class)->find($boardId);
        if (!$board) {
            return $this->json(['error' => 'Board not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['title'])) {
            return $this->json(['error' => 'Title is required'], Response::HTTP_BAD_REQUEST);
        }

        $list = new BoardList();
        $list->setTitle($data['title']);
        $list->setBoard($board);

        // Set position as the next available position in this board
        $maxPosition = $this->boardListRepository->createQueryBuilder('l')
            ->select('MAX(l.position)')
            ->where('l.board = :board')
            ->setParameter('board', $board)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
        $list->setPosition($maxPosition + 1);

        $this->entityManager->persist($list);
        $this->entityManager->flush();

        return $this->json($list, Response::HTTP_CREATED, [], [
            'groups' => ['list:read']
        ]);
    }

    #[Route('/lists/{id}', name: 'board_list_update', methods: ['PUT'])]
    public function update(Request $request, BoardList $list): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $list->setTitle($data['title']);
        }

        $this->entityManager->flush();

        return $this->json($list, Response::HTTP_OK, [], [
            'groups' => ['list:read']
        ]);
    }

    #[Route('/lists/{id}/position', name: 'board_list_reorder', methods: ['PUT'])]
    public function reorder(Request $request, BoardList $list): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['position'])) {
            return $this->json(['error' => 'Position is required'], Response::HTTP_BAD_REQUEST);
        }

        $list->setPosition((int) $data['position']);
        $this->entityManager->flush();

        return $this->json($list, Response::HTTP_OK, [], [
            'groups' => ['list:read']
        ]);
    }

    #[Route('/lists/{id}', name: 'board_list_delete', methods: ['DELETE'])]
    public function delete(BoardList $list): JsonResponse
    {
        $this->entityManager->remove($list);
        $this->entityManager->flush();

        return $this->json(['message' => 'List deleted successfully'], Response::HTTP_OK);
    }
}
