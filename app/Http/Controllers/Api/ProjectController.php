<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IdeSession;
use App\Models\SessionEvent;
use App\Services\ProjectInitService;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function init(Request $request, ProjectInitService $service)
    {
        $request->validate([
            'language'  => 'required|string',
            'framework' => 'nullable|string',
        ]);

        $sessionId = $request->cookie('ide_session');
        // $sessionId = "338ee3d8-8594-4497-b17b-b189835ccc0a";
        // dd($sessionId);
        $session = IdeSession::findOrFail($sessionId);
        $service->init(
            $session,
            $request->language,
            $request->framework
        );
        SessionEvent::create([
            'session_id' => $session->id,
            'event_type' => 'project_initialized',
            'payload' => [
                'language' => $request->language,
                'framework' => $request->framework,
            ],
        ]);

        return response()->json([
            'status' => 'initialized'
        ]);
    }
}
