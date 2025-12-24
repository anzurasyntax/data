<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FileDetailResource;
use App\Http\Resources\FilesResource;
use App\Models\UploadedFile;
use App\Services\PythonProcessingService;
use Illuminate\Http\Request;
use App\Services\UploadedFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class UploadedFileController extends Controller
{
    protected UploadedFileService $fileService;
    protected PythonProcessingService $pythonService;

    public function __construct(UploadedFileService $fileService, PythonProcessingService $pythonService)
    {
        $this->fileService   = $fileService;
        $this->pythonService = $pythonService;
    }


    public function index(): AnonymousResourceCollection
    {
        $files = UploadedFile::all();
        return FilesResource::collection($files);
    }

    public function upload(Request $request): JsonResponse
    {        // Validate request
        $validator = Validator::make($request->all(), [
            'file_type' => 'required|string|in:txt,csv,xml,xlsx',
            'file' => 'required|file|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $uploadedFile = $this->fileService->store(
            $request->input('file_type'),
            $request->file('file')
        );

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $uploadedFile->id,
                'original_name' => $uploadedFile->original_name,
                'file_type' => $uploadedFile->file_type,
                'file_path' => $uploadedFile->file_path,
                'mime_type' => $uploadedFile->mime_type,
                'file_size' => $uploadedFile->file_size,
            ],
            'message' => 'File uploaded successfully',
        ]);
    }


    public function show(int $id): FileDetailResource|JsonResponse
    {
        try {
            $file = $this->fileService->find($id);

            if (!$file) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'File not found'
                ], 404);
            }

            $result = $this->pythonService->process('process_file.py', [
                'file_type' => $file->file_type,
                'file_path' => storage_path("app/public/{$file->file_path}")
            ]);

            return (new FileDetailResource([
                'file'   => $file,
                'result' => $result
            ]))->additional([
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process file: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $file = $this->fileService->find($id);

            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

            // Delete physical file
            $filePath = storage_path("app/public/{$file->file_path}");
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Delete database record
            $file->delete();

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete file: ' . $e->getMessage()
            ], 500);
        }
    }
}
