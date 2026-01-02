<?php

namespace Tests\Feature\Api;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvoiceApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_can_list_invoices(): void
    {
        Invoice::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/invoices');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_invoice(): void
    {
        $data = [
            'invoice_type' => '01',
            'invoice_date_time' => now()->toIso8601String(),
            'supplier_name' => 'Test Supplier',
            'supplier_tin' => 'C1234567890123456789',
            'supplier_registration_number' => '202301000001',
            'supplier_email' => 'supplier@test.com',
            'supplier_msic_code' => '12345',
            'supplier_business_activity_description' => 'Testing',
            'supplier_address_line1' => '123 Test St',
            'supplier_state' => 'Test State',
            'supplier_country' => 'MY',
            'supplier_contact_number' => '+60123456789',
            'buyer_name' => 'Test Buyer',
            'buyer_tin' => 'EI00000000010',
            'buyer_address_line1' => '456 Test Ave',
            'buyer_state' => 'Test State',
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

        $response = $this->postJson('/api/invoices', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.supplier.name', 'Test Supplier');
    }

    public function test_can_view_invoice(): void
    {
        $invoice = Invoice::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson("/api/invoices/{$invoice->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $invoice->id);
    }

    public function test_cannot_view_other_users_invoice(): void
    {
        $otherUser = User::factory()->create();
        $invoice = Invoice::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson("/api/invoices/{$invoice->id}");

        $response->assertStatus(403);
    }

    public function test_can_update_draft_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        $response = $this->putJson("/api/invoices/{$invoice->id}", [
            'buyer_name' => 'Updated Buyer',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('Updated Buyer', $invoice->fresh()->buyer_name);
    }

    public function test_cannot_update_validated_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'validated',
        ]);

        $response = $this->putJson("/api/invoices/{$invoice->id}", [
            'buyer_name' => 'Updated Buyer',
        ]);

        $response->assertStatus(422);
    }

    public function test_can_delete_draft_invoice(): void
    {
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);

        $response = $this->deleteJson("/api/invoices/{$invoice->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted($invoice);
    }

    public function test_can_duplicate_invoice(): void
    {
        $invoice = Invoice::factory()
            ->hasLineItems(2)
            ->create(['user_id' => $this->user->id]);

        $response = $this->postJson("/api/invoices/{$invoice->id}/duplicate");

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'draft');
    }
}