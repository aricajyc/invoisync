<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceService
{
    /**
     * Create a new invoice with line items
     */
    public function createInvoice(array $data, User $user): Invoice
    {
        DB::beginTransaction();
        
        try {
            // Extract line items
            $lineItems = $data['line_items'] ?? [];
            unset($data['line_items']);
            
            // Set user ID
            $data['user_id'] = $user->id;
            
            // Generate invoice number if not provided
            if (empty($data['invoice_number'])) {
                $data['invoice_number'] = $this->generateInvoiceNumber($data['invoice_type']);
            }
            
            // Set default buyer TIN if not provided
            if (empty($data['buyer_tin'])) {
                $data['buyer_tin'] = 'EI00000000010'; // General public TIN
            }
            
            // Set default currency
            if (empty($data['currency_code'])) {
                $data['currency_code'] = 'MYR';
            }
            
            // Initialize totals
            $data['total_excluding_tax'] = 0;
            $data['total_including_tax'] = 0;
            $data['total_payable_amount'] = 0;
            $data['total_discount_value'] = 0;
            $data['total_fee_charge_amount'] = 0;
            $data['total_tax_amount'] = 0;
            
            // Create invoice
            $invoice = \App\Models\Invoice::create($data);
            
            // Create line items
            foreach ($lineItems as $lineItemData) {
                $invoice->lineItems()->create($lineItemData);
            }
            
            // Calculate totals
            $this->recalculateTotals($invoice);
            
            // Generate tax summaries
            $this->generateTaxSummaries($invoice);
            
            DB::commit();
            
            return $invoice->fresh(['lineItems', 'taxSummaries']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Update existing invoice
     */
    public function updateInvoice(Invoice $invoice, array $data): Invoice
    {
        DB::beginTransaction();
        
        try {
            // Extract line items if provided
            if (isset($data['line_items'])) {
                $lineItems = $data['line_items'];
                unset($data['line_items']);
                
                // Delete existing line items
                $invoice->lineItems()->delete();
                
                // Create new line items
                foreach ($lineItems as $lineItemData) {
                    $invoice->lineItems()->create($lineItemData);
                }
            }
            
            // Update invoice
            $invoice->update($data);
            
            // Recalculate if line items changed
            if (isset($lineItems)) {
                $this->recalculateTotals($invoice);
                $this->generateTaxSummaries($invoice);
            }
            
            DB::commit();
            
            return $invoice->fresh(['lineItems', 'taxSummaries']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Duplicate an existing invoice
     */
    public function duplicateInvoice(Invoice $invoice): Invoice
    {
        DB::beginTransaction();
        
        try {
            // Clone invoice
            $newInvoice = $invoice->replicate();
            $newInvoice->invoice_number = $this->generateInvoiceNumber($invoice->invoice_type);
            $newInvoice->status = 'draft';
            $newInvoice->myinvois_uid = null;
            $newInvoice->qr_code_data = null;
            $newInvoice->irbm_unique_identifier = null;
            $newInvoice->validation_date_time = null;
            $newInvoice->submitted_at = null;
            $newInvoice->invoice_date_time = now();
            $newInvoice->save();
            
            // Clone line items
            foreach ($invoice->lineItems as $lineItem) {
                $newLineItem = $lineItem->replicate();
                $newLineItem->invoice_id = $newInvoice->id;
                $newLineItem->save();
            }
            
            // Clone tax summaries
            foreach ($invoice->taxSummaries as $taxSummary) {
                $newTaxSummary = $taxSummary->replicate();
                $newTaxSummary->invoice_id = $newInvoice->id;
                $newTaxSummary->save();
            }
            
            DB::commit();
            
            return $newInvoice->fresh(['lineItems', 'taxSummaries']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Recalculate invoice totals based on line items
     */
    public function recalculateTotals(Invoice $invoice): void
    {
        $totalExcludingTax = 0;
        $totalTax = 0;
        
        foreach ($invoice->lineItems as $lineItem) {
            $lineItem->calculateTotals();
            $lineItem->save();
            
            $totalExcludingTax += $lineItem->total_excluding_tax_per_line;
            $totalTax += $lineItem->tax_amount;
        }
        
        $invoice->total_excluding_tax = $totalExcludingTax;
        $invoice->total_tax_amount = $totalTax;
        $invoice->total_including_tax = $totalExcludingTax + $totalTax;
        $invoice->total_payable_amount = $invoice->total_including_tax 
            - ($invoice->total_discount_value ?? 0) 
            + ($invoice->total_fee_charge_amount ?? 0);
        
        $invoice->save();
    }
    
    /**
     * Generate tax summaries grouped by tax type
     */
    public function generateTaxSummaries(Invoice $invoice): void
    {
        // Delete existing tax summaries
        $invoice->taxSummaries()->delete();
        
        // Group line items by tax type
        $taxGroups = $invoice->lineItems->groupBy('tax_type');
        
        foreach ($taxGroups as $taxType => $items) {
            $taxableAmount = $items->sum('total_excluding_tax_per_line');
            $taxAmount = $items->sum('tax_amount');
            $taxExemptedAmount = $items->sum('tax_exempted_amount');
            $taxRate = $items->first()->tax_rate; // Assumes same rate for same type
            
            $invoice->taxSummaries()->create([
                'tax_type' => $taxType,
                'taxable_amount' => $taxableAmount,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'tax_exempted_amount' => $taxExemptedAmount,
            ]);
        }
    }
    
    /**
     * Generate invoice number
     */
    protected function generateInvoiceNumber(string $invoiceType): string
    {
        $prefix = match($invoiceType) {
            '01' => 'INV',
            '02' => 'CN',
            '03' => 'DN',
            '04' => 'RN',
            default => 'INV',
        };
        
        $date = now()->format('Ymd');
        
        $lastInvoice = Invoice::whereDate('created_at', now()->toDateString())
            ->where('invoice_type', $invoiceType)
            ->latest()
            ->first();
        
        $sequence = $lastInvoice 
            ? (intval(substr($lastInvoice->invoice_number, -6)) + 1) 
            : 1;
        
        return sprintf('%s-%s-%06d', $prefix, $date, $sequence);
    }
    
    /**
     * Generate PDF for invoice
     */
    public function generatePdf(Invoice $invoice)
    {
        $invoice->load(['lineItems', 'taxSummaries']);
        
        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
        ]);
        
        return $pdf->output();
    }
}