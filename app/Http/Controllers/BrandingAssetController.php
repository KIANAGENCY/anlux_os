<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\BrandingService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class BrandingAssetController extends Controller
{
    public function logo(BrandingService $branding): BinaryFileResponse
    {
        return response()->file($branding->logoAbsolutePath(), [
            'Content-Type' => $branding->logoMime(),
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
