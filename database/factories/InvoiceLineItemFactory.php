<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InvoiceLineItem>
 */
class InvoiceLineItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'line_number' => $this->faker->numberBetween(1, 100),
            'classification_code' => '001',
            'product_service_description' => $this->faker->sentence(3),
            'quantity' => $this->faker->randomFloat(4, 1, 100),
            'unit_of_measure' => 'C62',
            'unit_price' => $this->faker->randomFloat(2, 10, 1000),
            'subtotal' => 0, // Calculated in configure() or manually
            'tax_type' => '01',
            'tax_rate' => 6.00,
            'tax_amount' => 0,
            'total_excluding_tax_per_line' => 0,
            'total_including_tax_per_line' => 0,
        ];
    }

    public function configure()
    {
        return $this->afterMaking(function (InvoiceLineItem $item) {
            $item->subtotal = $item->quantity * $item->unit_price;
            $item->tax_amount = $item->subtotal * ($item->tax_rate / 100);
            $item->total_excluding_tax_per_line = $item->subtotal;
            $item->total_including_tax_per_line = $item->subtotal + $item->tax_amount;
        })->afterCreating(function (InvoiceLineItem $item) {
             // ensure calculations are present if not set
            if ($item->subtotal == 0) {
                 $item->subtotal = $item->quantity * $item->unit_price;
            }
             if ($item->total_excluding_tax_per_line == 0) {
                 $item->total_excluding_tax_per_line = $item->subtotal;
             }
        });
    }
}
