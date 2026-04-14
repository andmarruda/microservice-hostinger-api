import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import Dashboard from '../Dashboard';

const defaultProps = {
    vpsCount: 5,
    openReviews: 2,
    pendingApprovals: 1,
    openDriftReports: 3,
    queuePending: 10,
    queueFailed: 0,
    quotaUsed: 40,
    quotaLimit: 100,
};

describe('Dashboard page', () => {
    it('renders VPS count', () => {
        render(<Dashboard {...defaultProps} />);
        expect(screen.getByText('5')).toBeInTheDocument();
    });

    it('renders open drift reports', () => {
        render(<Dashboard {...defaultProps} />);
        expect(screen.getByText('3')).toBeInTheDocument();
    });

    it('renders pending approvals', () => {
        render(<Dashboard {...defaultProps} />);
        expect(screen.getByText('1')).toBeInTheDocument();
    });

    it('renders open access reviews', () => {
        render(<Dashboard {...defaultProps} />);
        expect(screen.getByText('2')).toBeInTheDocument();
    });

    it('renders queue pending jobs', () => {
        render(<Dashboard {...defaultProps} />);
        expect(screen.getByText('10')).toBeInTheDocument();
    });

    it('renders queue failed jobs as 0', () => {
        render(<Dashboard {...defaultProps} queueFailed={0} />);
        expect(screen.getAllByText('0').length).toBeGreaterThan(0);
    });

    it('renders failed jobs in red when > 0', () => {
        render(<Dashboard {...defaultProps} queueFailed={3} />);
        const els = screen.getAllByText('3');
        const redEl = els.find((el) => el.classList.contains('text-red-600'));
        expect(redEl).toBeTruthy();
    });

    it('shows quota percentage correctly', () => {
        render(<Dashboard {...defaultProps} quotaUsed={40} quotaLimit={100} />);
        expect(screen.getByText('40%')).toBeInTheDocument();
    });

    it('renders quota bar green when under 70%', () => {
        const { container } = render(<Dashboard {...defaultProps} quotaUsed={30} quotaLimit={100} />);
        expect(container.querySelector('.bg-green-500')).toBeInTheDocument();
    });

    it('renders quota bar yellow between 70%-90%', () => {
        const { container } = render(<Dashboard {...defaultProps} quotaUsed={75} quotaLimit={100} />);
        expect(container.querySelector('.bg-yellow-500')).toBeInTheDocument();
    });

    it('renders quota bar red at 90%+', () => {
        const { container } = render(<Dashboard {...defaultProps} quotaUsed={95} quotaLimit={100} />);
        expect(container.querySelector('.bg-red-500')).toBeInTheDocument();
    });

    it('handles zero quota limit gracefully', () => {
        render(<Dashboard {...defaultProps} quotaUsed={0} quotaLimit={0} />);
        expect(screen.getByText('0%')).toBeInTheDocument();
    });
});
