<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfilePhotoController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): StreamedResponse
    {
        $profilePhotoPath = $request->user()->profile_photo_path;

        abort_if($profilePhotoPath === null || ! Storage::exists($profilePhotoPath), 404);

        return Storage::response($profilePhotoPath);
    }
}
