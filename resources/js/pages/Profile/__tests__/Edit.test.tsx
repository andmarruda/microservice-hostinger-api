import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import ProfileEdit from '../Edit';

const user = {
    id: 1,
    name: 'John Engineer',
    email: 'john@example.com',
};

describe('Profile/Edit page', () => {
    it('renders the profile page title', () => {
        render(<ProfileEdit user={user} />);
        expect(screen.getByText('Profile Information')).toBeInTheDocument();
    });

    it('renders the change password section', () => {
        render(<ProfileEdit user={user} />);
        expect(screen.getByText('Change Password')).toBeInTheDocument();
    });

    it('shows current name in the name field', () => {
        render(<ProfileEdit user={user} />);
        const nameInput = screen.getByLabelText(/^name$/i) as HTMLInputElement;
        expect(nameInput.value).toBe('John Engineer');
    });

    it('shows user email as disabled', () => {
        render(<ProfileEdit user={user} />);
        const emailInput = screen.getByDisplayValue('john@example.com') as HTMLInputElement;
        expect(emailInput).toBeDisabled();
    });

    it('shows email cannot be changed notice', () => {
        render(<ProfileEdit user={user} />);
        expect(screen.getByText(/email cannot be changed/i)).toBeInTheDocument();
    });

    it('has a save button for profile update', () => {
        render(<ProfileEdit user={user} />);
        const saveButtons = screen.getAllByRole('button', { name: /save/i });
        expect(saveButtons.length).toBeGreaterThan(0);
    });

    it('has an update password button', () => {
        render(<ProfileEdit user={user} />);
        expect(screen.getByRole('button', { name: /update password/i })).toBeInTheDocument();
    });

    it('has current password, new password, and confirm password fields', () => {
        render(<ProfileEdit user={user} />);
        expect(screen.getByLabelText(/current password/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/^new password$/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/confirm new password/i)).toBeInTheDocument();
    });
});
