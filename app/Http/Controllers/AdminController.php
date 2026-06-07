<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Invoice;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function index()
    {
        $totalUsers = User::count();
        $totalInvoices = Invoice::count();
        $recentUsers = User::latest()->take(5)->get();
        $recentInvoices = Invoice::with('user')->latest()->take(5)->get();

        return Inertia::render('Admin/Dashboard', [
            'totalUsers' => $totalUsers,
            'totalInvoices' => $totalInvoices,
            'recentUsers' => $recentUsers,
            'recentInvoices' => $recentInvoices,
        ]);
    }

    public function users()
    {
        $users = User::latest()->paginate(15);
        return Inertia::render('Admin/Users', [
            'users' => $users,
        ]);
    }

    public function invoices()
    {
        $invoices = Invoice::with('user')->latest()->paginate(15);
        return Inertia::render('Admin/Invoices', [
            'invoices' => $invoices,
        ]);
    }
}
