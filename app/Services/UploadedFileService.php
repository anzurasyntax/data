<?php

namespace App\Services;

use App\Models\UploadedFile;

class UploadedFileService
{
    public function store($fileType, $file): UploadedFile
    {
        $path = $file->store('uploads', 'public');

        return UploadedFile::create([
            'original_name' => $file->getClientOriginalName(),
            'file_type'     => $fileType,
            'file_path'     => $path,
            'mime_type'     => $file->getClientMimeType(),
            'file_size'     => $file->getSize(),
        ]);
    }

    public function find(int|string $id): UploadedFile
    {
        return UploadedFile::findOrFail($id);
    }
}
