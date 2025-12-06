<?php

namespace App\Http\Controllers;

use App\Models\UploadedFile;
use App\Services\PythonProcessingService;
use App\Services\UploadedFileService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Symfony\Component\Process\Process;

class FileProcessingController extends Controller
{
    public function __construct(
        private UploadedFileService $fileService,
        private PythonProcessingService $pythonService
    ) {}


    public function index(): Factory|View
    {
        $files = UploadedFile::all();
        return view('files.index', compact('files'));
    }

    public function show($id): Factory|View
    {
        $file = $this->fileService->find($id);

        $result = $this->pythonService->process('process_file.py', [
            'file_type' => $file->file_type,
            'file_path' => storage_path("app/private/{$file->file_path}")
        ]);

        return view('files.preview', compact('file', 'result'));
    }


}
