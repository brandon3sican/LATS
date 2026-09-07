<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SignatureController extends Controller
{
    /**
     * Stream stored signatures through Laravel instead of relying on the
     * web server's /storage symlink configuration.
     */
    public function show(Request $request, string $path)
    {
        if (!str_starts_with($path, 'signatures/') || str_contains($path, '..')) {
            abort(404);
        }

        $disk = Storage::disk('public');

        if (!$disk->exists($path)) {
            abort(404, 'Signature not found.');
        }

        $absolutePath = $disk->path($path);
        $mime = $disk->mimeType($path) ?: 'image/png';

        if (!str_starts_with($mime, 'image/')) {
            abort(404);
        }

        return response()->file($absolutePath, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
