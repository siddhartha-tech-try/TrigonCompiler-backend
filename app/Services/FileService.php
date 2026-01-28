<?php

namespace App\Services;

use App\Models\IdeSession;
use Illuminate\Support\Facades\File;
use RuntimeException;

class FileService
{
    public function getWorkspacePath(IdeSession $session): string
    {
        return realpath($session->workspace_path);
    }

    private function resolvePath(IdeSession $session, string $relativePath): string
    {
        if (str_contains($relativePath, '..') || str_starts_with($relativePath, '/')) {
            throw new RuntimeException('Invalid path');
        }

        $fullPath = $this->getWorkspacePath($session) . DIRECTORY_SEPARATOR . $relativePath;
        $realPath = realpath(dirname($fullPath)) ?: dirname($fullPath);

        if (!str_starts_with($realPath, $this->getWorkspacePath($session))) {
            throw new RuntimeException('Path traversal detected');
        }

        return $fullPath;
    }

    /* ======================
       FILE TREE
    ====================== */

    public function tree(IdeSession $session): array
    {
        return $this->scanDirectory($this->getWorkspacePath($session));
    }

    private function scanDirectory(string $path): array
    {
        $items = [];

        foreach (File::directories($path) as $dir) {
            $items[] = [
                'type' => 'directory',
                'name' => basename($dir),
                'children' => $this->scanDirectory($dir),
            ];
        }

        foreach (File::files($path) as $file) {
            $items[] = [
                'type' => 'file',
                'name' => $file->getFilename(),
            ];
        }

        return $items;
    }

    /* ======================
       FILE READ
    ====================== */

    public function read(IdeSession $session, string $path): string
    {
        $fullPath = $this->resolvePath($session, $path);

        if (!File::exists($fullPath) || !File::isFile($fullPath)) {
            throw new RuntimeException('File not found');
        }

        return File::get($fullPath);
    }

    /* ======================
       FILE CREATE
    ====================== */

    public function create(IdeSession $session, string $path, bool $isDirectory): void
    {
        $fullPath = $this->resolvePath($session, $path);

        if (File::exists($fullPath)) {
            throw new RuntimeException('File or directory already exists');
        }

        if ($isDirectory) {
            File::makeDirectory($fullPath, 0775, true);
        } else {
            File::put($fullPath, '');
        }
    }

    /* ======================
       FILE UPDATE
    ====================== */

    public function update(IdeSession $session, string $path, string $content): void
    {
        $fullPath = $this->resolvePath($session, $path);

        if (!File::exists($fullPath) || !File::isFile($fullPath)) {
            throw new RuntimeException('File not found');
        }

        File::put($fullPath, $content);
    }

    /* ======================
       FILE DELETE
    ====================== */

    public function delete(IdeSession $session, string $path): void
    {
        $fullPath = $this->resolvePath($session, $path);

        if (!File::exists($fullPath)) {
            throw new RuntimeException('File or directory not found');
        }

        File::isDirectory($fullPath)
            ? File::deleteDirectory($fullPath)
            : File::delete($fullPath);
    }
}
