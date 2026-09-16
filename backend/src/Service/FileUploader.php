<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    public function __construct(
        private string $uploadDirectory
    ) {
    }

    public function upload(UploadedFile $file): string
    {
        $originalName = pathinfo(
            $file->getClientOriginalName(),
            PATHINFO_FILENAME
        );

        $safeName = preg_replace(
            '/[^a-zA-Z0-9_-]/',
            '_',
            $originalName
        );

        $newFilename = $safeName . '-' . uniqid() . '.pdf';

        $file->move($this->uploadDirectory, $newFilename);

        return $newFilename;
    }
}