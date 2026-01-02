<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\SubmissionAnalytic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard overview
     * 
     * @group Dashboard
     */
    public function overview(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $period = $request->get('period', 'month'); // day, week, month, year

        $dateFrom = match($period) {
            'day' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        $dateTo = now();

        // Invoice statistics
        $invoiceStats = [
            'total' => Invoice::where('user_id', $userId)
                ->dateRange($dateFrom, $dateTo)
                ->count(),
            'draft' => Invoice::where('user_id', $userId)
                ->draft()
                ->dateRange($dateFrom, $dateTo)
                ->count(),
            'validated' => Invoice::where('user_id', $userId)
                ->validated()
                ->dateRange($dateFrom, $dateTo)
                ->count(),
            'submitted' => Invoice::where('user_id', $userId)
                ->submitted()
                ->dateRange($dateFrom, $dateTo)
                ->count(),
            'rejected' => Invoice::where('user_id', $userId)
                ->rejected()
                ->dateRange($dateFrom, $dateTo)
                ->count(),
        ];

        // Financial statistics
        $financialStats = [
            'total_amount' => Invoice::where('user_id', $userId)
                ->whereIn('status', ['validated', 'submitted', 'approved'])
                ->dateRange($dateFrom, $dateTo)
                ->sum('total_payable_amount'),
            'total_tax' => Invoice::where('user_id', $userId)
                ->whereIn('status', ['validated', 'submitted', 'approved'])
                ->dateRange($dateFrom, $dateTo)
                ->sum('total_tax_amount'),
            'average_invoice_value' => Invoice::where('user_id', $userId)
                ->whereIn('status', ['validated', 'submitted', 'approved'])
                ->dateRange($dateFrom, $dateTo)
                ->avg('total_payable_amount'),
        ];

        // Recent invoices
        $recentInvoices = Invoice::where('user_id', $userId)
            ->with(['lineItems'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Invoice by type
        $invoicesByType = Invoice::where('user_id', $userId)
            ->dateRange($dateFrom, $dateTo)
            ->select('invoice_type', DB::raw('count(*) as count'), DB::raw('sum(total_payable_amount) as total'))
            ->groupBy('invoice_type')
            ->get();

        // Daily trend
        $dailyTrend = Invoice::where('user_id', $userId)
            ->dateRange($dateFrom, $dateTo)
            ->select(
                DB::raw('DATE(invoice_date_time) as date'),
                DB::raw('count(*) as count'),
                DB::raw('sum(total_payable_amount) as total')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'data' => [
                'invoice_statistics' => $invoiceStats,
                'financial_statistics' => $financialStats,
                'recent_invoices' => $recentInvoices,
                'invoices_by_type' => $invoicesByType,
                'daily_trend' => $dailyTrend,
            ],
        ]);
    }

    /**
     * Get submission analytics
     * 
     * @group Dashboard
     */
    public function analytics(Request $request): JsonResponse
    {
        $userId = auth()->id();
        $dateFrom = $request->get('date_from', now()->subDays(30));
        $dateTo = $request->get('date_to', now());

        $analytics = SubmissionAnalytic::where('user_id', $userId)
            ->whereBetween('analytics_date', [$dateFrom, $dateTo])
            ->orderBy('analytics_date')
            ->get();

        // Calculate aggregates
        $totals = [
            'total_invoices' => $analytics->sum('total_invoices'),
            'successful_submissions' => $analytics->sum('successful_submissions'),
            'failed_submissions' => $analytics->sum('failed_submissions'),
            'total_value' => $analytics->sum('total_invoice_value'),
            'average_processing_time' => $analytics->avg('average_processing_time'),
            'success_rate' => $analytics->sum('total_invoices') > 0 
                ? ($analytics->sum('successful_submissions') / $analytics->sum('total_invoices')) * 100 
                : 0,
        ];

        return response()->json([
            'data' => [
                'analytics' => $analytics,
                'totals' => $totals,
            ],
        ]);
    }
}