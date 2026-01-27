<?php

use App\Models\UploadedFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uploaded_files', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('user_id')->index();
        });

        // Backfill existing rows (best-effort). We keep it nullable to avoid breaking legacy data.
        UploadedFile::whereNull('slug')->chunkById(200, function ($files) {
            foreach ($files as $file) {
                $base = pathinfo((string) $file->original_name, PATHINFO_FILENAME);
                $baseSlug = Str::slug($base) ?: 'file';
                $slug = $baseSlug;
                $i = 2;
                while (UploadedFile::where('slug', $slug)->where('id', '!=', $file->id)->exists()) {
                    $slug = $baseSlug . '-' . $i;
                    $i++;
                }
                $file->slug = $slug;
                $file->save();
            }
        });

        Schema::table('uploaded_files', function (Blueprint $table) {
            $table->unique(['user_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('uploaded_files', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'slug']);
            $table->dropIndex(['slug']);
            $table->dropColumn('slug');
        });
    }
};

