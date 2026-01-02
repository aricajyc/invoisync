<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'invoice_number' => 'INV-' . now()->format('Ymd') . '-' . rand(100000, 999999),
            'invoice_type' => '01',
            'invoice_date_time' => now(),
            'supplier_name' => fake()->company(),
            'supplier_tin' => 'C' . fake()->numerify('###################'),
            'supplier_registration_number' => fake()->numerify('########'),
            'supplier_email' => fake()->companyEmail(),
            'supplier_msic_code' => fake()->numerify('#####'),
            'supplier_business_activity_description' => fake()->bs(),
            'supplier_address_line1' => fake()->streetAddress(),
            'supplier_city' => fake()->city(),
            'supplier_state' => fake()->state(),
            'supplier_country' => 'MY',
            'supplier_contact_number' => fake()->phoneNumber(),
            'buyer_name' => fake()->company(),
            'buyer_tin' => 'EI00000000010',
            'buyer_address_line1' => fake()->streetAddress(),
            'buyer_city' => fake()->city(),
            'buyer_state' => fake()->state(),
            'buyer_country' => 'MY',
            'currency_code' => 'MYR',
            'total_excluding_tax' => 1000,
            'total_tax_amount' => 60,
            'total_including_tax' => 1060,
            'total_payable_amount' => 1060,
            'status' => 'draft',
        ];
    }
}