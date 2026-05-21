<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        // Date Filtering
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $groupBy = $request->input('group_by', 'day'); // 'day' or 'month'

        // Base Query scoped to user and date range
        $baseQuery = $user->invoices()->whereBetween('invoice_date_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        // 1. Basic Stats
        $stats = [
            'draft' => (clone $baseQuery)->where('status', 'draft')->count(),
            'valid' => (clone $baseQuery)->whereIn('status', ['validated', 'submitted', 'approved'])->count(),
            'invalid' => (clone $baseQuery)->whereIn('status', ['rejected', 'invalid'])->count(),
            'cancelled' => (clone $baseQuery)->where('status', 'cancelled')->count(),
            'failed' => 0,
            
            // Tax Liability (from valid invoices only)
            'tax_liability' => (clone $baseQuery)->whereIn('status', ['validated', 'approved'])->sum('total_tax_amount'),
            
            // Total Revenue (excluding tax) from valid invoices
            'total_revenue' => (clone $baseQuery)->whereIn('status', ['validated', 'approved'])->sum('total_excluding_tax'),
        ];

        // 2. Revenue Trends
        $dateFormat = $groupBy === 'month' ? '%Y-%m' : '%Y-%m-%d';
        $revenueTrends = (clone $baseQuery)
            ->whereIn('status', ['validated', 'approved'])
            ->selectRaw("DATE_FORMAT(invoice_date_time, '{$dateFormat}') as date, SUM(total_excluding_tax) as revenue")
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // 3. Top Customers (by revenue)
        $topCustomers = (clone $baseQuery)
            ->whereIn('status', ['validated', 'approved'])
            ->selectRaw('buyer_name, SUM(total_excluding_tax) as total_revenue')
            ->groupBy('buyer_name')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();

        // 4. Rejection Reasons Breakdown
        // We join myinvois_submissions to get the exact rejection reasons for the user's invoices
        $rejectionReasons = \App\Models\MyinvoisSubmission::join('invoices', 'myinvois_submissions.invoice_id', '=', 'invoices.id')
            ->where('invoices.user_id', $user->id)
            ->whereBetween('invoices.invoice_date_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereNotNull('myinvois_submissions.rejection_reason')
            ->selectRaw('myinvois_submissions.rejection_reason, COUNT(*) as count')
            ->groupBy('myinvois_submissions.rejection_reason')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'revenueTrends' => $revenueTrends,
            'topCustomers' => $topCustomers,
            'rejectionReasons' => $rejectionReasons,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'group_by' => $groupBy,
            ]
        ]);
    }
}
