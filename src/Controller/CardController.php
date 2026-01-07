<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\BoardList;
use App\Entity\Card;
use App\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class CardController extends AbstractController
{
    public function __construct(
        private CardRepository $cardRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    #[Route('/lists/{listId}/cards', name: 'card_list', methods: ['GET'])]
    public function index(int $listId): JsonResponse
    {
        $list = $this->entityManager->getRepository(BoardList::class)->find($listId);
        if (!$list) {
            return $this->json(['error' => 'List not found'], Response::HTTP_NOT_FOUND);
        }

        $cards = $list->getCards();

        return $this->json($cards, Response::HTTP_OK, [], [
            'groups' => ['card:read']
        ]);
    }

    #[Route('/lists/{listId}/cards', name: 'card_create', methods: ['POST'])]
    public function create(Request $request, int $listId): JsonResponse
    {
        $list = $this->entityManager->getRepository(BoardList::class)->find($listId);
        if (!$list) {
            return $this->json(['error' => 'List not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['title'])) {
            return $this->json(['error' => 'Title is required'], Response::HTTP_BAD_REQUEST);
        }

        $card = new Card();
        $card->setTitle($data['title']);
        $card->setDescription($data['description'] ?? null);
        $card->setList($list);

        // Set position as the next available position in this list
        $maxPosition = $this->cardRepository->createQueryBuilder('c')
            ->select('MAX(c.position)')
            ->where('c.list = :list')
            ->setParameter('list', $list)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
        $card->setPosition($maxPosition + 1);

        $this->entityManager->persist($card);
        $this->entityManager->flush();

        return $this->json($card, Response::HTTP_CREATED, [], [
            'groups' => ['card:read']
        ]);
    }

    #[Route('/cards/{id}', name: 'card_update', methods: ['PUT'])]
    public function update(Request $request, Card $card): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $card->setTitle($data['title']);
        }

        if (isset($data['description'])) {
            $card->setDescription($data['description']);
        }

        $this->entityManager->flush();

        return $this->json($card, Response::HTTP_OK, [], [
            'groups' => ['card:read']
        ]);
    }

    #[Route('/cards/{id}/position', name: 'card_reorder', methods: ['PUT'])]
    public function reorder(Request $request, Card $card): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['position']) || !isset($data['list_id'])) {
            return $this->json(['error' => 'Position and list_id are required'], Response::HTTP_BAD_REQUEST);
        }

        $newList = $this->entityManager->getRepository(BoardList::class)->find($data['list_id']);
        if (!$newList) {
            return $this->json(['error' => 'Target list not found'], Response::HTTP_NOT_FOUND);
        }

        $card->setPosition((int) $data['position']);
        $card->setList($newList);

        $this->entityManager->flush();

        return $this->json($card, Response::HTTP_OK, [], [
            'groups' => ['card:read']
        ]);
    }

    #[Route('/cards/{id}', name: 'card_delete', methods: ['DELETE'])]
    public function delete(Card $card): JsonResponse
    {
        $this->entityManager->remove($card);
        $this->entityManager->flush();

        return $this->json(['message' => 'Card deleted successfully'], Response::HTTP_OK);
    }
}
