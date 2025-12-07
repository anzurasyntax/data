<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class FileDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'file' => [
                'id'   => $this->resource['file']->id,
                'name' => $this->resource['file']->original_name,
                'type' => $this->resource['file']->file_type,
                'path' => $this->resource['file']->file_path,

                'full_url' => url(Storage::url($this->resource['file']->file_path)),
            ],

            'result' => $this->resource['result'],
        ];
    }
}
