import React, { useState, useRef } from 'react';
import { Terminal as TerminalIcon, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Drawer,
    DrawerClose,
    DrawerContent,
    DrawerHeader,
    DrawerTitle,
} from '@/components/ui/drawer';
import { Terminal } from '@/components/terminal';

export function TerminalDrawer() {
    const [isOpen, setIsOpen] = useState(false);
    const buttonRef = useRef<HTMLButtonElement>(null);

    const handleOpenChange = (open: boolean) => {
        setIsOpen(open);
        // If closing, restore focus to the button after a short delay
        if (!open) {
            setTimeout(() => {
                buttonRef.current?.focus();
            }, 100);
        }
    };

    return (
        <>
            {/* Floating Terminal Button */}
            <Button
                ref={buttonRef}
                onClick={() => setIsOpen(true)}
                size="icon"
                className="fixed bottom-6 right-6 z-40 h-12 w-12 rounded-full shadow-lg"
                title="Open Terminal"
            >
                <TerminalIcon className="h-5 w-5" />
            </Button>

            {/* Terminal Drawer */}
            <Drawer 
                open={isOpen} 
                onOpenChange={handleOpenChange}
                direction="bottom"
                modal={true}
            >
                <DrawerContent className="h-[60vh] max-h-[600px]" aria-describedby="terminal-description">
                    <DrawerHeader className="border-b border-zinc-800">
                        <DrawerTitle className="flex items-center gap-2">
                            <TerminalIcon className="h-4 w-4" />
                            Terminal
                        </DrawerTitle>
                        <DrawerClose asChild>
                            <Button variant="ghost" size="icon" className="h-8 w-8 absolute right-4 top-4">
                                <X className="h-4 w-4" />
                            </Button>
                        </DrawerClose>
                    </DrawerHeader>
                    <div id="terminal-description" className="sr-only">
                        Interactive terminal for executing commands
                    </div>
                    <div className="h-full overflow-hidden">
                        <Terminal className="h-full" />
                    </div>
                </DrawerContent>
            </Drawer>
        </>
    );
}