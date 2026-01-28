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
}
