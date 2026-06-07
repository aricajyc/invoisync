import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { format } from 'date-fns';

export default function Invoices({ auth, invoices }) {
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">All Invoices</h2>}
        >
            <Head title="Admin - All Invoices" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 dark:border-gray-700">
                        <div className="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                            <div>
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white">Invoice Directory</h3>
                                <p className="mt-1 text-sm text-gray-500">A complete list of all invoices in the system.</p>
                            </div>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm text-left">
                                <thead className="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-700/50 dark:text-gray-400">
                                    <tr>
                                        <th className="px-6 py-3">Invoice #</th>
                                        <th className="px-6 py-3">Creator</th>
                                        <th className="px-6 py-3">Total Payable</th>
                                        <th className="px-6 py-3">Status</th>
                                        <th className="px-6 py-3">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {invoices.data.map((invoice) => (
                                        <tr key={invoice.id} className="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                                            <td className="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                                {invoice.invoice_number || 'N/A'}
                                                <div className="text-xs text-gray-500 font-normal truncate max-w-[200px]">
                                                    Buyer: {invoice.buyer_name || 'Unknown'}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="font-medium text-gray-900 dark:text-white">
                                                    {invoice.user ? invoice.user.full_name : 'Unknown User'}
                                                </div>
                                                <div className="text-xs text-gray-500">
                                                    {invoice.user ? invoice.user.email : ''}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-gray-700 dark:text-gray-300 font-medium whitespace-nowrap">
                                                {invoice.total_payable_amount ? `RM ${parseFloat(invoice.total_payable_amount).toFixed(2)}` : 'N/A'}
                                            </td>
                                            <td className="px-6 py-4">
                                                <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                                                    invoice.status === 'Submitted' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' :
                                                    invoice.status === 'Draft' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' :
                                                    invoice.status === 'Failed' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' :
                                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
                                                }`}>
                                                    {invoice.status || 'Pending'}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-gray-500 whitespace-nowrap">
                                                {invoice.issue_date || invoice.created_at ? format(new Date(invoice.issue_date || invoice.created_at), 'MMM d, yyyy') : 'N/A'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {/* Pagination placeholder */}
                        <div className="p-4 flex items-center justify-between border-t border-gray-100 dark:border-gray-700">
                            <span className="text-sm text-gray-500">
                                Showing {invoices.from} to {invoices.to} of {invoices.total} invoices
                            </span>
                            <div className="flex space-x-1">
                                {invoices.links.map((link, index) => (
                                    <Link
                                        key={index}
                                        href={link.url || '#'}
                                        className={`px-3 py-1 rounded-md text-sm ${link.active ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'} ${!link.url && 'opacity-50 cursor-not-allowed'}`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    ></Link>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
