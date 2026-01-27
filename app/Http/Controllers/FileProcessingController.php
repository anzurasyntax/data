<?php

namespace App\Http\Controllers;

use App\Models\UploadedFile;
use App\Services\PythonProcessingService;
use App\Services\UploadedFileService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FileProcessingController extends Controller
{
    public function __construct(
        private UploadedFileService $fileService,
        private PythonProcessingService $pythonService
    ) {}

    public function index(): Factory|View
    {
        $files = UploadedFile::where('user_id', Auth::id())
            ->latest()
            ->get();
        return view('files.index', compact('files'));
    }

    public function show($id): View|Factory|RedirectResponse
    {
        try {
            $file = $this->fileService->findForUser($id, (int) Auth::id());

            $result = $this->pythonService->process('process_file.py', [
                'file_type' => $file->file_type,
                'file_path' => storage_path("app/public/{$file->file_path}")
            ]);

            return view('files.preview', compact('file', 'result'));
        } catch (\Exception $e) {
            return redirect()->route('process.index')
                ->with('error', 'Failed to process file: ' . $e->getMessage());
        }
    }

    public function updateCell(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'row_index' => 'required|integer|min:0',
            'column' => 'required|string',
            'value' => 'nullable|string'
        ]);

        try {
            $file = $this->fileService->findForUser($id, (int) Auth::id());

            $result = $this->pythonService->process('update_cell.py', [
                'file_type' => $file->file_type,
                'file_path' => storage_path("app/public/{$file->file_path}"),
                'row_index' => $validated['row_index'],
                'column' => $validated['column'],
                'value' => $validated['value']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cell updated successfully',
                'column_stats' => $result['column_stats'],
                'total_duplicate_rows' => $result['total_duplicate_rows'],
                'outlier_map' => $result['outlier_map'],
                'updated_value' => $result['updated_value']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function cleanData(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'operations' => 'required|array',
            'operations.*.type' => 'required|string',
            'operations.*.method' => 'nullable|string',
            'operations.*.column' => 'nullable|string',
            'operations.*.columns' => 'nullable|array',
            'operations.*.value' => 'nullable',
            'operations.*.lower_percentile' => 'nullable|numeric',
            'operations.*.upper_percentile' => 'nullable|numeric',
        ]);

        try {
            $file = $this->fileService->findForUser($id, (int) Auth::id());

            $result = $this->pythonService->process('clean_data.py', [
                'file_type' => $file->file_type,
                'file_path' => storage_path("app/public/{$file->file_path}"),
                'operations' => $validated['operations']
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

    public function qualityCheck($id): JsonResponse
    {
        try {
            $file = $this->fileService->findForUser($id, (int) Auth::id());

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
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
