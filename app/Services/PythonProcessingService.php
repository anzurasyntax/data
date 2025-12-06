<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Exception;

class PythonProcessingService
{
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
        $process = new Process([
            'python',
            base_path("python/$script"),
            json_encode($payload)
        ]);

        $process->run();

        if (! $process->isSuccessful()) {
            throw new Exception($process->getErrorOutput() ?: 'Python process failed');
        }

        $decoded = json_decode(stripcslashes(trim($process->getOutput(), "\"")), true);

        if ($decoded === null) {
            throw new Exception('JSON decode failed: ' . json_last_error_msg());
        }

        return $decoded;
    }
}
