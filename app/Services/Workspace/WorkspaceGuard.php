<?php

namespace App\Services\Workspace;

use App\Models\ProgrammingLanguage;
use Illuminate\Support\Facades\File;
use RuntimeException;

class WorkspaceGuard
{
    public function ensureEntryFileExists(
        ProgrammingLanguage $language,
        string $workspacePath
    ): void {
        $entryFilePath = $workspacePath . DIRECTORY_SEPARATOR . $language->file_name;

        if (!File::exists($entryFilePath)) {
            File::put($entryFilePath, $language->code_preview ?? '');
        }
    }

    public function forbidEntryFileDeletion(
        ProgrammingLanguage $language,
        string $relativePath
    ): void {
        if (basename($relativePath) === $language->file_name) {
            throw new RuntimeException('Entry file cannot be deleted');
        }
    }
}
