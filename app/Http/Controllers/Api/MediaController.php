<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class MediaController extends Controller
{
    public function show(string $path): BinaryFileResponse
    {
        $path = ltrim($path, '/');

        if ($path === '' || str_contains($path, '..') || ! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $root = realpath(storage_path('app/public'));
        $file = realpath(Storage::disk('public')->path($path));

        if (! $root || ! $file || ! str_starts_with($file, $root.DIRECTORY_SEPARATOR)) {
            abort(404);
        }

        return response()->file($file);
    }
}
