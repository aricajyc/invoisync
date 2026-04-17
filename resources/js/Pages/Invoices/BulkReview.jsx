import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import { useState } from 'react';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

export default function BulkReview({ parsedData, filename }) {
    const [rows, setRows] = useState(parsedData || []);
    
    const { post, processing, data, setData } = useForm({
        invoices: rows
    });

    const handleCellChange = (rowIndex, field, value) => {
        const newRows = [...rows];
        
        // Try parsing JSON if modifying an object
        let finalValue = value;
        try {
            if ((value.startsWith('[') && value.endsWith(']')) || (value.startsWith('{') && value.endsWith('}'))) {
                finalValue = JSON.parse(value);
            }
        } catch(e) { } // leave as string if it fails to parse

        newRows[rowIndex].data[field] = finalValue;
        newRows[rowIndex].is_valid = true; 
        
        setRows(newRows);
        setData('invoices', newRows);
    };

    const handleCommit = () => {
        post(route('invoices.bulk-commit'));
    };

    if (rows.length === 0) {
        return (
            <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Bulk Upload Review</h2>}>
                <div className="py-12"><div className="mx-auto max-w-7xl sm:px-6 lg:px-8">No valid data found in file.</div></div>
            </AuthenticatedLayout>
        );
    }

    const headers = Object.keys(rows[0]?.data || {});

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Review Upload: {filename}
                    </h2>
                    <div className="flex gap-2">
                        <Link href={route('invoices.index')}>
                            <SecondaryButton>Cancel</SecondaryButton>
                        </Link>
                        <PrimaryButton onClick={handleCommit} disabled={processing || rows.length === 0}>
                            {processing ? 'Committing...' : 'Commit & Save'}
                        </PrimaryButton>
                    </div>
                </div>
            }
        >
            <Head title="Review Bulk Upload" />

            <div className="py-12">
                <div className="mx-auto max-w-[95%] sm:px-6 lg:px-8">
                    <div className="bg-white shadow-sm sm:rounded-lg dark:bg-gray-800 overflow-hidden">
                        <div className="p-6 text-gray-900 dark:text-gray-100">
                            <p className="mb-4 text-sm text-gray-600 dark:text-gray-400">
                                Please review the parsed data below. Click any cell to edit it inline. Once everything looks correct, click 'Commit & Save'.
                            </p>
                            <div className="overflow-x-auto max-h-[70vh]">
                                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200 dark:border-gray-700">
                                    <thead className="bg-gray-50 dark:bg-gray-700 sticky top-0 z-10">
                                        <tr>
                                            <th className="px-4 py-3 text-left pl-2 text-xs font-medium text-gray-500 uppercase tracking-wider w-16 bg-gray-50 dark:bg-gray-700">Row</th>
                                            {headers.map(header => (
                                                <th key={header} className="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[200px] bg-gray-50 dark:bg-gray-700">
                                                    {header.replace(/_/g, ' ')}
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                        {rows.map((row, rIndex) => (
                                            <tr key={row.id || rIndex} className={row.is_valid ? 'hover:bg-gray-50 dark:hover:bg-gray-700' : 'bg-red-50 dark:bg-red-900/20'}>
                                                <td className="px-4 py-2 text-sm text-gray-500 border-r border-gray-200 dark:border-gray-700">
                                                    {rIndex + 1}
                                                    {!row.is_valid && <span className="text-red-500 block text-xs">Error</span>}
                                                </td>
                                                {headers.map(header => {
                                                    // Handle array serialization (like line_items)
                                                    let cellValue = row.data[header] || '';
                                                    if (typeof cellValue === 'object') {
                                                        cellValue = JSON.stringify(cellValue);
                                                    }
                                                    
                                                    return (
                                                    <td key={header} className="p-0 border-r border-gray-200 dark:border-gray-700 relative">
                                                        <input 
                                                            type="text"
                                                            value={cellValue}
                                                            onChange={(e) => handleCellChange(rIndex, header, e.target.value)}
                                                            className={`w-full h-full p-2 border-0 focus:ring-2 focus:ring-inset focus:ring-indigo-500 text-sm dark:bg-gray-900 dark:text-gray-300 ${!row.is_valid && row.errors && row.errors[header] ? 'bg-red-100 dark:bg-red-800/40 text-red-900 dark:text-red-100' : 'bg-transparent'}`}
                                                            title={row.errors?.[header] || ''}
                                                            placeholder="-"
                                                        />
                                                    </td>
                                                )})}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
