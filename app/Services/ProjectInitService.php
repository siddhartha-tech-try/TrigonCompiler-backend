<?php

namespace App\Services;

use App\Models\IdeSession;
use RuntimeException;
use Illuminate\Support\Facades\File;

class ProjectInitService
{
    public function init(IdeSession $session, string $language, ?string $framework): void
    {
        if (!$this->isWorkspaceEmpty($session->workspace_path)) {
            throw new RuntimeException('Project already initialized');
        }

        $templatePath = $this->resolveTemplatePath($language, $framework);

        if (!File::exists($templatePath)) {
            throw new RuntimeException('Template not found');
        }

        File::copyDirectory($templatePath, $session->workspace_path);
    }

    private function isWorkspaceEmpty(string $path): bool
    {
        return count(File::files($path)) === 0
            && count(File::directories($path)) === 0;
    }

    private function resolveTemplatePath(string $language, ?string $framework): string
    {
        $base = base_path('templates');

        if ($framework) {
            return "{$base}/{$language}/{$framework}";
        }

        return "{$base}/{$language}/script";
    }
}
