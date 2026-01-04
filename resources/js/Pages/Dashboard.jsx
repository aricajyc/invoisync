import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';

export default function Dashboard({ stats }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Dashboard
                </h2>
            }
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                        <div className="overflow-hidden rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                            <div className="text-sm font-medium text-gray-500 dark:text-gray-400">Draft Invoices</div>
                            <div className="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{stats.draft}</div>
                        </div>
                        <div className="overflow-hidden rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                            <div className="text-sm font-medium text-gray-500 dark:text-gray-400">Valid Invoices</div>
                            <div className="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">{stats.valid}</div>
                        </div>
                        <div className="overflow-hidden rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                            <div className="text-sm font-medium text-gray-500 dark:text-gray-400">Invalid Invoices</div>
                            <div className="mt-2 text-3xl font-bold text-red-600 dark:text-red-400">{stats.invalid}</div>
                        </div>
                        <div className="overflow-hidden rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                            <div className="text-sm font-medium text-gray-500 dark:text-gray-400">Cancelled Invoices</div>
                            <div className="mt-2 text-3xl font-bold text-yellow-600 dark:text-yellow-400">{stats.cancelled}</div>
                        </div>
                        <div className="overflow-hidden rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
                            <div className="text-sm font-medium text-gray-500 dark:text-gray-400">Failed Invoices</div>
                            <div className="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{stats.failed}</div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
