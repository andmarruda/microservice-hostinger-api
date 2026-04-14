import { router } from '@inertiajs/react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import DomainsAvailability from '../Availability';

describe('Domain Availability page', () => {
    it('renders domain input', () => {
        render(<DomainsAvailability result={null} query={null} />);
        expect(screen.getByLabelText(/domain name/i)).toBeInTheDocument();
    });

    it('renders Check button', () => {
        render(<DomainsAvailability result={null} query={null} />);
        expect(screen.getByRole('button', { name: /check/i })).toBeInTheDocument();
    });

    it('pre-fills input from query prop', () => {
        render(<DomainsAvailability result={null} query="example.com" />);
        expect(screen.getByDisplayValue('example.com')).toBeInTheDocument();
    });

    it('calls router.get on Check submit', async () => {
        render(<DomainsAvailability result={null} query={null} />);
        await userEvent.click(screen.getByRole('button', { name: /check/i }));
        expect(vi.mocked(router.get)).toHaveBeenCalledWith(
            '/domains/check',
            expect.any(Object),
            expect.any(Object),
        );
    });

    it('shows available badge for available domain', () => {
        render(
            <DomainsAvailability
                result={{ domain: 'cool.io', available: true, premium: false, price: 12.99, currency: 'USD' }}
                query="cool.io"
            />,
        );
        expect(screen.getByText('cool.io')).toBeInTheDocument();
        expect(screen.getByText('Available')).toBeInTheDocument();
        expect(screen.getByText(/12.99 USD/)).toBeInTheDocument();
    });

    it('shows premium label for premium domains', () => {
        render(
            <DomainsAvailability
                result={{ domain: 'premium.io', available: true, premium: true, price: 500, currency: 'USD' }}
                query="premium.io"
            />,
        );
        expect(screen.getByText('(Premium)')).toBeInTheDocument();
    });

    it('shows Taken badge for unavailable domain', () => {
        render(
            <DomainsAvailability
                result={{ domain: 'taken.com', available: false, premium: false }}
                query="taken.com"
            />,
        );
        expect(screen.getByText('Taken')).toBeInTheDocument();
    });

    it('does not show result card when result is null', () => {
        render(<DomainsAvailability result={null} query={null} />);
        expect(screen.queryByText('Available')).not.toBeInTheDocument();
        expect(screen.queryByText('Taken')).not.toBeInTheDocument();
    });
});
