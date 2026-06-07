import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { UserGroupIcon, DocumentTextIcon, ChartBarIcon } from '@heroicons/react/24/outline';
import { format } from 'date-fns';

export default function Dashboard({ auth, totalUsers, totalInvoices, recentUsers, recentInvoices }) {
    const stats = [
        { name: 'Total Users', stat: totalUsers, icon: UserGroupIcon, color: 'text-indigo-500', bgColor: 'bg-indigo-100' },
        { name: 'Total Invoices', stat: totalInvoices, icon: DocumentTextIcon, color: 'text-emerald-500', bgColor: 'bg-emerald-100' },
        { name: 'System Health', stat: '99.9%', icon: ChartBarIcon, color: 'text-rose-500', bgColor: 'bg-rose-100' },
    ];

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Admin Dashboard</h2>}
        >
            <Head title="Admin Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    
                    {/* Stats Grid */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {stats.map((item) => (
                            <div key={item.name} className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-2xl transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
                                <div className="p-6 flex items-center">
                                    <div className={`p-4 rounded-xl ${item.bgColor} bg-opacity-10 dark:bg-opacity-20`}>
                                        <item.icon className={`h-8 w-8 ${item.color}`} aria-hidden="true" />
                                    </div>
                                    <div className="ml-5">
                                        <p className="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">{item.name}</p>
                                        <p className="mt-1 text-3xl font-semibold text-gray-900 dark:text-white">{item.stat}</p>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Recent Users */}
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 dark:border-gray-700">
                            <div className="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white">Recent Users</h3>
                                <span className="text-xs font-medium px-2.5 py-0.5 rounded-full bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300">
                                    Latest {recentUsers.length}
                                </span>
                            </div>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm text-left">
                                    <thead className="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-700/50 dark:text-gray-400">
                                        <tr>
                                            <th className="px-6 py-3">Name</th>
                                            <th className="px-6 py-3">Type</th>
                                            <th className="px-6 py-3">Joined</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {recentUsers.map((user) => (
                                            <tr key={user.id} className="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                                                <td className="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                                    {user.full_name}
                                                    <div className="text-xs text-gray-500 font-normal">{user.email}</div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                                                        user.user_type === 'Admin' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300' :
                                                        user.user_type === 'B2B' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' :
                                                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                                    }`}>
                                                        {user.user_type || 'User'}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 text-gray-500 whitespace-nowrap">
                                                    {format(new Date(user.created_at), 'MMM d, yyyy')}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                            {recentUsers.length === 0 && (
                                <div className="p-8 text-center text-gray-500 dark:text-gray-400">No recent users found.</div>
                            )}
                        </div>

                        {/* Recent Invoices */}
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 dark:border-gray-700">
                            <div className="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white">Recent Invoices</h3>
                                <span className="text-xs font-medium px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300">
                                    Latest {recentInvoices.length}
                                </span>
                            </div>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm text-left">
                                    <thead className="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-700/50 dark:text-gray-400">
                                        <tr>
                                            <th className="px-6 py-3">Inv #</th>
                                            <th className="px-6 py-3">Total</th>
                                            <th className="px-6 py-3">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {recentInvoices.map((invoice) => (
                                            <tr key={invoice.id} className="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                                                <td className="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                                    {invoice.invoice_number || 'N/A'}
                                                    <div className="text-xs text-gray-500 font-normal truncate max-w-[150px]">
                                                        {invoice.buyer_name || (invoice.user ? invoice.user.full_name : 'Unknown')}
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
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                            {recentInvoices.length === 0 && (
                                <div className="p-8 text-center text-gray-500 dark:text-gray-400">No recent invoices found.</div>
                            )}
                        </div>
                    </div>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
