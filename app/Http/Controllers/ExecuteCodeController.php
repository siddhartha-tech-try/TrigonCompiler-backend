<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\CodeExecutor;
use Illuminate\Support\Facades\Log;
use App\Models\ProgrammingLanguage;
use Illuminate\Support\Facades\Http;

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

    public function interactive(Request $request)
    {
        $sessionId = $request->cookie('ide_session');
        abort_if(!$sessionId, 403);

        $request->validate([
            'language' => 'required|string',
        ]);

        $lang = ProgrammingLanguage::where('language_name', $request->language)
            ->where('is_active', true)
            ->firstOrFail();

        $workspace = storage_path("workspaces/{$sessionId}");

        $response = Http::withHeaders([
            'X-Internal-Token' => config('services.interactive_gateway.token'),
        ])->post(config('services.interactive_gateway.url') . '/interactive/sessions', [
            'session_id' => $sessionId,
            'workspace_path' => $workspace,
            'language' => $lang->language_name,
            'container_image' => $lang->container_image,
            'run_command' => $lang->run_command,
        ]);

        if (!$response->successful()) {
            Log::error($response->body());
            return response()->json(['error' => 'Failed to start interactive session'], 500);
        }

        return response()->json($response->json());
    }

}
