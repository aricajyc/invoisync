import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  ArcElement,
} from 'chart.js';
import { Line, Bar, Pie } from 'react-chartjs-2';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  ArcElement,
  Title,
  Tooltip,
  Legend
);

export default function Dashboard({ stats, revenueTrends, topCustomers, rejectionReasons, filters }) {
    const [startDate, setStartDate] = useState(filters.start_date);
    const [endDate, setEndDate] = useState(filters.end_date);
    const [groupBy, setGroupBy] = useState(filters.group_by);

    const handleFilter = () => {
        router.get(route('dashboard'), {
            start_date: startDate,
            end_date: endDate,
            group_by: groupBy
        }, { preserveState: true });
    };

    // Revenue Trend Line Chart Data
    const revenueData = {
        labels: revenueTrends.map(item => item.date),
        datasets: [
            {
                label: 'Revenue (MYR)',
                data: revenueTrends.map(item => item.revenue),
                borderColor: 'rgb(79, 70, 229)', // Indigo 600
                backgroundColor: 'rgba(79, 70, 229, 0.5)',
                tension: 0.3,
            }
        ]
    };

    // Top Customers Bar Chart Data
    const topCustomersData = {
        labels: topCustomers.map(item => item.buyer_name),
        datasets: [
            {
                label: 'Revenue (MYR)',
                data: topCustomers.map(item => item.total_revenue),
                backgroundColor: 'rgba(16, 185, 129, 0.7)', // Emerald 500
            }
        ]
    };

    // Rejection Reasons Pie Chart Data
    // Generate colors for pie chart
    const pieColors = [
        'rgba(239, 68, 68, 0.7)', // Red
        'rgba(245, 158, 11, 0.7)', // Amber
        'rgba(59, 130, 246, 0.7)', // Blue
        'rgba(139, 92, 246, 0.7)', // Violet
        'rgba(107, 114, 128, 0.7)', // Gray
    ];

    const rejectionData = {
        labels: rejectionReasons.map(item => item.rejection_reason.split(' | ')[0]),
        datasets: [
            {
                data: rejectionReasons.map(item => item.count),
                backgroundColor: pieColors,
                borderWidth: 1,
            }
        ]
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Business Intelligence Dashboard
                    </h2>
                    
                    {/* Date Filters */}
                    <div className="flex space-x-2 items-center bg-white dark:bg-gray-800 p-2 rounded-lg shadow-sm">
                        <input 
                            type="date" 
                            className="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm text-sm"
                            value={startDate}
                            onChange={(e) => setStartDate(e.target.value)}
                        />
                        <span className="text-gray-500">to</span>
                        <input 
                            type="date" 
                            className="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm text-sm"
                            value={endDate}
                            onChange={(e) => setEndDate(e.target.value)}
                        />
                        
                        <select 
                            className="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm text-sm ml-2"
                            value={groupBy}
                            onChange={(e) => setGroupBy(e.target.value)}
                        >
                            <option value="day">By Day</option>
                            <option value="month">By Month</option>
                        </select>
                        
                        <button 
                            onClick={handleFilter}
                            className="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                        >
                            Apply
                        </button>
                    </div>
                </div>
            }
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    
                    {/* Top Level Financials */}
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-2 mb-6">
                        <div className="overflow-hidden rounded-lg bg-gradient-to-r from-indigo-500 to-indigo-600 p-6 shadow-lg text-white">
                            <div className="text-sm font-medium text-indigo-100 uppercase tracking-wider">Total Revenue (Valid)</div>
                            <div className="mt-2 text-4xl font-bold">MYR {Number(stats.total_revenue || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                            <div className="mt-1 text-sm text-indigo-200">Excluding Tax</div>
                        </div>
                        <div className="overflow-hidden rounded-lg bg-gradient-to-r from-red-500 to-red-600 p-6 shadow-lg text-white">
                            <div className="text-sm font-medium text-red-100 uppercase tracking-wider">Est. Tax Liability</div>
                            <div className="mt-2 text-4xl font-bold">MYR {Number(stats.tax_liability || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                            <div className="mt-1 text-sm text-red-200">From Valid Invoices Only</div>
                        </div>
                    </div>

                    {/* Operational Stats Grid */}
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                        <div className="overflow-hidden rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800 border-l-4 border-gray-400">
                            <div className="text-sm font-medium text-gray-500 dark:text-gray-400">Draft Invoices</div>
                            <div className="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{stats.draft}</div>
                        </div>
                        <div className="overflow-hidden rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800 border-l-4 border-green-500">
                            <div className="text-sm font-medium text-gray-500 dark:text-gray-400">Valid Invoices</div>
                            <div className="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">{stats.valid}</div>
                        </div>
                        <div className="overflow-hidden rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800 border-l-4 border-red-500">
                            <div className="text-sm font-medium text-gray-500 dark:text-gray-400">Invalid / Rejected</div>
                            <div className="mt-2 text-3xl font-bold text-red-600 dark:text-red-400">{stats.invalid}</div>
                        </div>
                        <div className="overflow-hidden rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800 border-l-4 border-yellow-500">
                            <div className="text-sm font-medium text-gray-500 dark:text-gray-400">Cancelled</div>
                            <div className="mt-2 text-3xl font-bold text-yellow-600 dark:text-yellow-400">{stats.cancelled}</div>
                        </div>
                    </div>

                    {/* Charts Grid */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        {/* Revenue Trend */}
                        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Revenue Trend</h3>
                            {revenueTrends.length > 0 ? (
                                <Line data={revenueData} options={{ responsive: true, maintainAspectRatio: true }} />
                            ) : (
                                <div className="flex h-48 items-center justify-center text-gray-400">No revenue data for this period</div>
                            )}
                        </div>

                        {/* Top Customers */}
                        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Top Customers</h3>
                            {topCustomers.length > 0 ? (
                                <Bar data={topCustomersData} options={{ responsive: true, maintainAspectRatio: true }} />
                            ) : (
                                <div className="flex h-48 items-center justify-center text-gray-400">No customer data for this period</div>
                            )}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Error Breakdown */}
                        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">LHDN Validation Error Breakdown</h3>
                            {rejectionReasons.length > 0 ? (
                                <div className="w-2/3 mx-auto">
                                    <Pie 
                                        data={rejectionData} 
                                        options={{ 
                                            responsive: true, 
                                            maintainAspectRatio: true,
                                            plugins: {
                                                legend: {
                                                    position: 'bottom',
                                                    align: 'start'
                                                }
                                            }
                                        }} 
                                    />
                                </div>
                            ) : (
                                <div className="flex h-48 items-center justify-center text-gray-400">No rejection errors recorded! 🎉</div>
                            )}
                        </div>
                    </div>

                </div>
            </div>
        </AuthenticatedLayout>
    );
}
