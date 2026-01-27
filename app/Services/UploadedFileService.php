<?php

namespace App\Services;

use App\Models\UploadedFile;

class UploadedFileService
{
    public function store($fileType, $file, ?int $userId = null): UploadedFile
    {
        $path = $file->store('uploads', 'public');

        return UploadedFile::create(array_filter([
            'user_id'       => $userId,
            'original_name' => $file->getClientOriginalName(),
            'file_type'     => $fileType,
            'file_path'     => $path,
            'mime_type'     => $file->getClientMimeType(),
            'file_size'     => $file->getSize(),
        ], fn ($v) => $v !== null));
    }

    public function find(int|string $id): UploadedFile
    {
        return UploadedFile::findOrFail($id);
    }

    public function findForUser(int|string $id, int $userId): UploadedFile
    {
        return UploadedFile::where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();
    }
}
