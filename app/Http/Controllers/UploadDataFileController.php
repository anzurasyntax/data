<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadDataFileRequest;
use App\Models\UploadDataFile;
use App\Models\UploadedFile;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;

class UploadDataFileController extends Controller
{
    public function index(): Factory|View
    {
        return view('uploadDataFile');
    }

    public function create(Request $request)
    {
        $request->validate([
            'file_type' => 'required|string',
            'file' => 'required|file'
        ]);

        $path = $request->file('file')->store('uploads');

        $saved = \App\Models\UploadedFile::create([
            'original_name' => $request->file('file')->getClientOriginalName(),
            'file_type' => $request->file_type,
            'file_path' => $path,
            'mime_type' => $request->file('file')->getClientMimeType(),
            'file_size' => $request->file('file')->getSize(),
        ]);

        $files = UploadedFile::all();

        return redirect()->route('files');

    }

    public function getAllFiles(){
        $files = UploadedFile::all();
        return view('uploadedFiles', compact('files'));
    }
    public function processFile($id)
    {
        $file = UploadedFile::findOrFail($id);

        // Build payload for Python
        $payload = json_encode([
            'file_type' => $file->file_type,
            'file_path' => storage_path('app/private/' . $file->file_path)
        ]);

        $process = new Process([
            'python',
            base_path('python/process_file.py'),
            $payload
        ]);

        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json([
                'error' => $process->getErrorOutput()
            ]);
        }

        // Get raw Python output
        $output = $process->getOutput();

        // Remove starting & ending quotes added by Windows shell
        if (str_starts_with(trim($output), '"') && str_ends_with(trim($output), '"')) {
            $output = trim($output, "\"");
        }

        // Fix escaped characters
        $output = stripcslashes($output);

        // Decode JSON
        $result = json_decode($output, true);

        // Debug if decoding fails
        if ($result === null) {
            return response()->json([
                'error' => 'JSON decode failed',
                'output' => $output,
                'message' => json_last_error_msg()
            ]);
        }

        return view('previewDataFile', [
            'file' => $file,
            'result' => $result
        ]);
    }






}
