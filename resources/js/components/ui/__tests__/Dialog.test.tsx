import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '../Dialog';

describe('Dialog', () => {
    it('calls showModal when opened', () => {
        render(<Dialog open onClose={() => {}}>Content</Dialog>);
        expect(HTMLDialogElement.prototype.showModal).toHaveBeenCalledOnce();
    });

    it('calls close when closed', () => {
        const { rerender } = render(<Dialog open onClose={() => {}}>Content</Dialog>);
        rerender(<Dialog open={false} onClose={() => {}}>Content</Dialog>);
        expect(HTMLDialogElement.prototype.close).toHaveBeenCalled();
    });

    it('renders children when open', () => {
        render(<Dialog open onClose={() => {}}>Dialog body</Dialog>);
        expect(screen.getByText('Dialog body')).toBeInTheDocument();
    });

    it('applies custom className', () => {
        const { container } = render(
            <Dialog open onClose={() => {}} className="custom-dialog">
                Content
            </Dialog>,
        );
        expect(container.querySelector('dialog')).toHaveClass('custom-dialog');
    });

    it('calls onClose when close event fires', () => {
        const onClose = vi.fn();
        const { container } = render(<Dialog open onClose={onClose}>Content</Dialog>);
        const dialog = container.querySelector('dialog')!;
        dialog.dispatchEvent(new Event('close'));
        expect(onClose).toHaveBeenCalledOnce();
    });
});

describe('DialogHeader', () => {
    it('renders children', () => {
        render(<DialogHeader>Header</DialogHeader>);
        expect(screen.getByText('Header')).toBeInTheDocument();
    });
});

describe('DialogTitle', () => {
    it('renders as h2', () => {
        render(<DialogTitle>My Title</DialogTitle>);
        expect(screen.getByRole('heading', { name: 'My Title' })).toBeInTheDocument();
    });
});

describe('DialogContent', () => {
    it('renders children', () => {
        render(<DialogContent>Content area</DialogContent>);
        expect(screen.getByText('Content area')).toBeInTheDocument();
    });
});

describe('DialogFooter', () => {
    it('renders children', () => {
        render(<DialogFooter>Footer</DialogFooter>);
        expect(screen.getByText('Footer')).toBeInTheDocument();
    });

    it('has justify-end class', () => {
        const { container } = render(<DialogFooter>F</DialogFooter>);
        expect(container.firstChild).toHaveClass('justify-end');
    });
});
