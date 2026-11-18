<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicImageController extends Controller
{
    public function __invoke(string $path): BinaryFileResponse
    {
        abort_unless(preg_match('#\A(?:stores|products)/[A-Za-z0-9]{40}\.(?:jpe?g|png|webp)\z#D', $path) === 1, 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        return response()->file($disk->path($path));
    }
}
