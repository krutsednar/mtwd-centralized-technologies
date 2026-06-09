<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * GET /api/v1/profiles/{profile}/photo — the matched employee's avatar for the
 * kiosk success modal (BUILD_PROMPT §3.5). Streams the stored picture as JPEG
 * with an ETag the kiosk caches against.
 */
class ProfilePhotoController extends Controller
{
    public function __invoke(Profile $profile): Response
    {
        if (! $profile->picture || ! Storage::disk('public')->exists($profile->picture)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        $path = Storage::disk('public')->path($profile->picture);

        $response = new BinaryFileResponse($path, Response::HTTP_OK, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=86400',
        ]);

        // Strong validator so the kiosk can revalidate with If-None-Match (304).
        $response->setEtag(md5_file($path) ?: null);

        return $response;
    }
}
