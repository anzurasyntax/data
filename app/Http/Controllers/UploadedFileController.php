<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUploadedFileRequest;
use App\Models\UploadedFile;
use App\Services\UploadedFileService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UploadedFileController extends Controller
{
    public function __construct(
        private UploadedFileService $service
    ) {}

    public function index(): Factory|View
    {
        $files = UploadedFile::all();
        return view('files.create', compact('files'));
    }

    public function store(StoreUploadedFileRequest $request): RedirectResponse
    {
        $this->service->store($request->file_type, $request->file('file'));

        return redirect()->route('process.index');
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
