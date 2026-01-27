<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUploadedFileRequest;
use App\Models\UploadedFile;
use App\Services\UploadedFileService;
use App\Services\PythonProcessingService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UploadedFileController extends Controller
{
    public function __construct(
        private UploadedFileService $service,
        private PythonProcessingService $pythonService
    ) {}

    public function index(): Factory|View
    {
        $files = UploadedFile::all();
        return view('files.create', compact('files'));
    }

    public function store(StoreUploadedFileRequest $request): RedirectResponse
    {
        $file = $this->service->store($request->file_type, $request->file('file'));

        // Auto-check quality after upload
        try {
            $qualityResult = $this->pythonService->process('quality_check.py', [
                'file_type' => $file->file_type,
                'file_path' => storage_path("app/public/{$file->file_path}")
            ]);

            // Redirect to quality report page
            return redirect()->route('files.quality', $file->id)
                ->with('quality_result', $qualityResult);
        } catch (\Exception $e) {
            // If quality check fails, still redirect to process page
            return redirect()->route('process.index')
                ->with('warning', 'File uploaded but quality check failed: ' . $e->getMessage());
        }
    }

    public function quality($id): Factory|View|RedirectResponse
    {
        try {
            $file = $this->service->find($id);
            
            // Always run quality check (session data might be expired or missing)
            // Only use session data if it exists and matches this file
            $qualityResult = session('quality_result');
            
            // If no session data or it's from a different file, run the check
            if (!$qualityResult) {
                $qualityResult = $this->pythonService->process('quality_check.py', [
                    'file_type' => $file->file_type,
                    'file_path' => storage_path("app/public/{$file->file_path}")
                ]);
            }

            // Validate quality result structure
            if (!isset($qualityResult['quality_score']) || !isset($qualityResult['total_rows'])) {
                throw new \Exception('Invalid quality check result');
            }

            return view('files.quality', compact('file', 'qualityResult'));
        } catch (\Exception $e) {
            // Instead of redirecting, show error on quality page
            $file = $this->service->find($id);
            $errorMessage = 'Failed to check file quality: ' . $e->getMessage();
            
            // Return view with error instead of redirecting
            return view('files.quality', [
                'file' => $file,
                'error' => $errorMessage,
                'qualityResult' => null
            ]);
        }
    }

    public function destroy($id): RedirectResponse
    {
        try {
            $file = $this->service->find($id);

            // Delete physical file
            $filePath = storage_path("app/public/{$file->file_path}");
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Delete database record
            $file->delete();

            return redirect()->route('files.index')
                ->with('success', 'File deleted successfully');
        } catch (\Exception $e) {
            return redirect()->route('files.index')
                ->with('error', 'Failed to delete file: ' . $e->getMessage());
        }
    }
}
