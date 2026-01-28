<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IdeSession;
use App\Models\SessionEvent;
use App\Services\FileService;
use Illuminate\Http\Request;
use App\Models\ProgrammingLanguage;
use App\Services\Workspace\WorkspaceGuard;
use Illuminate\Support\Facades\Log;

class FileController extends Controller
{
    private function session(Request $request): IdeSession
    {
        return IdeSession::findOrFail($request->cookie('ide_session'));
    }

    /* 📂 File Tree */
    public function tree(Request $request, FileService $service)
    {
        return response()->json([
            'tree' => $service->tree($this->session($request)),
        ]);
    }

    /* 📄 Read File */
    public function read(Request $request, FileService $service)
    {
        $request->validate(['path' => 'required|string']);

        return response()->json([
            'content' => $service->read($this->session($request), $request->path),
        ]);
    }

    /* ➕ Create */
    public function create(Request $request, FileService $service)
    {
        $request->validate([
            'path' => 'required|string',
            'type' => 'required|in:file,directory',
        ]);

        $service->create(
            $this->session($request),
            $request->path,
            $request->type === 'directory'
        );

        SessionEvent::create([
            'session_id' => $this->session($request)->id,
            'event_type' => 'file_created',
            'payload' => $request->only('path', 'type'),
        ]);

        return response()->json(['status' => 'created']);
    }

    /* ✏️ Update */
    public function update(Request $request, FileService $service)
    {
        $request->validate([
            'path' => 'required|string',
            'content' => 'required|string',
        ]);

        dd($this->session($request), $request->path, $request->content);
        Log::info($this->session($request));
        Log::info($request->path);
        Log::info($request->content);

        $service->update(
            $this->session($request),
            $request->path,
            $request->content
        );

        SessionEvent::create([
            'session_id' => $this->session($request)->id,
            'event_type' => 'file_updated',
            'payload' => ['path' => $request->path],
        ]);

        return response()->json(['status' => 'updated']);
    }

    /* 🗑 Delete */
    public function delete(Request $request, FileService $service)
    {
        $request->validate(['path' => 'required|string']);

        $session = $this->session($request);

        // 🔑 Language is required for workspace rules
        $request->validate([
            'language' => 'required|string',
        ]);

        $language = ProgrammingLanguage::where('language_name', $request->language)
            ->where('is_active', true)
            ->firstOrFail();

        app(WorkspaceGuard::class)
            ->forbidEntryFileDeletion($language, $request->path);

        $service->delete($session, $request->path);

        SessionEvent::create([
            'session_id' => $session->id,
            'event_type' => 'file_deleted',
            'payload' => ['path' => $request->path],
        ]);

        return response()->json(['status' => 'deleted']);
    }
}
