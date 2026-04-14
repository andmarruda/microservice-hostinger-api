import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { Alert } from '../Alert';

describe('Alert', () => {
    it('renders children', () => {
        render(<Alert>Something happened</Alert>);
        expect(screen.getByText('Something happened')).toBeInTheDocument();
    });

    it('has role="alert"', () => {
        render(<Alert>Message</Alert>);
        expect(screen.getByRole('alert')).toBeInTheDocument();
    });

    it('applies custom className', () => {
        render(<Alert className="extra-class">msg</Alert>);
        expect(screen.getByRole('alert')).toHaveClass('extra-class');
    });

    it('default variant has gray styling', () => {
        render(<Alert variant="default">Default</Alert>);
        expect(screen.getByRole('alert')).toHaveClass('bg-gray-50');
    });

    it('success variant has green styling', () => {
        render(<Alert variant="success">Success</Alert>);
        expect(screen.getByRole('alert')).toHaveClass('bg-green-50');
    });

    it('warning variant has yellow styling', () => {
        render(<Alert variant="warning">Warning</Alert>);
        expect(screen.getByRole('alert')).toHaveClass('bg-yellow-50');
    });

    it('destructive variant has red styling', () => {
        render(<Alert variant="destructive">Error</Alert>);
        expect(screen.getByRole('alert')).toHaveClass('bg-red-50');
    });

    it('renders with no variant (uses default)', () => {
        render(<Alert>No variant</Alert>);
        expect(screen.getByRole('alert')).toBeInTheDocument();
    });
});
