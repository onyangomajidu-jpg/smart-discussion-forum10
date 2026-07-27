<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class CloudinaryUploader
{
    private ?object $sdk = null;

    private function sdk(): object
    {
        if (!$this->sdk) {
            $this->sdk = new \Cloudinary\Cloudinary(config('cloudinary.cloud_url'));
        }
        return $this->sdk;
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

        $result = $this->sdk()->uploadApi()->upload(
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
