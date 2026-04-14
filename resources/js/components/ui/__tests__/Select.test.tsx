import { render, screen } from '@testing-library/react';
import { createRef } from 'react';
import { describe, expect, it } from 'vitest';
import { Select } from '../Select';

describe('Select', () => {
    it('renders a select element', () => {
        render(<Select />);
        expect(screen.getByRole('combobox')).toBeInTheDocument();
    });

    it('renders options', () => {
        render(
            <Select>
                <option value="a">Option A</option>
                <option value="b">Option B</option>
            </Select>,
        );
        expect(screen.getByRole('option', { name: 'Option A' })).toBeInTheDocument();
        expect(screen.getByRole('option', { name: 'Option B' })).toBeInTheDocument();
    });

    it('is disabled when disabled prop is set', () => {
        render(<Select disabled />);
        expect(screen.getByRole('combobox')).toBeDisabled();
    });

    it('applies custom className', () => {
        render(<Select className="custom-select" />);
        expect(screen.getByRole('combobox')).toHaveClass('custom-select');
    });

    it('forwards ref', () => {
        const ref = createRef<HTMLSelectElement>();
        render(<Select ref={ref} />);
        expect(ref.current).toBeInstanceOf(HTMLSelectElement);
    });

    it('renders with value', () => {
        render(
            <Select value="b" onChange={() => {}}>
                <option value="a">A</option>
                <option value="b">B</option>
            </Select>,
        );
        expect(screen.getByDisplayValue('B')).toBeInTheDocument();
    });
});
