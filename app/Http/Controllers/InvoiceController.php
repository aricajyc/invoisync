<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $invoices = Invoice::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Invoices/Create', [
            'businessProfile' => $request->user()->businessProfile,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(\App\Http\Requests\StoreInvoiceRequest $request, \App\Services\InvoiceService $invoiceService)
    {
        try {
            $invoice = $invoiceService->createInvoice(
                $request->validated(),
                $request->user()
            );

            return redirect()->route('invoices.index')
                ->with('status', 'Invoice created successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to create invoice: ' . $e->getMessage()]);
        }
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Invoice $invoice): Response
    {
        // Ensure user owns the invoice
        if ($invoice->user_id !== $request->user()->id) {
            abort(403);
        }

        $invoice->load(['lineItems']);

        return Inertia::render('Invoices/Create', [
            'businessProfile' => $request->user()->businessProfile,
            'invoice' => $invoice,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(\App\Http\Requests\StoreInvoiceRequest $request, Invoice $invoice, \App\Services\InvoiceService $invoiceService)
    {
        // Ensure user owns the invoice
        if ($invoice->user_id !== $request->user()->id) {
            abort(403);
        }

        try {
            $invoiceService->updateInvoice(
                $invoice,
                $request->validated()
            );

            return redirect()->route('invoices.index')
                ->with('status', 'Invoice updated successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update invoice: ' . $e->getMessage()]);
        }
    }
}