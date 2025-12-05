<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;

class UploadDataFileController extends Controller
{
    public function index(): Factory|View
    {
        return view('uploadDataFile');
    }

    public function create(Request $request): Factory|View|JsonResponse
    {
        $request->validate([
            'file_type' => 'required|string',
            'file' => 'required|file'
        ]);

        // Store the uploaded file
        $savedPath = $request->file('file')->store('uploads');
        $absolutePath = str_replace('/', DIRECTORY_SEPARATOR, storage_path('app/private/' . $savedPath));

        // Python script path
        $pythonScriptPath = base_path('python' . DIRECTORY_SEPARATOR . 'getData.py');

        // Payload for Python
        $payload = json_encode([
            'file_type' => $request->file_type,
            'file_path' => $absolutePath
        ]);

        Log::info('Python script path: ' . $pythonScriptPath);
        Log::info('File path: ' . $absolutePath);

        $process = new Process([
            'python',
            $pythonScriptPath,
            $payload
        ]);

        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json([
                'status' => 'python_error',
                'error_message' => $process->getErrorOutput(),
                'file_path' => $absolutePath,
                'python_output' => $process->getOutput(),
            ]);
        }

        $pythonResponse = json_decode($process->getOutput(), true);
        return view('dataPreview', [
            'file_path' => $absolutePath,
            'file_type' => $request->file_type,
            'columns' => $pythonResponse['columns_info'] ?? [],
            'data' => $pythonResponse['data'] ?? [],
        ]);
    }

}
