import '@testing-library/jest-dom/vitest';
import { vi } from 'vitest';

// jsdom does not implement HTMLDialogElement methods
HTMLDialogElement.prototype.showModal = vi.fn();
HTMLDialogElement.prototype.close = vi.fn();

// Allow window.location.href assignment in tests
Object.defineProperty(window, 'location', {
    writable: true,
    value: { href: '' },
});

// Reset all vi mocks between tests
beforeEach(() => {
    vi.clearAllMocks();
});
