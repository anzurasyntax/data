<?php

namespace App\Http\Controllers;

use App\Models\UploadedFile;
use App\Services\PythonProcessingService;
use App\Services\UploadedFileService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        try {
            $file = $this->fileService->find($id);

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
            $file = $this->fileService->find($id);

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
}
