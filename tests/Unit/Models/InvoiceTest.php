<?php

namespace Tests\Unit\Models;

use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $invoice->user);
        $this->assertEquals($user->id, $invoice->user->id);
    }

    public function test_invoice_has_many_line_items(): void
    {
        $invoice = Invoice::factory()->create();
        InvoiceLineItem::factory()->count(3)->create(['invoice_id' => $invoice->id]);

        $this->assertCount(3, $invoice->lineItems);
    }

    public function test_can_generate_invoice_number(): void
    {
        $invoice = new Invoice(['invoice_type' => '01']);
        $invoiceNumber = $invoice->generateInvoiceNumber();

        $this->assertStringStartsWith('INV-', $invoiceNumber);
        $this->assertEquals(19, strlen($invoiceNumber)); // INV-YYYYMMDD-NNNNNN
    }

    public function test_calculate_totals(): void
    {
        $invoice = Invoice::factory()->create([
            'total_excluding_tax' => 0,
            'total_tax_amount' => 0,
            'total_including_tax' => 0,
        ]);

        InvoiceLineItem::factory()->create([
            'invoice_id' => $invoice->id,
            'quantity' => 1,
            'unit_price' => 1000,
            'subtotal' => 1000,
            'tax_rate' => 6,
            'tax_type' => '02',
        ]);

        $invoice->calculateTotals();

        $this->assertEquals(1000, $invoice->total_excluding_tax);
        $this->assertEquals(60, $invoice->total_tax_amount);
        $this->assertEquals(1060, $invoice->total_including_tax);
    }

    public function test_is_editable(): void
    {
        $draftInvoice = Invoice::factory()->create(['status' => 'draft']);
        $validatedInvoice = Invoice::factory()->create(['status' => 'validated']);

        $this->assertTrue($draftInvoice->isEditable());
        $this->assertFalse($validatedInvoice->isEditable());
    }

    public function test_can_submit(): void
    {
        $validatedInvoice = Invoice::factory()->create([
            'status' => 'validated',
            'myinvois_uid' => null,
        ]);

        $submittedInvoice = Invoice::factory()->create([
            'status' => 'validated',
            'myinvois_uid' => 'UID123456',
        ]);

        $this->assertTrue($validatedInvoice->canSubmit());
        $this->assertFalse($submittedInvoice->canSubmit());
    }

    public function test_can_cancel_within_72_hours(): void
    {
        $recentInvoice = Invoice::factory()->create([
            'status' => 'validated',
            'validation_date_time' => now()->subHours(24),
        ]);

        $oldInvoice = Invoice::factory()->create([
            'status' => 'validated',
            'validation_date_time' => now()->subHours(73),
        ]);

        $this->assertTrue($recentInvoice->canCancel());
        $this->assertFalse($oldInvoice->canCancel());
    }
}