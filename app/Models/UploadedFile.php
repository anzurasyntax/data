<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UploadedFile extends Model
{
    protected $fillable = [
        'original_name',
        'file_type',
        'file_path',
        'mime_type',
        'file_size'
    ];
}
