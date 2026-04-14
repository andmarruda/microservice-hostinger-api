import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { createRef } from 'react';
import { describe, expect, it, vi } from 'vitest';
import { Button } from '../Button';

describe('Button', () => {
    it('renders children', () => {
        render(<Button>Click me</Button>);
        expect(screen.getByRole('button', { name: 'Click me' })).toBeInTheDocument();
    });

    it('is a button element', () => {
        render(<Button>OK</Button>);
        expect(screen.getByRole('button')).toBeInTheDocument();
    });

    it('calls onClick handler', async () => {
        const handler = vi.fn();
        render(<Button onClick={handler}>Click</Button>);
        await userEvent.click(screen.getByRole('button'));
        expect(handler).toHaveBeenCalledOnce();
    });

    it('is disabled when disabled prop is set', () => {
        render(<Button disabled>Disabled</Button>);
        expect(screen.getByRole('button')).toBeDisabled();
    });

    it('does not call onClick when disabled', async () => {
        const handler = vi.fn();
        render(<Button disabled onClick={handler}>Click</Button>);
        await userEvent.click(screen.getByRole('button'), { pointerEventsCheck: 0 });
        expect(handler).not.toHaveBeenCalled();
    });

    it('applies custom className', () => {
        render(<Button className="custom-class">Btn</Button>);
        expect(screen.getByRole('button')).toHaveClass('custom-class');
    });

    it.each(['default', 'destructive', 'outline', 'ghost', 'link', 'success'] as const)(
        'renders variant=%s without crashing',
        (variant) => {
            render(<Button variant={variant}>Label</Button>);
            expect(screen.getByRole('button')).toBeInTheDocument();
        },
    );

    it.each(['default', 'sm', 'lg', 'icon'] as const)(
        'renders size=%s without crashing',
        (size) => {
            render(<Button size={size}>Label</Button>);
            expect(screen.getByRole('button')).toBeInTheDocument();
        },
    );

    it('forwards ref', () => {
        const ref = createRef<HTMLButtonElement>();
        render(<Button ref={ref}>Ref</Button>);
        expect(ref.current).toBeInstanceOf(HTMLButtonElement);
    });

    it('passes through type attribute', () => {
        render(<Button type="submit">Submit</Button>);
        expect(screen.getByRole('button')).toHaveAttribute('type', 'submit');
    });
});
