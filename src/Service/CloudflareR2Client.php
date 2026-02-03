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
        $endpoint = $_ENV['AWS_ENDPOINT']
            ?? $_ENV['CLOUDFLARE_R2_ENDPOINT']
            ?? 'https://your-account-id.r2.cloudflarestorage.com';
        $region = $_ENV['AWS_DEFAULT_REGION'] ?? $_ENV['CLOUDFLARE_R2_REGION'] ?? 'auto';
        $accessKey = $_ENV['AWS_ACCESS_KEY_ID'] ?? $_ENV['CLOUDFLARE_R2_ACCESS_KEY'] ?? '';
        $secretKey = $_ENV['AWS_SECRET_ACCESS_KEY'] ?? $_ENV['CLOUDFLARE_R2_SECRET_KEY'] ?? '';
        $bucket = $_ENV['AWS_BUCKET'] ?? $_ENV['CLOUDFLARE_R2_BUCKET'] ?? 'trello-attachments';
        $usePathStyle = $_ENV['AWS_USE_PATH_STYLE_ENDPOINT'] ?? null;
        $usePathStyleEndpoint = null;
        if ($usePathStyle !== null) {
            $usePathStyleEndpoint = filter_var($usePathStyle, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $client = new S3Client([
            'endpoint' => $endpoint,
            'region' => $region,
            'version' => 'latest',
            'credentials' => [
                'key' => $accessKey,
                'secret' => $secretKey,
            ],
            'use_path_style_endpoint' => $usePathStyleEndpoint,
        ]);

        $adapter = new AwsS3V3Adapter($client, $bucket);
        $this->filesystem = new Filesystem($adapter);
    }

    public function getFilesystem(): Filesystem
    {
        return $this->filesystem;
    }
}