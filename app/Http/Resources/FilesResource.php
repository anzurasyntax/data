<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class FilesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'original_name' => $this->original_name,
            'file_type'     => $this->file_type,
            'full_path'     => url(Storage::url($this->file_path)),
            'mime_type'     => $this->mime_type,
            'file_size'     => $this->file_size,
        ];
    }
}
