<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\CodeExecutor;

class ExecuteCodeController extends Controller
{
    public function __invoke(Request $request)
    {
        $sessionId = $request->cookie('ide_session');
        abort_if(!$sessionId, 403, 'IDE session missing');

        $request->validate([
            'language' => 'required|string',
            'stdin' => 'nullable|string',
        ]);

        $workspace = storage_path("workspaces/{$sessionId}");

        $result = app(CodeExecutor::class)
            ->execute(
                $request->language,
                $workspace,
                $request->input('stdin', '')
            );

        return response()->json($result);
    }

    public function stream(Request $request)
    {
        $sessionId = $request->cookie('ide_session');
        abort_if(!$sessionId, 403, 'IDE session missing');

        $request->validate([
            'language' => 'required|string',
            'stdin' => 'nullable|string',
        ]);

        $workspace = storage_path("workspaces/{$sessionId}");

        return new StreamedResponse(function () use ($request, $workspace) {
            app(CodeExecutor::class)->executeStream(
                $request->language,
                $workspace,
                // $request->input('stdin', '')
                (string) ($request->input('stdin') ?? '')
            );
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
