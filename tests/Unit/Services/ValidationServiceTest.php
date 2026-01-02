<?php

namespace Tests\Unit\Services;

use App\Models\Invoice;
use App\Models\ValidationRule;
use App\Services\ValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ValidationService $validationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validationService = app(ValidationService::class);
    }

    public function test_validate_invoice(): void
    {
        // Create validation rules
        ValidationRule::factory()->create([
            'rule_code' => 'VR001',
            'rule_type' => 'mandatory_field',
            'is_active' => true,
        ]);

        $invoice = Invoice::factory()->create([
            'supplier_tin' => 'C1234567890123456789',
        ]);

        $results = $this->validationService->validateInvoice($invoice, 'basic');

        $this->assertGreaterThan(0, $results->count());
        $this->assertTrue($results->contains('result_type', 'pass'));
    }

    public function test_validation_creates_results(): void
    {
        ValidationRule::factory()->create([
            'rule_code' => 'VR001',
            'rule_type' => 'mandatory_field',
            'is_active' => true,
        ]);

        $invoice = Invoice::factory()->create();

        $this->validationService->validateInvoice($invoice);

        $this->assertDatabaseHas('validation_results', [
            'invoice_id' => $invoice->id,
        ]);
    }
}