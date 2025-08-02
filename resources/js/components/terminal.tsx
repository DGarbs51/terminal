import React, { useEffect, useRef, useState, useCallback } from 'react';
import { useEcho } from '@laravel/echo-react';
import { usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { SharedData } from '@/types';

interface TerminalProps {
    className?: string;
}

interface TerminalLine {
    id: string;
    type: 'input' | 'output' | 'error';
    content: string;
    timestamp: Date;
}

export function Terminal({ className }: TerminalProps) {
    const { auth } = usePage<SharedData>().props;
    const [lines, setLines] = useState<TerminalLine[]>([]);
    const [currentInput, setCurrentInput] = useState('');
    const [isConnected, setIsConnected] = useState(false);
    const [currentDirectory, setCurrentDirectory] = useState('~');
    const terminalRef = useRef<HTMLDivElement>(null);
    const inputRef = useRef<HTMLInputElement>(null);
    const shouldDisconnectRef = useRef(false);

    const addLine = useCallback((content: string, type: TerminalLine['type']) => {
        const newLine: TerminalLine = {
            id: `${Date.now()}-${Math.random()}`,
            type,
            content,
            timestamp: new Date(),
        };
        setLines(prev => [...prev, newLine]);
        
        // Auto-scroll to bottom
        setTimeout(() => {
            if (terminalRef.current) {
                terminalRef.current.scrollTop = terminalRef.current.scrollHeight;
            }
        }, 10);
    }, []);

    // Subscribe to terminal events on user channel
    useEcho(
        `App.Models.User.${auth.user.id}`, 
        '.TerminalOutput', 
        (e: { output: string; type: 'output' | 'error' }) => {
            console.log('[Terminal] Received TerminalOutput event:', e);
            addLine(e.output, e.type);
        },
        [auth.user.id, addLine]
    );

    useEcho(
        `App.Models.User.${auth.user.id}`,
        '.TerminalDisconnected',
        () => {
            console.log('[Terminal] Received TerminalDisconnected event');
            setIsConnected(false);
            addLine('Terminal disconnected', 'error');
        },
        [auth.user.id, addLine]
    );

    // Debug all events
    useEcho(
        `App.Models.User.${auth.user.id}`,
        '*',
        (e: unknown) => {
            console.log('[Terminal] Received event on user channel:', e);
        },
        [auth.user.id]
    );

    // Initialize terminal
    useEffect(() => {
        console.log('[Terminal] Component mounting, initializing terminal...');
        let mounted = true;

        const initializeTerminal = async () => {
            try {
                console.log('[Terminal] Calling /api/terminal/connect...');
                addLine('Initializing terminal...', 'output');
                
                const response = await window.axios.post('/api/terminal/connect');
                console.log('[Terminal] Connect response:', response.data);
                
                if (mounted && response.data.status === 'connected') {
                    console.log('[Terminal] Setting isConnected to true');
                    shouldDisconnectRef.current = true;
                    setIsConnected(true);
                    addLine('Terminal connected', 'output');
                } else if (!mounted) {
                    console.log('[Terminal] Component unmounted before connection completed');
                }
            } catch (error) {
                console.error('[Terminal] Failed to connect terminal:', error);
                addLine('Failed to initialize terminal', 'error');
            }
        };

        // Add a small delay to avoid StrictMode double-mount issues
        const timer = setTimeout(() => {
            if (mounted) {
                initializeTerminal();
            }
        }, 100);

        return () => {
            console.log('[Terminal] Component unmounting, shouldDisconnect:', shouldDisconnectRef.current);
            mounted = false;
            clearTimeout(timer);
            
            // Only disconnect if we actually connected
            if (shouldDisconnectRef.current) {
                shouldDisconnectRef.current = false;
                setIsConnected(false);
                window.axios.post('/api/terminal/disconnect')
                    .then(() => console.log('[Terminal] Disconnect successful'))
                    .catch((error) => console.error('[Terminal] Disconnect error:', error));
            }
        };
    }, [addLine]);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!currentInput.trim() || !isConnected) {
            console.log('[Terminal] Cannot execute - input empty or not connected', { 
                input: currentInput, 
                isConnected 
            });
            return;
        }

        // Add input to display
        addLine(`$ ${currentInput}`, 'input');

        // Send command to server
        try {
            console.log('[Terminal] Executing command:', currentInput);
            const response = await window.axios.post('/api/terminal/execute', { 
                command: currentInput 
            });
            console.log('[Terminal] Execute response:', response.data);
            
            // Update current directory if returned
            if (response.data.cwd) {
                const home = '/Users/dgarbs51'; // TODO: Get from server
                const cwd = response.data.cwd;
                if (cwd.startsWith(home)) {
                    setCurrentDirectory('~' + cwd.slice(home.length));
                } else {
                    setCurrentDirectory(cwd);
                }
            }
        } catch (error) {
            console.error('[Terminal] Failed to execute command:', error);
            addLine('Failed to execute command', 'error');
        }

        setCurrentInput('');
    };

    const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
        // Add command history navigation with arrow keys in the future
        if (e.key === 'ArrowUp') {
            e.preventDefault();
            // TODO: Navigate command history up
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            // TODO: Navigate command history down
        }
    };

    return (
        <div className={cn("flex h-full flex-col bg-zinc-950 font-mono text-sm", className)}>
            <div className="flex items-center justify-between border-b border-zinc-800 px-4 py-2">
                <div className="flex items-center gap-2">
                    <div className={cn(
                        "h-2 w-2 rounded-full",
                        isConnected ? "bg-green-500" : "bg-red-500"
                    )} />
                    <span className="text-xs text-zinc-400">
                        {isConnected ? "Connected" : "Connecting..."}
                    </span>
                </div>
                <span className="text-xs text-zinc-400">Terminal</span>
            </div>

            <div 
                ref={terminalRef}
                className="flex-1 overflow-y-auto p-4 text-zinc-100"
                onClick={() => inputRef.current?.focus()}
            >
                {lines.map((line) => (
                    <div 
                        key={line.id} 
                        className={cn(
                            "whitespace-pre-wrap break-all",
                            line.type === 'input' && "text-blue-400",
                            line.type === 'error' && "text-red-400"
                        )}
                    >
                        {line.content}
                    </div>
                ))}
                
                <form onSubmit={handleSubmit} className="flex items-center gap-2 mt-2">
                    <span className="text-blue-400">{currentDirectory}</span>
                    <span className="text-green-400">$</span>
                    <input
                        ref={inputRef}
                        type="text"
                        value={currentInput}
                        onChange={(e) => setCurrentInput(e.target.value)}
                        onKeyDown={handleKeyDown}
                        disabled={!isConnected}
                        className="flex-1 bg-transparent outline-none text-zinc-100 placeholder-zinc-600"
                        placeholder={isConnected ? "Type a command..." : "Waiting for connection..."}
                        autoFocus
                    />
                </form>
            </div>
        </div>
    );
}