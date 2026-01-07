<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\AttachmentRepository;
use App\Service\CloudflareR2Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cleanup:attachments',
    description: 'Clean up orphaned file attachments from database and storage',
)]
class AttachmentCleanupCommand extends Command
{
    public function __construct(
        private AttachmentRepository $attachmentRepository,
        private CloudflareR2Client $r2Client,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be deleted without actually deleting')
            ->addOption('older-than', null, InputOption::VALUE_OPTIONAL, 'Delete attachments older than X days', '30')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');
        $daysOld = (int) $input->getOption('older-than');

        $io->title('Attachment Cleanup');
        $io->info("Finding orphaned attachments older than {$daysOld} days...");

        $cutoffDate = new \DateTime("-{$daysOld} days");

        // Find attachments not linked to any cards (orphaned)
        $orphanedAttachments = $this->attachmentRepository->createQueryBuilder('a')
            ->leftJoin('a.card', 'c')
            ->where('c.id IS NULL')
            ->andWhere('a.createdAt < :cutoffDate')
            ->setParameter('cutoffDate', $cutoffDate)
            ->getQuery()
            ->getResult();

        $count = count($orphanedAttachments);

        if ($count === 0) {
            $io->success('No orphaned attachments found. Database is clean!');
            return Command::SUCCESS;
        }

        $io->warning("Found {$count} orphaned attachments to clean up.");

        if ($dryRun) {
            $io->info('DRY RUN - Would delete the following attachments:');
            foreach ($orphanedAttachments as $attachment) {
                $io->text("- {$attachment->getFilename()} ({$attachment->getOriginalName()})");
            }
            $io->success("Dry run complete. Use without --dry-run to actually delete {$count} files.");
            return Command::SUCCESS;
        }

        $deletedCount = 0;
        $errorCount = 0;
        $totalSize = 0;

        $progressBar = $io->createProgressBar($count);
        $progressBar->start();

        foreach ($orphanedAttachments as $attachment) {
            try {
                // Delete from R2 storage
                $filesystem = $this->r2Client->getFilesystem();
                if ($filesystem->fileExists($attachment->getPath())) {
                    $filesystem->delete($attachment->getPath());
                }

                // Delete from database
                $this->entityManager->remove($attachment);
                $totalSize += $attachment->getSize();
                $deletedCount++;

            } catch (\Exception $e) {
                $errorCount++;
                $io->error("Failed to delete attachment {$attachment->getId()}: {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $this->entityManager->flush();
        $progressBar->finish();
        $io->newLine(2);

        $formattedSize = $this->formatBytes($totalSize);
        $io->success("Cleanup complete: {$deletedCount} attachments deleted, {$errorCount} errors, {$formattedSize} freed.");

        return Command::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
