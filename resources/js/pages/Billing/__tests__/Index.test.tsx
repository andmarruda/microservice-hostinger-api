import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';
import BillingIndex from '../Index';

const catalog = [
    { id: 'c1', name: 'KVM 2', price: 9.99, currency: 'USD', billing_cycle: 'monthly', description: 'Starter plan' },
    { id: 'c2', name: 'KVM 4', price: 19.99, currency: 'USD', billing_cycle: 'monthly' },
];

const subscriptions = [
    { id: 's1', name: 'KVM 2', status: 'active', price: 9.99, currency: 'USD', billing_cycle: 'monthly', next_billing_date: '2025-02-01' },
    { id: 's2', name: 'KVM 4', status: 'past_due', price: 19.99, currency: 'USD', billing_cycle: 'monthly', next_billing_date: null },
];

const paymentMethods = [
    { id: 'pm1', type: 'card', last4: '4242', brand: 'Visa', is_default: true },
    { id: 'pm2', type: 'card', last4: '5555', brand: 'Mastercard', is_default: false },
];

const defaultProps = { catalog, subscriptions, paymentMethods };

describe('Billing Index page', () => {
    it('renders subscriptions tab by default', () => {
        render(<BillingIndex {...defaultProps} />);
        expect(screen.getByText('Active Subscriptions')).toBeInTheDocument();
    });

    it('renders subscription names', () => {
        render(<BillingIndex {...defaultProps} />);
        expect(screen.getByText('KVM 2')).toBeInTheDocument();
    });

    it('renders active status badge', () => {
        render(<BillingIndex {...defaultProps} />);
        expect(screen.getByText('active')).toBeInTheDocument();
    });

    it('renders past_due status badge', () => {
        render(<BillingIndex {...defaultProps} />);
        expect(screen.getByText('past_due')).toBeInTheDocument();
    });

    it('renders next billing date', () => {
        render(<BillingIndex {...defaultProps} />);
        expect(screen.getByText('2025-02-01')).toBeInTheDocument();
    });

    it('renders — for null next_billing_date', () => {
        render(<BillingIndex {...defaultProps} />);
        expect(screen.getByText('—')).toBeInTheDocument();
    });

    it('switches to Catalog tab', async () => {
        render(<BillingIndex {...defaultProps} />);
        await userEvent.click(screen.getByRole('button', { name: /catalog/i }));
        expect(screen.getByText('Starter plan')).toBeInTheDocument();
        expect(screen.getByText('9.99')).toBeInTheDocument();
    });

    it('renders catalog items without description', async () => {
        render(<BillingIndex {...defaultProps} />);
        await userEvent.click(screen.getByRole('button', { name: /catalog/i }));
        expect(screen.getByText('KVM 4')).toBeInTheDocument();
    });

    it('switches to Payment Methods tab', async () => {
        render(<BillingIndex {...defaultProps} />);
        await userEvent.click(screen.getByRole('button', { name: /payment methods/i }));
        expect(screen.getByText('Visa')).toBeInTheDocument();
        expect(screen.getByText('····4242')).toBeInTheDocument();
    });

    it('shows Default badge for default payment method', async () => {
        render(<BillingIndex {...defaultProps} />);
        await userEvent.click(screen.getByRole('button', { name: /payment methods/i }));
        expect(screen.getByText('Default')).toBeInTheDocument();
    });

    it('renders cancelled subscription status badge', () => {
        const cancelled = { ...subscriptions[0]!, status: 'cancelled' };
        render(<BillingIndex {...defaultProps} subscriptions={[cancelled]} />);
        expect(screen.getByText('cancelled')).toBeInTheDocument();
    });

    it('renders trialing subscription status badge', () => {
        const trialing = { ...subscriptions[0]!, status: 'trialing' };
        render(<BillingIndex {...defaultProps} subscriptions={[trialing]} />);
        expect(screen.getByText('trialing')).toBeInTheDocument();
    });

    it('renders empty subscriptions state', () => {
        render(<BillingIndex {...defaultProps} subscriptions={[]} />);
        expect(screen.getByText('No subscriptions.')).toBeInTheDocument();
    });

    it('renders empty catalog state', async () => {
        render(<BillingIndex {...defaultProps} catalog={[]} />);
        await userEvent.click(screen.getByRole('button', { name: /catalog/i }));
        expect(screen.getByText('No catalog items.')).toBeInTheDocument();
    });

    it('renders empty payment methods state', async () => {
        render(<BillingIndex {...defaultProps} paymentMethods={[]} />);
        await userEvent.click(screen.getByRole('button', { name: /payment methods/i }));
        expect(screen.getByText('No payment methods on file.')).toBeInTheDocument();
    });
});
