import { router } from '@inertiajs/react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import AccessReviewShow from '../Show';

const pendingReview = {
    id: 'review-1',
    reviewer_id: 'user-root',
    status: 'pending' as const,
    created_at: '2024-01-01',
    items: [
        {
            id: 'item-1',
            review_id: 'review-1',
            user_id: 'user-a',
            vps_id: 'vps-x',
            granted_at: '2024-01-01',
            expires_at: '2025-01-01',
            decision: null,
            decided_at: null,
            decided_by: null,
        },
        {
            id: 'item-2',
            review_id: 'review-1',
            user_id: 'user-b',
            vps_id: 'vps-y',
            granted_at: '2024-01-02',
            expires_at: null,
            decision: 'approved' as const,
            decided_at: '2024-01-03',
            decided_by: 'user-root',
        },
    ],
};

const completedReview = { ...pendingReview, status: 'completed' as const };
const emptyReview = { ...pendingReview, items: [] };

describe('Access Reviews Show page', () => {
    it('renders review reviewer_id', () => {
        render(<AccessReviewShow review={pendingReview} />);
        expect(screen.getByText('user-root')).toBeInTheDocument();
    });

    it('renders pending status badge', () => {
        render(<AccessReviewShow review={pendingReview} />);
        expect(screen.getAllByText('pending').length).toBeGreaterThan(0);
    });

    it('renders item user IDs', () => {
        render(<AccessReviewShow review={pendingReview} />);
        expect(screen.getByText('user-a')).toBeInTheDocument();
    });

    it('renders item vps IDs', () => {
        render(<AccessReviewShow review={pendingReview} />);
        expect(screen.getByText('vps-x')).toBeInTheDocument();
    });

    it('renders — for null expires_at', () => {
        render(<AccessReviewShow review={pendingReview} />);
        expect(screen.getByText('—')).toBeInTheDocument();
    });

    it('renders approved decision badge', () => {
        render(<AccessReviewShow review={pendingReview} />);
        expect(screen.getByText('approved')).toBeInTheDocument();
    });

    it('renders Approve and Revoke buttons for pending undecided items', () => {
        render(<AccessReviewShow review={pendingReview} />);
        expect(screen.getByRole('button', { name: /approve/i })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /revoke/i })).toBeInTheDocument();
    });

    it('does not render action buttons for decided items', () => {
        render(<AccessReviewShow review={pendingReview} />);
        // item-2 is already decided — only one Approve button (for item-1)
        expect(screen.getAllByRole('button', { name: /approve/i }).length).toBe(1);
    });

    it('does not render action buttons when review is completed', () => {
        render(<AccessReviewShow review={completedReview} />);
        expect(screen.queryByRole('button', { name: /approve/i })).not.toBeInTheDocument();
    });

    it('calls router.post on Approve click', async () => {
        render(<AccessReviewShow review={pendingReview} />);
        await userEvent.click(screen.getByRole('button', { name: /approve/i }));
        expect(vi.mocked(router.post)).toHaveBeenCalledWith(
            '/governance/reviews/review-1/items/item-1',
            { decision: 'approved' },
        );
    });

    it('calls router.post on Revoke click', async () => {
        render(<AccessReviewShow review={pendingReview} />);
        await userEvent.click(screen.getByRole('button', { name: /revoke/i }));
        expect(vi.mocked(router.post)).toHaveBeenCalledWith(
            '/governance/reviews/review-1/items/item-1',
            { decision: 'revoked' },
        );
    });

    it('renders revoked decision badge', () => {
        const reviewWithRevoked = {
            ...pendingReview,
            items: [{
                ...pendingReview.items[0]!,
                decision: 'revoked' as const,
                decided_at: '2024-01-04',
                decided_by: 'user-root',
            }],
        };
        render(<AccessReviewShow review={reviewWithRevoked} />);
        expect(screen.getByText('revoked')).toBeInTheDocument();
    });

    it('renders cancelled review status badge', () => {
        const cancelledReview = {
            ...pendingReview,
            status: 'cancelled' as const,
            items: [],
        };
        render(<AccessReviewShow review={cancelledReview} />);
        expect(screen.getByText('cancelled')).toBeInTheDocument();
    });

    it('renders empty state when no items', () => {
        render(<AccessReviewShow review={emptyReview} />);
        expect(screen.getByText(/no items in this review/i)).toBeInTheDocument();
    });
});
