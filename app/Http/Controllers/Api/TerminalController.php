<?php

namespace App\Http\Controllers\Api;

use App\Events\TerminalDisconnected;
use App\Events\TerminalOutput;
use App\Http\Controllers\Controller;
use App\Services\TerminalSessionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class TerminalController extends Controller
{
    /**
     * Connect to a terminal session
     */
    public function connect(Request $request): JsonResponse
    {
        \Log::info('[Terminal] Connect called');
        
        $user = Auth::user();
        if (!$user) {
            \Log::warning('[Terminal] Connect called without authenticated user');
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            // Create persistent shell session
            $sessionId = TerminalSessionManager::createSession($user->id);
            
            // Store session info in cache
            $sessionKey = "terminal_user_{$user->id}";
            $sessionData = [
                'session_id' => $sessionId,
                'connected_at' => now(),
                'last_activity' => now(),
            ];
            
            \Log::info('[Terminal] Created persistent session for user ' . $user->id);
            Cache::put($sessionKey, $sessionData, now()->addHours(1));

            // Send initial prompt
            broadcast(new TerminalOutput(
                $user->id,
                "Terminal session started\n",
                'output'
            ));

            return response()->json([
                'status' => 'connected',
            ]);
        } catch (\Exception $e) {
            \Log::error('[Terminal] Failed to create session: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to create terminal session',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Execute a command in the terminal
     */
    public function execute(Request $request): JsonResponse
    {
        /** @var array{command: string} $validated */
        $validated = $request->validate([
            'command' => 'required|string',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Verify user has active terminal session
        $sessionKey = "terminal_user_{$user->id}";
        $cacheSession = Cache::get($sessionKey);
        
        if (!$cacheSession || !isset($cacheSession['session_id'])) {
            return response()->json(['error' => 'No active terminal session'], 403);
        }

        // Update last activity
        $cacheSession['last_activity'] = now();
        Cache::put($sessionKey, $cacheSession, now()->addHours(1));

        // Execute command in persistent session
        try {
            $sessionId = $cacheSession['session_id'];
            $result = TerminalSessionManager::executeCommand($sessionId, $validated['command']);

            if ($result['error']) {
                \Log::info('Broadcasting error output to user ' . $user->id . ': ' . $result['error']);
                broadcast(new TerminalOutput(
                    $user->id, 
                    $result['error'], 
                    'error'
                ));
            }

            if ($result['output']) {
                \Log::info('Broadcasting output to user ' . $user->id . ': ' . $result['output']);
                broadcast(new TerminalOutput(
                    $user->id, 
                    $result['output'], 
                    'output'
                ));
            }

            return response()->json([
                'status' => 'executed',
                'cwd' => $result['cwd'],
            ]);
        } catch (\Exception $e) {
            \Log::error('[Terminal] Command execution failed: ' . $e->getMessage());
            
            broadcast(new TerminalOutput(
                $user->id, 
                "Error: " . $e->getMessage() . "\n", 
                'error'
            ));

            return response()->json([
                'error' => 'Command execution failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Disconnect from a terminal session
     */
    public function disconnect(Request $request): JsonResponse
    {
        \Log::info('[Terminal] Disconnect called');
        
        $user = Auth::user();
        if (!$user) {
            \Log::warning('[Terminal] Disconnect called without authenticated user');
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $sessionKey = "terminal_user_{$user->id}";
        $cacheSession = Cache::get($sessionKey);
        \Log::info('[Terminal] Disconnecting user ' . $user->id . ', existing session: ' . json_encode($cacheSession));
        
        // Destroy the persistent shell session
        if ($cacheSession && isset($cacheSession['session_id'])) {
            try {
                TerminalSessionManager::destroySession($cacheSession['session_id']);
                \Log::info('[Terminal] Destroyed persistent session for user ' . $user->id);
            } catch (\Exception $e) {
                \Log::error('[Terminal] Failed to destroy session: ' . $e->getMessage());
            }
        }
        
        Cache::forget($sessionKey);

        \Log::info('[Terminal] Broadcasting TerminalDisconnected event to user ' . $user->id);
        broadcast(new TerminalDisconnected($user->id));

        return response()->json([
            'status' => 'disconnected',
        ]);
    }
}