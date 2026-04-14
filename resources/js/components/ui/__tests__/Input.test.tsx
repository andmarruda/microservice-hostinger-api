import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { createRef } from 'react';
import { describe, expect, it, vi } from 'vitest';
import { Input } from '../Input';

describe('Input', () => {
    it('renders an input element', () => {
        render(<Input />);
        expect(screen.getByRole('textbox')).toBeInTheDocument();
    });

    it('renders with placeholder', () => {
        render(<Input placeholder="Enter value" />);
        expect(screen.getByPlaceholderText('Enter value')).toBeInTheDocument();
    });

    it('renders with type=email', () => {
        render(<Input type="email" />);
        expect(document.querySelector('input[type="email"]')).toBeInTheDocument();
    });

    it('renders with type=password', () => {
        render(<Input type="password" />);
        expect(document.querySelector('input[type="password"]')).toBeInTheDocument();
    });

    it('is disabled when disabled prop is set', () => {
        render(<Input disabled />);
        expect(screen.getByRole('textbox')).toBeDisabled();
    });

    it('calls onChange', async () => {
        const handler = vi.fn();
        render(<Input onChange={handler} />);
        await userEvent.type(screen.getByRole('textbox'), 'hello');
        expect(handler).toHaveBeenCalled();
    });

    it('applies custom className', () => {
        render(<Input className="custom" />);
        expect(screen.getByRole('textbox')).toHaveClass('custom');
    });

    it('forwards ref', () => {
        const ref = createRef<HTMLInputElement>();
        render(<Input ref={ref} />);
        expect(ref.current).toBeInstanceOf(HTMLInputElement);
    });

    it('renders with value', () => {
        render(<Input value="prefilled" onChange={() => {}} />);
        expect(screen.getByDisplayValue('prefilled')).toBeInTheDocument();
    });
});
