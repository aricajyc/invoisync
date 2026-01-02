<?php

namespace Tests\Unit\Services;

use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InvoiceService $invoiceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->invoiceService = app(InvoiceService::class);
    }

    public function test_create_invoice_with_line_items(): void
    {
        $user = User::factory()->create();
        $data = [
            'invoice_number' => 'TEST-SERVICE-01',
            'invoice_type' => '01',
            'invoice_date_time' => now(),
            'supplier_name' => 'Service Supplier',
            'supplier_tin' => 'C1234567890123456789',
            'supplier_registration_number' => '123',
            'supplier_email' => 'service@test.com',
            'supplier_msic_code' => '12345',
            'supplier_business_activity_description' => 'Service',
            'supplier_address_line1' => '123 Service St',
            'supplier_state' => 'Service State',
            'supplier_country' => 'MY',
            'supplier_contact_number' => '+60123456789',
            'buyer_name' => 'Service Buyer',
            'buyer_tin' => 'EI00000000010',
            'buyer_address_line1' => '456 Service Ave',
            'buyer_state' => 'Service State',
            'buyer_country' => 'MY',
            'currency_code' => 'MYR',
            'line_items' => [
                [
                    'classification_code' => '001',
                    'product_service_description' => 'Test Product',
                    'quantity' => 1,
                    'unit_of_measure' => 'C62',
                    'unit_price' => 1000,
                    'subtotal' => 1000,
                    'tax_type' => '02',
                    'tax_rate' => 6,
                ],
            ],
        ];

        $invoice = $this->invoiceService->createInvoice($data, $user);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals($user->id, $invoice->user_id);
        $this->assertCount(1, $invoice->lineItems);
        $this->assertEquals(1000, $invoice->total_excluding_tax);
    }

    public function test_duplicate_invoice(): void
    {
        $original = Invoice::factory()
            ->hasLineItems(2)
            ->create();

        $duplicate = $this->invoiceService->duplicateInvoice($original);

        $this->assertNotEquals($original->id, $duplicate->id);
        $this->assertNotEquals($original->invoice_number, $duplicate->invoice_number);
        $this->assertEquals('draft', $duplicate->status);
        $this->assertCount(2, $duplicate->lineItems);
    }

    public function test_recalculate_totals(): void
    {
        $invoice = Invoice::factory()
            ->hasLineItems(2, [
                'quantity' => 1,
                'unit_price' => 1000,
                'subtotal' => 1000,
                'tax_rate' => 6,
            ])
            ->create([
                'total_excluding_tax' => 0,
                'total_tax_amount' => 0,
            ]);

        $this->invoiceService->recalculateTotals($invoice);

        $invoice->refresh();
        $this->assertEquals(2000, $invoice->total_excluding_tax);
        $this->assertEquals(120, $invoice->total_tax_amount);
    }
}