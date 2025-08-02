<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Cache;

class TerminalSessionManager
{
    public static function createSession(int $userId): string
    {
        $sessionId = "user_{$userId}";
        
        // Store session state in cache instead of memory
        $sessionData = [
            'cwd' => getcwd(),
            'env' => [
                'HOME' => $_SERVER['HOME'] ?? '/tmp',
                'USER' => $_SERVER['USER'] ?? 'www-data',
                'PATH' => $_SERVER['PATH'] ?? '/usr/local/bin:/usr/bin:/bin',
            ],
            'created_at' => now(),
            'last_activity' => now(),
        ];
        
        Cache::put("terminal_session_{$sessionId}", $sessionData, now()->addHours(1));
        
        return $sessionId;
    }

    public static function executeCommand(string $sessionId, string $command): array
    {
        $sessionKey = "terminal_session_{$sessionId}";
        $sessionData = Cache::get($sessionKey);
        
        if (!$sessionData) {
            throw new \RuntimeException("Session not found");
        }

        $sessionData['last_activity'] = now();
        
        // Handle cd commands to update working directory
        if (preg_match('/^\s*cd\s*$/', $command)) {
            // cd with no args goes to home
            $sessionData['cwd'] = $sessionData['env']['HOME'];
            Cache::put($sessionKey, $sessionData, now()->addHours(1));
            return [
                'output' => '',
                'error' => '',
                'cwd' => $sessionData['cwd'],
            ];
        } elseif (preg_match('/^\s*cd\s+(.+)/', $command, $matches)) {
            $newDir = trim($matches[1]);
            
            // Handle special paths
            if ($newDir === '~') {
                $newDir = $sessionData['env']['HOME'];
            } elseif (strpos($newDir, '~/') === 0) {
                $newDir = $sessionData['env']['HOME'] . substr($newDir, 1);
            } elseif (strpos($newDir, '/') !== 0) {
                // Relative path
                $newDir = $sessionData['cwd'] . '/' . $newDir;
            }
            
            // Normalize the path
            $newDir = realpath($newDir);
            
            if ($newDir && is_dir($newDir)) {
                $sessionData['cwd'] = $newDir;
                Cache::put($sessionKey, $sessionData, now()->addHours(1));
                return [
                    'output' => '',
                    'error' => '',
                    'cwd' => $sessionData['cwd'],
                ];
            } else {
                return [
                    'output' => '',
                    'error' => "cd: {$matches[1]}: No such file or directory\n",
                    'cwd' => $sessionData['cwd'],
                ];
            }
        }
        
        // Execute command in the session's working directory
        $process = Process::fromShellCommandline($command, $sessionData['cwd'], $sessionData['env']);
        $process->setTimeout(30);
        
        try {
            $process->run();
            
            Cache::put($sessionKey, $sessionData, now()->addHours(1));
            
            return [
                'output' => $process->getOutput(),
                'error' => $process->getErrorOutput(),
                'cwd' => $sessionData['cwd'],
            ];
        } catch (\Exception $e) {
            return [
                'output' => '',
                'error' => $e->getMessage() . "\n",
                'cwd' => $sessionData['cwd'],
            ];
        }
    }

    public static function destroySession(string $sessionId): void
    {
        $sessionKey = "terminal_session_{$sessionId}";
        Cache::forget($sessionKey);
    }

    public static function getSession(string $sessionId): ?array
    {
        $sessionKey = "terminal_session_{$sessionId}";
        return Cache::get($sessionKey);
    }
}