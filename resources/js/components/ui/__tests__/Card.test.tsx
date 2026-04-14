import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '../Card';

describe('Card', () => {
    it('renders children', () => {
        render(<Card>Card body</Card>);
        expect(screen.getByText('Card body')).toBeInTheDocument();
    });

    it('applies custom className', () => {
        render(<Card className="extra">body</Card>);
        expect(screen.getByText('body')).toHaveClass('extra');
    });
});

describe('CardHeader', () => {
    it('renders children', () => {
        render(<CardHeader>Header content</CardHeader>);
        expect(screen.getByText('Header content')).toBeInTheDocument();
    });
});

describe('CardTitle', () => {
    it('renders as h3', () => {
        render(<CardTitle>My Title</CardTitle>);
        const el = screen.getByText('My Title');
        expect(el.tagName).toBe('H3');
    });

    it('applies custom className', () => {
        render(<CardTitle className="text-xl">Title</CardTitle>);
        expect(screen.getByText('Title')).toHaveClass('text-xl');
    });
});

describe('CardDescription', () => {
    it('renders as p', () => {
        render(<CardDescription>Desc</CardDescription>);
        expect(screen.getByText('Desc').tagName).toBe('P');
    });
});

describe('CardContent', () => {
    it('renders children', () => {
        render(<CardContent>Content</CardContent>);
        expect(screen.getByText('Content')).toBeInTheDocument();
    });
});

describe('CardFooter', () => {
    it('renders children', () => {
        render(<CardFooter>Footer</CardFooter>);
        expect(screen.getByText('Footer')).toBeInTheDocument();
    });
});

describe('Card composition', () => {
    it('renders full card structure', () => {
        render(
            <Card>
                <CardHeader>
                    <CardTitle>Title</CardTitle>
                    <CardDescription>Description</CardDescription>
                </CardHeader>
                <CardContent>Content area</CardContent>
                <CardFooter>Footer area</CardFooter>
            </Card>,
        );
        expect(screen.getByText('Title')).toBeInTheDocument();
        expect(screen.getByText('Description')).toBeInTheDocument();
        expect(screen.getByText('Content area')).toBeInTheDocument();
        expect(screen.getByText('Footer area')).toBeInTheDocument();
    });
});
