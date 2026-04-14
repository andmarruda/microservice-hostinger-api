import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { Badge } from '../Badge';

describe('Badge', () => {
    it('renders children', () => {
        render(<Badge>active</Badge>);
        expect(screen.getByText('active')).toBeInTheDocument();
    });

    it('renders as a span', () => {
        render(<Badge>label</Badge>);
        expect(screen.getByText('label').tagName).toBe('SPAN');
    });

    it('applies custom className', () => {
        render(<Badge className="my-class">x</Badge>);
        expect(screen.getByText('x')).toHaveClass('my-class');
    });

    it.each(['default', 'success', 'warning', 'destructive', 'info', 'outline'] as const)(
        'renders variant=%s without crashing',
        (variant) => {
            render(<Badge variant={variant}>{variant}</Badge>);
            expect(screen.getByText(variant)).toBeInTheDocument();
        },
    );

    it('default variant has gray styling', () => {
        render(<Badge variant="default">default</Badge>);
        expect(screen.getByText('default')).toHaveClass('bg-gray-100');
    });

    it('success variant has green styling', () => {
        render(<Badge variant="success">ok</Badge>);
        expect(screen.getByText('ok')).toHaveClass('bg-green-100');
    });

    it('destructive variant has red styling', () => {
        render(<Badge variant="destructive">err</Badge>);
        expect(screen.getByText('err')).toHaveClass('bg-red-100');
    });

    it('warning variant has yellow styling', () => {
        render(<Badge variant="warning">warn</Badge>);
        expect(screen.getByText('warn')).toHaveClass('bg-yellow-100');
    });

    it('info variant has blue styling', () => {
        render(<Badge variant="info">info</Badge>);
        expect(screen.getByText('info')).toHaveClass('bg-blue-100');
    });
});
