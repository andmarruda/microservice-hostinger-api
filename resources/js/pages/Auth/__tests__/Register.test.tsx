import { useForm } from '@inertiajs/react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import Register from '../Register';

const token = 'invite-token-123';

describe('Register page', () => {
    it('renders name, password, and confirm password fields', () => {
        render(<Register token={token} />);
        expect(screen.getByLabelText(/name/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/^password$/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/confirm password/i)).toBeInTheDocument();
    });

    it('renders Create account button', () => {
        render(<Register token={token} />);
        expect(screen.getByRole('button', { name: /create account/i })).toBeInTheDocument();
    });

    it('calls setData when typing in password field', async () => {
        const setData = vi.fn();
        vi.mocked(useForm).mockReturnValue({
            data: { token, name: '', password: '', password_confirmation: '' },
            setData,
            post: vi.fn(),
            errors: {},
            processing: false,
        } as ReturnType<typeof useForm>);

        render(<Register token={token} />);
        const passwordInputs = document.querySelectorAll('input[type="password"]');
        await userEvent.type(passwordInputs[0]!, 's');
        expect(setData).toHaveBeenCalledWith('password', expect.any(String));
    });

    it('calls setData when typing in password_confirmation field', async () => {
        const setData = vi.fn();
        vi.mocked(useForm).mockReturnValue({
            data: { token, name: '', password: '', password_confirmation: '' },
            setData,
            post: vi.fn(),
            errors: {},
            processing: false,
        } as ReturnType<typeof useForm>);

        render(<Register token={token} />);
        const passwordInputs = document.querySelectorAll('input[type="password"]');
        await userEvent.type(passwordInputs[1]!, 's');
        expect(setData).toHaveBeenCalledWith('password_confirmation', expect.any(String));
    });

    it('calls setData when typing in name field', async () => {
        const setData = vi.fn();
        vi.mocked(useForm).mockReturnValue({
            data: { token, name: '', password: '', password_confirmation: '' },
            setData,
            post: vi.fn(),
            errors: {},
            processing: false,
        } as ReturnType<typeof useForm>);

        render(<Register token={token} />);
        await userEvent.type(screen.getByLabelText(/^name$/i), 'A');
        expect(setData).toHaveBeenCalledWith('name', expect.any(String));
    });

    it('calls post with /register on submit', async () => {
        const post = vi.fn();
        vi.mocked(useForm).mockReturnValue({
            data: { token, name: 'Alice', password: 'secret', password_confirmation: 'secret' },
            setData: vi.fn(),
            post,
            errors: {},
            processing: false,
        } as ReturnType<typeof useForm>);

        render(<Register token={token} />);
        await userEvent.click(screen.getByRole('button', { name: /create account/i }));
        expect(post).toHaveBeenCalledWith('/register');
    });

    it('shows "Creating account…" while processing', () => {
        vi.mocked(useForm).mockReturnValue({
            data: { token, name: '', password: '', password_confirmation: '' },
            setData: vi.fn(),
            post: vi.fn(),
            errors: {},
            processing: true,
        } as ReturnType<typeof useForm>);

        render(<Register token={token} />);
        expect(screen.getByRole('button', { name: /creating account/i })).toBeDisabled();
    });

    it('shows name validation error', () => {
        vi.mocked(useForm).mockReturnValue({
            data: { token, name: '', password: '', password_confirmation: '' },
            setData: vi.fn(),
            post: vi.fn(),
            errors: { name: 'Name is required' },
            processing: false,
        } as ReturnType<typeof useForm>);

        render(<Register token={token} />);
        expect(screen.getByText('Name is required')).toBeInTheDocument();
    });

    it('shows password validation error', () => {
        vi.mocked(useForm).mockReturnValue({
            data: { token, name: '', password: '', password_confirmation: '' },
            setData: vi.fn(),
            post: vi.fn(),
            errors: { password: 'Password too short' },
            processing: false,
        } as ReturnType<typeof useForm>);

        render(<Register token={token} />);
        expect(screen.getByText('Password too short')).toBeInTheDocument();
    });
});
