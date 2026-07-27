<?php

use Illuminate\Support\Facades\Storage;

if (!function_exists('storage_url')) {
    function storage_url(?string $path): string
    {
        if (!$path) return '';
        // Cloudinary URLs are already full URLs
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        return Storage::disk('public')->url($path);
    }
}
