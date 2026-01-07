<?php

declare(strict_types=1);

namespace App\Service;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;

class CloudflareR2Client
{
    private Filesystem $filesystem;

    public function __construct()
    {
        $client = new S3Client([
            'endpoint' => $_ENV['CLOUDFLARE_R2_ENDPOINT'] ?? 'https://your-account-id.r2.cloudflarestorage.com',
            'region' => $_ENV['CLOUDFLARE_R2_REGION'] ?? 'auto',
            'version' => 'latest',
            'credentials' => [
                'key' => $_ENV['CLOUDFLARE_R2_ACCESS_KEY'] ?? '',
                'secret' => $_ENV['CLOUDFLARE_R2_SECRET_KEY'] ?? '',
            ],
        ]);

        $adapter = new AwsS3V3Adapter($client, $_ENV['CLOUDFLARE_R2_BUCKET'] ?? 'trello-attachments');
        $this->filesystem = new Filesystem($adapter);
    }

    public function getFilesystem(): Filesystem
    {
        return $this->filesystem;
    }
}