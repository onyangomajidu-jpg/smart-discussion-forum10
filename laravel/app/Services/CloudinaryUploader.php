<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;

class CloudinaryUploader
{
    private Cloudinary $sdk;

    public function __construct()
    {
        $this->sdk = new Cloudinary(config('cloudinary.cloud_url'));
    }

    /**
     * Upload a file and return its secure URL.
     * Falls back to public disk on local environment.
     */
    public function upload(UploadedFile $file, string $folder): string
    {
        if (!app()->isProduction()) {
            $ext  = $file->getClientOriginalExtension() ?: 'bin';
            $path = $file->storeAs($folder, uniqid().'.'.$ext, 'public');
            return $path; // relative path — storage_url() handles it
        }

        $mime         = $file->getMimeType() ?? '';
        $resourceType = str_starts_with($mime, 'image/') ? 'image'
                      : (str_starts_with($mime, 'video/') || str_starts_with($mime, 'audio/') ? 'video'
                      : 'raw');

        $result = $this->sdk->uploadApi()->upload(
            $file->getRealPath(),
            [
                'folder'        => $folder,
                'public_id'     => uniqid(),
                'resource_type' => $resourceType,
            ]
        );

        return $result['secure_url'];
    }
}
