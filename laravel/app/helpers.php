<?php

use Illuminate\Support\Facades\Storage;

if (!function_exists('storage_url')) {
    /**
     * Return the public URL for a stored file path.
     * Works for both the local 'public' disk and S3.
     */
    function storage_url(?string $path): string
    {
        if (!$path) return '';
        return Storage::url($path);
    }
}
