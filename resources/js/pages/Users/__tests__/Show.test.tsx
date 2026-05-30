import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';
import UsersShow from '../Show';

const adminUser = {
    id: 1,
    name: 'Admin User',
    email: 'admin@example.com',
    email_verified_at: '2024-01-01T00:00:00.000000Z',
    created_at: '2024-01-01T00:00:00.000000Z',
    role: 'admin',
};

const regularUser = {
    id: 2,
    name: 'John Engineer',
    email: 'john@example.com',
    email_verified_at: '2024-01-01T00:00:00.000000Z',
    created_at: '2024-01-01T00:00:00.000000Z',
    role: 'user',
};

const vpsGrant = {
    id: 'vps-1',
    hostname: 'web-server-01',
    status: 'running',
    ip_address: '10.0.0.1',
    grant_id: 1,
    granted_at: '2024-01-01T00:00:00.000000Z',
    expires_at: null,
    can_manage_firewall: true,
    can_manage_ssh_keys: false,
    can_manage_snapshots: false,
};

const availableVps = {
    id: 'vps-2',
    hostname: 'db-server-01',
    status: 'running',
    ip_address: '10.0.0.2',
};

describe('Users/Show page', () => {
    it('renders the user name and email', () => {
        render(<UsersShow user={regularUser} grantedVps={[]} availableVps={[]} />);
        expect(screen.getByText('John Engineer')).toBeInTheDocument();
        expect(screen.getByText('john@example.com')).toBeInTheDocument();
    });

    it('renders user role badge', () => {
        render(<UsersShow user={regularUser} grantedVps={[]} availableVps={[]} />);
        expect(screen.getByText('User')).toBeInTheDocument();
    });

    it('renders admin badge for admin users', () => {
        render(<UsersShow user={adminUser} grantedVps={[]} availableVps={[]} />);
        expect(screen.getByText('Admin')).toBeInTheDocument();
    });

    it('shows empty state when no VPS granted', () => {
        render(<UsersShow user={regularUser} grantedVps={[]} availableVps={[]} />);
        expect(screen.getByText(/no vps access granted/i)).toBeInTheDocument();
    });

    it('renders granted VPS hostname', () => {
        render(<UsersShow user={regularUser} grantedVps={[vpsGrant]} availableVps={[]} />);
        expect(screen.getByText('web-server-01')).toBeInTheDocument();
    });

    it('shows firewall permission as Yes when granted', () => {
        render(<UsersShow user={regularUser} grantedVps={[vpsGrant]} availableVps={[]} />);
        const yesElements = screen.getAllByText('Yes');
        expect(yesElements.length).toBeGreaterThan(0);
    });

    it('shows Grant VPS Access button when available VPS exist', () => {
        render(<UsersShow user={regularUser} grantedVps={[]} availableVps={[availableVps]} />);
        expect(screen.getByRole('button', { name: /grant vps access/i })).toBeInTheDocument();
    });

    it('does not show Grant VPS Access button when no available VPS', () => {
        render(<UsersShow user={regularUser} grantedVps={[vpsGrant]} availableVps={[]} />);
        expect(screen.queryByRole('button', { name: /grant vps access/i })).not.toBeInTheDocument();
    });

    it('opens grant dialog on button click', async () => {
        render(<UsersShow user={regularUser} grantedVps={[]} availableVps={[availableVps]} />);
        await userEvent.click(screen.getByRole('button', { name: /grant vps access/i }));
        expect(screen.getAllByText(/grant vps access/i).length).toBeGreaterThan(1);
        expect(screen.getByRole('combobox')).toBeInTheDocument();
    });

    it('shows Revoke and Permissions buttons for each granted VPS', () => {
        render(<UsersShow user={regularUser} grantedVps={[vpsGrant]} availableVps={[]} />);
        expect(screen.getByText('Revoke')).toBeInTheDocument();
        const permButtons = screen.getAllByText('Permissions');
        expect(permButtons.length).toBeGreaterThan(0);
    });

    it('opens permissions dialog on Permissions button click', async () => {
        render(<UsersShow user={regularUser} grantedVps={[vpsGrant]} availableVps={[]} />);
        const permButtons = screen.getAllByText('Permissions');
        const actionButton = permButtons.find((el) => el.tagName === 'BUTTON' || el.closest('button'));
        await userEvent.click(actionButton!);
        expect(screen.getByText('Update Permissions')).toBeInTheDocument();
    });

    it('shows Delete User button', () => {
        render(<UsersShow user={regularUser} grantedVps={[]} availableVps={[]} />);
        expect(screen.getByRole('button', { name: /delete user/i })).toBeInTheDocument();
    });

    it('shows back link to users list', () => {
        render(<UsersShow user={regularUser} grantedVps={[]} availableVps={[]} />);
        const link = screen.getByText(/← users/i);
        expect(link).toBeInTheDocument();
        expect(link.closest('a')).toHaveAttribute('href', '/users');
    });
});
