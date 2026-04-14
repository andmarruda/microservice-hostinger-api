import { render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../Table';

describe('Table', () => {
    it('renders a table element', () => {
        render(<Table />);
        expect(screen.getByRole('table')).toBeInTheDocument();
    });

    it('wraps table in overflow-auto div', () => {
        const { container } = render(<Table />);
        expect(container.firstChild).toHaveClass('overflow-auto');
    });

    it('applies custom className to table', () => {
        render(<Table className="custom-table" />);
        expect(screen.getByRole('table')).toHaveClass('custom-table');
    });
});

describe('TableHeader', () => {
    it('renders thead', () => {
        render(
            <table>
                <TableHeader>
                    <tr>
                        <th>H</th>
                    </tr>
                </TableHeader>
            </table>,
        );
        expect(document.querySelector('thead')).toBeInTheDocument();
    });
});

describe('TableBody', () => {
    it('renders tbody', () => {
        render(
            <table>
                <TableBody>
                    <tr>
                        <td>cell</td>
                    </tr>
                </TableBody>
            </table>,
        );
        expect(document.querySelector('tbody')).toBeInTheDocument();
    });
});

describe('TableRow', () => {
    it('renders a tr element', () => {
        render(
            <table>
                <tbody>
                    <TableRow>
                        <td>row</td>
                    </TableRow>
                </tbody>
            </table>,
        );
        expect(document.querySelector('tr')).toBeInTheDocument();
    });

    it('applies hover class', () => {
        render(
            <table>
                <tbody>
                    <TableRow>
                        <td>r</td>
                    </TableRow>
                </tbody>
            </table>,
        );
        expect(document.querySelector('tr')).toHaveClass('hover:bg-gray-50');
    });
});

describe('TableHead', () => {
    it('renders a th element', () => {
        render(
            <table>
                <thead>
                    <tr>
                        <TableHead>Name</TableHead>
                    </tr>
                </thead>
            </table>,
        );
        expect(screen.getByRole('columnheader', { name: 'Name' })).toBeInTheDocument();
    });
});

describe('TableCell', () => {
    it('renders a td element', () => {
        render(
            <table>
                <tbody>
                    <tr>
                        <TableCell>Value</TableCell>
                    </tr>
                </tbody>
            </table>,
        );
        expect(screen.getByRole('cell', { name: 'Value' })).toBeInTheDocument();
    });
});

describe('Table full composition', () => {
    it('renders a complete table', () => {
        render(
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Name</TableHead>
                        <TableHead>Status</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow>
                        <TableCell>server-01</TableCell>
                        <TableCell>running</TableCell>
                    </TableRow>
                </TableBody>
            </Table>,
        );
        expect(screen.getByText('Name')).toBeInTheDocument();
        expect(screen.getByText('server-01')).toBeInTheDocument();
        expect(screen.getByText('running')).toBeInTheDocument();
    });
});
