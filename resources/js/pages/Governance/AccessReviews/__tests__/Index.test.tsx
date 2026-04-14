import { useForm } from '@inertiajs/react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import AccessReviewsIndex from '../Index';

const reviews = [
    { id: 'r1', reviewer_id: 'user-1', status: 'pending' as const, created_at: '2024-01-01', items: [] },
    { id: 'r2', reviewer_id: 'user-2', status: 'completed' as const, created_at: '2024-01-02', items: [{}] as never },
    { id: 'r3', reviewer_id: 'user-3', status: 'cancelled' as const, created_at: '2024-01-03', items: [] },
];

describe('Access Reviews Index page', () => {
    it('renders empty state when no reviews', () => {
        render(<AccessReviewsIndex reviews={[]} />);
        expect(screen.getByText(/no access reviews found/i)).toBeInTheDocument();
    });

    it('renders review IDs (truncated)', () => {
        render(<AccessReviewsIndex reviews={reviews} />);
        expect(screen.getByText('r1')).toBeInTheDocument();
    });

    it('renders reviewer IDs', () => {
        render(<AccessReviewsIndex reviews={reviews} />);
        expect(screen.getByText('user-1')).toBeInTheDocument();
    });

    it('renders status badges', () => {
        render(<AccessReviewsIndex reviews={reviews} />);
        expect(screen.getByText('pending')).toBeInTheDocument();
        expect(screen.getByText('completed')).toBeInTheDocument();
        expect(screen.getByText('cancelled')).toBeInTheDocument();
    });

    it('renders item counts', () => {
        render(<AccessReviewsIndex reviews={reviews} />);
        expect(screen.getByText('1')).toBeInTheDocument();
    });

    it('renders View links', () => {
        render(<AccessReviewsIndex reviews={reviews} />);
        expect(screen.getAllByRole('link', { name: /^view$/i }).length).toBe(3);
    });

    it('New Review button calls post', async () => {
        const post = vi.fn();
        vi.mocked(useForm).mockReturnValue({
            data: {},
            setData: vi.fn(),
            post,
            errors: {},
            processing: false,
        } as ReturnType<typeof useForm>);

        render(<AccessReviewsIndex reviews={[]} />);
        await userEvent.click(screen.getByRole('button', { name: /new review/i }));
        expect(post).toHaveBeenCalledWith('/governance/reviews');
    });

    it('renders created_at dates', () => {
        render(<AccessReviewsIndex reviews={reviews} />);
        expect(screen.getByText('2024-01-01')).toBeInTheDocument();
    });
});
