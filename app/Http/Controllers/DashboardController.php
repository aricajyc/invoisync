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

        // Calculate invoice stats
        $stats = [
            'draft' => $user->invoices()->where('status', 'draft')->count(),
            'valid' => $user->invoices()->whereIn('status', ['validated', 'submitted', 'approved'])->count(),
            'invalid' => $user->invoices()->where('status', 'rejected')->count(),
            'cancelled' => $user->invoices()->where('status', 'cancelled')->count(),
            'failed' => 0, // Placeholder for now, maybe for API failures if tracked separately
        ];

        return Inertia::render('Dashboard', [
            'stats' => $stats,
        ]);
    }
}
