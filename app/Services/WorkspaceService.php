<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;

class WorkspaceService
{
    public function create(string $sessionId): string
    {
        $basePath = storage_path('workspaces');
        $workspacePath = $basePath . DIRECTORY_SEPARATOR . $sessionId;

        if (!File::exists($basePath)) {
            File::makeDirectory($basePath, 0775, true);
        }

        if (!File::exists($workspacePath)) {
            File::makeDirectory($workspacePath, 0775, true);
        }

        if (!File::isDirectory($workspacePath)) {
            throw new RuntimeException('Workspace path is invalid');
        }

        return $workspacePath;
    }
}
