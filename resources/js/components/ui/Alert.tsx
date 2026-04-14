import { cn } from '@/lib/utils';
import { HTMLAttributes } from 'react';

interface AlertProps extends HTMLAttributes<HTMLDivElement> {
    variant?: 'default' | 'success' | 'warning' | 'destructive';
}

export function Alert({ className, variant = 'default', ...props }: AlertProps) {
    return (
        <div
            role="alert"
            className={cn(
                'relative w-full rounded-lg border px-4 py-3 text-sm',
                variant === 'default'     && 'border-gray-200 bg-gray-50 text-gray-800',
                variant === 'success'     && 'border-green-200 bg-green-50 text-green-800',
                variant === 'warning'     && 'border-yellow-200 bg-yellow-50 text-yellow-800',
                variant === 'destructive' && 'border-red-200 bg-red-50 text-red-800',
                className,
            )}
            {...props}
        />
    );
}
