import { useForm } from '@inertiajs/react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import Login from '../Login';

describe('Login page', () => {
    it('renders email and password fields', () => {
        render(<Login />);
        expect(screen.getByLabelText(/email/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/password/i)).toBeInTheDocument();
    });

    it('renders sign in button', () => {
        render(<Login />);
        expect(screen.getByRole('button', { name: /sign in/i })).toBeInTheDocument();
    });

    it('renders Hostinger heading', () => {
        render(<Login />);
        expect(screen.getByRole('heading', { name: /hostinger/i })).toBeInTheDocument();
    });

    it('calls setData on email input change', async () => {
        const setData = vi.fn();
        vi.mocked(useForm).mockReturnValue({
            data: { email: '', password: '' },
            setData,
            post: vi.fn(),
            errors: {},
            processing: false,
        } as ReturnType<typeof useForm>);

        render(<Login />);
        await userEvent.type(screen.getByLabelText(/email/i), 'a@b.com');
        expect(setData).toHaveBeenCalledWith('email', expect.any(String));
    });

    it('calls setData on password input change', async () => {
        const setData = vi.fn();
        vi.mocked(useForm).mockReturnValue({
            data: { email: '', password: '' },
            setData,
            post: vi.fn(),
            errors: {},
            processing: false,
        } as ReturnType<typeof useForm>);

        render(<Login />);
        await userEvent.type(document.querySelector('input[type="password"]')!, 's');
        expect(setData).toHaveBeenCalledWith('password', expect.any(String));
    });

    it('calls post on form submit', async () => {
        const post = vi.fn();
        vi.mocked(useForm).mockReturnValue({
            data: { email: 'a@b.com', password: 'secret' },
            setData: vi.fn(),
            post,
            errors: {},
            processing: false,
        } as ReturnType<typeof useForm>);

        render(<Login />);
        await userEvent.click(screen.getByRole('button', { name: /sign in/i }));
        expect(post).toHaveBeenCalledWith('/login');
    });

    it('shows "Signing in…" when processing', () => {
        vi.mocked(useForm).mockReturnValue({
            data: { email: '', password: '' },
            setData: vi.fn(),
            post: vi.fn(),
            errors: {},
            processing: true,
        } as ReturnType<typeof useForm>);

        render(<Login />);
        expect(screen.getByRole('button', { name: /signing in/i })).toBeDisabled();
    });

    it('shows email error', () => {
        vi.mocked(useForm).mockReturnValue({
            data: { email: '', password: '' },
            setData: vi.fn(),
            post: vi.fn(),
            errors: { email: 'Invalid email address' },
            processing: false,
        } as ReturnType<typeof useForm>);

        render(<Login />);
        expect(screen.getByText('Invalid email address')).toBeInTheDocument();
    });

    it('shows password error', () => {
        vi.mocked(useForm).mockReturnValue({
            data: { email: '', password: '' },
            setData: vi.fn(),
            post: vi.fn(),
            errors: { password: 'Wrong password' },
            processing: false,
        } as ReturnType<typeof useForm>);

        render(<Login />);
        expect(screen.getByText('Wrong password')).toBeInTheDocument();
    });

    it('shows generic error when no field errors', () => {
        vi.mocked(useForm).mockReturnValue({
            data: { email: '', password: '' },
            setData: vi.fn(),
            post: vi.fn(),
            errors: { credentials: 'These credentials do not match.' },
            processing: false,
        } as ReturnType<typeof useForm>);

        render(<Login />);
        expect(screen.getByText('These credentials do not match.')).toBeInTheDocument();
    });
});
