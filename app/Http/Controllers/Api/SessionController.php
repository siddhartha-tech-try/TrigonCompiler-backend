<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IdeSession;
use App\Models\SessionEvent;
use App\Services\WorkspaceService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

class SessionController extends Controller
{
    public function bootstrap(Request $request, WorkspaceService $workspaceService)
    {
        $cookieSessionId = $request->cookie('ide_session');

        // 1️⃣ Reuse existing valid session
        if ($cookieSessionId) {
            $existingSession = IdeSession::find($cookieSessionId);

            if ($existingSession && $existingSession->status === 'active') {
                $existingSession->update([
                    'last_activity_at' => now(),
                ]);

                return response()->json([
                    'session_id' => $existingSession->id,
                    'status'     => 'existing',
                ]);
            }
        }

        // 2️⃣ Create new session
        $sessionId = (string) Str::uuid();
        $workspacePath = $workspaceService->create($sessionId);

        $session = IdeSession::create([
            'id'             => $sessionId,
            'workspace_path' => $workspacePath,
            'is_anonymous'   => true,
            'status'         => 'active',
            'started_at'     => now(),
            'last_activity_at' => now(),
        ]);

        SessionEvent::create([
            'session_id' => $session->id,
            'event_type' => 'session_created',
        ]);

        return response()
            ->json([
                'session_id' => $session->id,
                'status'     => 'created',
            ])
            ->withCookie(
                cookie(
                    'ide_session',
                    $session->id,
                    60 * 24,   // minutes
                    '/',
                    null,
                    false,     // secure
                    true,      // httpOnly
                    false,     // raw
                    'Lax'
                )
            );

    }

    public function cleanup(Request $request)
    {
        $sessionId = $request->cookie('ide_session');

        if (!$sessionId) {
            // Nothing to clean
            return response()->json(['status' => 'no_session']);
        }

        $session = IdeSession::find($sessionId);

        if (!$session) {
            return response()->json(['status' => 'not_found']);
        }

        // 🔁 Idempotency: already cleaned
        if ($session->status === 'ended') {
            return response()->json(['status' => 'already_ended']);
        }

        // 1️⃣ Mark session ended
        $session->update([
            'status' => 'ended',
            'ended_at' => now(),
        ]);

        // 2️⃣ Delete workspace (best effort)
        try {
            if ($session->workspace_path && is_dir($session->workspace_path)) {
                \Illuminate\Support\Facades\File::deleteDirectory(
                    $session->workspace_path
                );
            }
        } catch (\Throwable $e) {
            // Log but don't fail cleanup
            \Illuminate\Support\Facades\Log::warning(
                '[v0] Workspace cleanup failed',
                [
                    'session_id' => $sessionId,
                    'error' => $e->getMessage(),
                ]
            );
        }

        // 3️⃣ Emit event
        SessionEvent::create([
            'session_id' => $session->id,
            'event_type' => 'session_ended',
        ]);

        return response()->json(['status' => 'cleaned']);
    }

}
