<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UploadDataFile extends Model
{
    protected $fillable = [
        'file_type',
        'original_name',
        'stored_name',
        'mime_type',
        'size',
        'path',
    ];
}
