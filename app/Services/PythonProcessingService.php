<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Exception;
use Illuminate\Support\Facades\Log;

class PythonProcessingService
{
    /**
     * Get the Python executable path from environment or default.
     *
     * @return string
     */
    protected function getPythonPath(): string
    {
        return env('PYTHON_PATH', 'python');
    }

    /**
     * Validate file path to prevent directory traversal attacks.
     *
     * @param string $path
     * @return string
     * @throws Exception
     */
    protected function validatePath(string $path): string
    {
        // Ensure the file path is within the storage directory
        $realStoragePath = realpath(storage_path('app/public'));
        $realFilePath = realpath($path);
        
        if (!$realFilePath) {
            throw new Exception('Invalid file path: file does not exist');
        }
        
        if (strpos($realFilePath, $realStoragePath) !== 0) {
            throw new Exception('Invalid file path: path must be within storage directory');
        }
        
        return $realFilePath;
    }

    /**
     * Run a Python script with a payload and return decoded JSON result.
     *
     * @param string $script
     * @param array $payload
     * @return array
     * @throws Exception
     */
    public function process(string $script, array $payload): array
    {
        // Sanitize script name
        $script = basename($script);
        if (!preg_match('/^[a-zA-Z0-9_\-\.]+\.py$/', $script)) {
            throw new Exception('Invalid script name');
        }

        $scriptPath = base_path("python/$script");
        
        // Validate script exists
        if (!file_exists($scriptPath)) {
            throw new Exception("Python script not found: $script");
        }

        // Validate file paths in payload
        if (isset($payload['file_path'])) {
            $payload['file_path'] = $this->validatePath($payload['file_path']);
        }

        $pythonPath = $this->getPythonPath();
        $process = new Process([
            $pythonPath,
            $scriptPath,
            json_encode($payload)
        ]);

        try {
            $process->setTimeout(300); // 5 minute timeout
            $process->run();

            if (! $process->isSuccessful()) {
                $errorOutput = $process->getErrorOutput();
                Log::error('Python process failed', [
                    'script' => $script,
                    'error' => $errorOutput,
                    'exit_code' => $process->getExitCode()
                ]);
                throw new Exception('Python process failed: ' . ($errorOutput ?: 'Unknown error'));
            }

            $output = trim($process->getOutput());
            // Remove any surrounding quotes if present
            $output = trim($output, '"');
            $output = stripcslashes($output);

            $decoded = json_decode($output, true);

            if ($decoded === null) {
                Log::error('JSON decode failed', [
                    'script' => $script,
                    'output' => $output,
                    'json_error' => json_last_error_msg()
                ]);
                throw new Exception('JSON decode failed: ' . json_last_error_msg());
            }

            // Check if Python script returned an error
            if (isset($decoded['success']) && $decoded['success'] === false) {
                $errorMessage = $decoded['error'] ?? 'Unknown error from Python script';
                throw new Exception($errorMessage);
            }

            return $decoded;
        } catch (Exception $e) {
            Log::error('Python processing exception', [
                'script' => $script,
                'exception' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
