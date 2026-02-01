<?php

namespace App\Services;

use App\Models\IdeSession;
use Illuminate\Support\Facades\File;
use RuntimeException;
use App\Services\Workspace\WorkspaceGuard;
use Illuminate\Support\Facades\Log;
use App\Models\ProgrammingLanguage;


class CodeExecutor
{
    public function execute(string $language, string $workspace, string $stdin)
    {
        $lang = ProgrammingLanguage::where('language_name', $language)
            ->where('is_active', true)
            ->firstOrFail();

        if (!$lang->container_image || !$lang->run_command) {
            throw new RuntimeException("Execution not supported for {$language}");
        }

        // $guard = app(WorkspaceGuard::class);
        // $guard->ensureEntryFileExists($lang, $workspace);

        $dockerCmd = [
            'docker run --rm',
            '--network none',
            '--memory=256m',
            '--cpus=0.5',
            "-v {$workspace}:/app",
            '-w /app',
            $lang->container_image,
            "sh -c " . escapeshellarg($lang->run_command)
        ];

        return $this->runProcess(implode(' ', $dockerCmd), $stdin);
    }

    public function executeStream(string $language, string $workspace, string $stdin): void
    {
        $lang = ProgrammingLanguage::where('language_name', $language)
            ->where('is_active', true)
            ->firstOrFail();
        // app(WorkspaceGuard::class)
        //     ->ensureEntryFileExists($lang, $workspace);

        $cmd = implode(' ', [
            'docker run --rm',
            '--network none',
            '--memory=256m',
            '--cpus=0.5',
            "-v {$workspace}:/app",
            '-w /app',
            $lang->container_image,
            "sh -c " . escapeshellarg($lang->run_command),
        ]);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes);

        fwrite($pipes[0], $stdin);
        fclose($pipes[0]);

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $start = microtime(true);
        $timeout = 60;

        while (true) {
            $read = [$pipes[1], $pipes[2]];
            $write = $except = [];

            stream_select($read, $write, $except, 0, 200000);

            foreach ($read as $stream) {
                $chunk = fread($stream, 8192);
                if ($chunk !== false && $chunk !== '') {
                    $type = ($stream === $pipes[1]) ? 'stdout' : 'stderr';
                    Log::info("[EXEC {$type}] " . $chunk);
                    echo "event: {$type}\n";
                    echo 'data: ' . json_encode($chunk) . "\n\n";
                    ob_flush();
                    flush();
                }
            }

            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }

            if ((microtime(true) - $start) > $timeout) {
                proc_terminate($process, 9);
                echo "event: error\ndata: \"Execution timed out\"\n\n";
                break;
            }
        }

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        echo "event: done\ndata: {$exitCode}\n\n";
        ob_flush();
        flush();
    }

    private function runProcess($cmd, $stdin)
    {
        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptorspec, $pipes);

        if (!is_resource($process)) {
            throw new RuntimeException("Failed to start process");
        }

        fwrite($pipes[0], $stdin);
        fclose($pipes[0]);

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout = '';
        $stderr = '';
        $start = microtime(true);
        $timeout = 60; // seconds

        while (true) {
            $stdout .= stream_get_contents($pipes[1]);
            $stderr .= stream_get_contents($pipes[2]);

            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }

            if ((microtime(true) - $start) > $timeout) {
                proc_terminate($process, 9);
                return [
                    'stdout' => '',
                    'stderr' => 'Execution timed out',
                    'exitCode' => null
                ];
            }

            usleep(100000); // 100ms
        }

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return compact('stdout', 'stderr', 'exitCode');
    }


}
