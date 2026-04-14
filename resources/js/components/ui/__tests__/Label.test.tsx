import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { Label } from '../Label';

describe('Label', () => {
    it('renders children as text', () => {
        render(<Label>My label</Label>);
        expect(screen.getByText('My label')).toBeInTheDocument();
    });

    it('renders as label element', () => {
        render(<Label>Field</Label>);
        expect(screen.getByText('Field').tagName).toBe('LABEL');
    });

    it('associates with input via htmlFor', () => {
        render(
            <>
                <Label htmlFor="my-input">Name</Label>
                <input id="my-input" />
            </>,
        );
        expect(screen.getByLabelText('Name')).toBeInTheDocument();
    });

    it('applies custom className', () => {
        render(<Label className="text-red-500">Error label</Label>);
        expect(screen.getByText('Error label')).toHaveClass('text-red-500');
    });
});
