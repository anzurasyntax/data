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

        // Auto-check quality after upload
        $qualityResult = null;
        try {
            $qualityResult = $this->pythonService->process('quality_check.py', [
                'file_type' => $uploadedFile->file_type,
                'file_path' => storage_path("app/public/{$uploadedFile->file_path}")
            ]);
        } catch (\Exception $e) {
            // Quality check failed, but upload succeeded
        }

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
            'quality_check' => $qualityResult,
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

    public function qualityCheck(int $id): JsonResponse
    {
        try {
            $file = $this->fileService->find($id);

            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

            $result = $this->pythonService->process('quality_check.py', [
                'file_type' => $file->file_type,
                'file_path' => storage_path("app/public/{$file->file_path}")
            ]);

            return response()->json([
                'success' => true,
                'quality_score' => $result['quality_score'],
                'is_clean' => $result['is_clean'],
                'total_rows' => $result['total_rows'],
                'total_columns' => $result['total_columns'],
                'total_missing' => $result['total_missing'],
                'total_duplicate_rows' => $result['total_duplicate_rows'],
                'total_outliers' => $result['total_outliers'],
                'issues' => $result['issues'],
                'issues_by_type' => $result['issues_by_type'],
                'column_quality' => $result['column_quality']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check file quality: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cleanData(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'operations' => 'required|array',
            'operations.*.type' => 'required|string',
            'operations.*.method' => 'nullable|string',
            'operations.*.column' => 'nullable|string',
            'operations.*.columns' => 'nullable|array',
            'operations.*.value' => 'nullable',
            'operations.*.lower_percentile' => 'nullable|numeric',
            'operations.*.upper_percentile' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $file = $this->fileService->find($id);

            if (!$file) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

            $result = $this->pythonService->process('clean_data.py', [
                'file_type' => $file->file_type,
                'file_path' => storage_path("app/public/{$file->file_path}"),
                'operations' => $request->input('operations')
            ]);

            return response()->json([
                'success' => true,
                'message' => $result['message'] ?? 'Data cleaned successfully',
                'original_rows' => $result['original_rows'],
                'cleaned_rows' => $result['cleaned_rows'],
                'rows_removed' => $result['rows_removed'],
                'applied_operations' => $result['applied_operations']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
