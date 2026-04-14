import { cn } from '@/lib/utils';
import { HTMLAttributes, ReactNode, useEffect, useRef } from 'react';

interface DialogProps {
    open: boolean;
    onClose: () => void;
    children: ReactNode;
    className?: string;
}

export function Dialog({ open, onClose, children, className }: DialogProps) {
    const ref = useRef<HTMLDialogElement>(null);

    useEffect(() => {
        if (open) {
            ref.current?.showModal();
        } else {
            ref.current?.close();
        }
    }, [open]);

    return (
        <dialog
            ref={ref}
            onClose={onClose}
            onClick={(e) => e.target === ref.current && onClose()}
            className={cn(
                'rounded-lg border border-gray-200 bg-white p-0 shadow-lg backdrop:bg-black/50 open:flex open:flex-col w-full max-w-md',
                className,
            )}
        >
            {children}
        </dialog>
    );
}

export function DialogHeader({ className, ...props }: HTMLAttributes<HTMLDivElement>) {
    return <div className={cn('flex flex-col gap-1.5 p-6 pb-4', className)} {...props} />;
}

export function DialogTitle({ className, ...props }: HTMLAttributes<HTMLHeadingElement>) {
    return <h2 className={cn('text-lg font-semibold text-gray-900', className)} {...props} />;
}

export function DialogContent({ className, ...props }: HTMLAttributes<HTMLDivElement>) {
    return <div className={cn('px-6 pb-6', className)} {...props} />;
}

export function DialogFooter({ className, ...props }: HTMLAttributes<HTMLDivElement>) {
    return <div className={cn('flex justify-end gap-2 px-6 pb-6', className)} {...props} />;
}
