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
use Illuminate\Support\Facades\Auth;

class UploadedFileController extends Controller
{
    public function __construct(
        private UploadedFileService $service,
        private PythonProcessingService $pythonService
    ) {}

    public function index(): Factory|View
    {
        $files = UploadedFile::where('user_id', Auth::id())
            ->latest()
            ->get();
        return view('files.create', compact('files'));
    }

    public function store(StoreUploadedFileRequest $request): RedirectResponse
    {
        $file = $this->service->store($request->file_type, $request->file('file'), (int) Auth::id());
        try {
            $qualityResult = $this->pythonService->process('quality_check.py', [
                'file_type' => $file->file_type,
                'file_path' => storage_path("app/public/{$file->file_path}")
            ]);
            return redirect()->route('files.quality', $file)
                ->with('quality_result', $qualityResult);
        } catch (\Exception $e) {
            return redirect()->route('files.list')
                ->with('warning', 'File uploaded but quality check failed: ' . $e->getMessage());
        }
    }

    public function quality(string $slug): Factory|View|RedirectResponse
    {
        try {
            $file = $this->service->findForUserBySlug($slug, (int) Auth::id());

            $qualityResult = session('quality_result');
            if (!$qualityResult) {
                $qualityResult = $this->pythonService->process('quality_check.py', [
                    'file_type' => $file->file_type,
                    'file_path' => storage_path("app/public/{$file->file_path}")
                ]);
            }
            if (!isset($qualityResult['quality_score']) || !isset($qualityResult['total_rows'])) {
                throw new \Exception('Invalid quality check result');
            }

            return view('files.quality', compact('file', 'qualityResult'));
        } catch (\Exception $e) {
            $file = $this->service->findForUserBySlug($slug, (int) Auth::id());
            $errorMessage = 'Failed to check file quality: ' . $e->getMessage();


            return view('files.quality', [
                'file' => $file,
                'error' => $errorMessage,
                'qualityResult' => null
            ]);
        }
    }

    public function destroy(string $slug): RedirectResponse
    {
        try {
            $file = $this->service->findForUserBySlug($slug, (int) Auth::id());
            $filePath = storage_path("app/public/{$file->file_path}");
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $file->delete();

            return redirect()->route('files.upload')
                ->with('success', 'File deleted successfully');
        } catch (\Exception $e) {
            return redirect()->route('files.upload')
                ->with('error', 'Failed to delete file: ' . $e->getMessage());
        }
    }
}
