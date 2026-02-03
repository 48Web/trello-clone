<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Attachment;
use App\Entity\Card;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToGenerateTemporaryUrl;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class AttachmentController extends AbstractController
{
    public function __construct(
        #[Autowire(service: 'default.storage')]
        private FilesystemOperator $storage,
        private EntityManagerInterface $entityManager,
    ) {}

    #[Route('/cards/{cardId}/attachments', name: 'attachment_upload', methods: ['POST'])]
    public function upload(Request $request, int $cardId): JsonResponse
    {
        $card = $this->entityManager->getRepository(Card::class)->find($cardId);
        if (!$card) {
            return $this->json(['error' => 'Card not found'], Response::HTTP_NOT_FOUND);
        }

        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'No file uploaded'], Response::HTTP_BAD_REQUEST);
        }

        // Validate file type (only images for now)
        if (!str_starts_with($file->getMimeType(), 'image/')) {
            return $this->json(['error' => 'Only image files are allowed'], Response::HTTP_BAD_REQUEST);
        }

        // Generate unique filename
        $filename = uniqid() . '.' . $file->guessExtension();
        $path = 'attachments/' . $filename;

        try {
            // Upload to R2
            $stream = fopen($file->getPathname(), 'r');
            if ($stream === false) {
                return $this->json(['error' => 'Failed to read uploaded file'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            try {
                $this->storage->writeStream($path, $stream);
            } finally {
                fclose($stream);
            }

            // Create attachment record
            $attachment = new Attachment();
            $attachment->setFilename($filename);
            $attachment->setOriginalName($file->getClientOriginalName());
            $attachment->setMimeType($file->getMimeType());
            $attachment->setPath($path);
            $attachment->setSize($file->getSize());
            $attachment->setCard($card);

            $this->entityManager->persist($attachment);
            $this->entityManager->flush();

            return $this->json($attachment, Response::HTTP_CREATED, [], [
                'groups' => ['attachment:read']
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to upload file'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/attachments/{id}', name: 'attachment_show', methods: ['GET'])]
    public function show(Attachment $attachment): JsonResponse
    {
        return $this->json($attachment, Response::HTTP_OK, [], [
            'groups' => ['attachment:read']
        ]);
    }

    #[Route('/attachments/{id}/url', name: 'attachment_url', methods: ['GET'])]
    public function url(Attachment $attachment): JsonResponse
    {
        $expiresAt = new \DateTimeImmutable('+15 minutes');

        try {
            $url = $this->storage->temporaryUrl($attachment->getPath(), $expiresAt);
        } catch (UnableToGenerateTemporaryUrl $exception) {
            return $this->json(['error' => 'Failed to generate attachment URL'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json([
            'url' => $url,
            'expiresAt' => $expiresAt->format(DATE_ATOM),
        ]);
    }

    #[Route('/attachments/{id}/download', name: 'attachment_download', methods: ['GET'])]
    public function download(Attachment $attachment): Response
    {
        try {
            $stream = $this->storage->readStream($attachment->getPath());

            return new Response($stream, Response::HTTP_OK, [
                'Content-Type' => $attachment->getMimeType(),
                'Content-Disposition' => 'attachment; filename="' . $attachment->getOriginalName() . '"',
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'File not found'], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('/attachments/{id}', name: 'attachment_delete', methods: ['DELETE'])]
    public function delete(Attachment $attachment): JsonResponse
    {
        try {
            // Delete from R2
            $this->storage->delete($attachment->getPath());

            // Delete from database
            $this->entityManager->remove($attachment);
            $this->entityManager->flush();

            return $this->json(['message' => 'Attachment deleted successfully'], Response::HTTP_OK);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Failed to delete attachment'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
