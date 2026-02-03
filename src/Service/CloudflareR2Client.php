<?php

declare(strict_types=1);

namespace App\Service;

use Aws\S3\S3Client;

class CloudflareR2Client
{
    public function createClient(): S3Client
    {
        $endpoint = $_ENV['AWS_ENDPOINT']
            ?? $_ENV['CLOUDFLARE_R2_ENDPOINT']
            ?? 'https://your-account-id.r2.cloudflarestorage.com';
        $region = $_ENV['AWS_DEFAULT_REGION'] ?? $_ENV['CLOUDFLARE_R2_REGION'] ?? 'auto';
        $accessKey = $_ENV['AWS_ACCESS_KEY_ID'] ?? $_ENV['CLOUDFLARE_R2_ACCESS_KEY'] ?? '';
        $secretKey = $_ENV['AWS_SECRET_ACCESS_KEY'] ?? $_ENV['CLOUDFLARE_R2_SECRET_KEY'] ?? '';
        $usePathStyle = $_ENV['AWS_USE_PATH_STYLE_ENDPOINT'] ?? null;
        $usePathStyleEndpoint = null;
        if ($usePathStyle !== null) {
            $usePathStyleEndpoint = filter_var($usePathStyle, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        return new S3Client([
            'endpoint' => $endpoint,
            'region' => $region,
            'version' => 'latest',
            'credentials' => [
                'key' => $accessKey,
                'secret' => $secretKey,
            ],
            'use_path_style_endpoint' => $usePathStyleEndpoint,
        ]);
    }
}