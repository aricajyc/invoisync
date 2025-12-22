<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MyInvoisReferenceDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Validation Rules
        $this->seedValidationRules();
        
        // You can add more reference data here
        // - Classification codes
        // - Tax types
        // - Unit of measure codes
        // - Country codes
    }

    private function seedValidationRules(): void
    {
        $rules = [
            [
                'rule_code' => 'VR001',
                'rule_name' => 'Supplier TIN Required',
                'rule_description' => 'Supplier Tax Identification Number must be provided',
                'rule_type' => 'mandatory_field',
                'validation_expression' => 'required|string|size:20',
                'error_message_template' => 'Supplier TIN is mandatory and must be 20 characters',
                'is_active' => true,
                'priority' => 100,
            ],
            [
                'rule_code' => 'VR002',
                'rule_name' => 'Supplier MSIC Code Required',
                'rule_description' => 'Supplier must have 5-digit MSIC code',
                'rule_type' => 'mandatory_field',
                'validation_expression' => 'required|string|size:5',
                'error_message_template' => 'Supplier MSIC code is mandatory (5 digits)',
                'is_active' => true,
                'priority' => 90,
            ],
            [
                'rule_code' => 'VR003',
                'rule_name' => 'Invoice Date Time Format',
                'rule_description' => 'Invoice must have date and time',
                'rule_type' => 'format_check',
                'validation_expression' => 'required|date_format:Y-m-d H:i:s',
                'error_message_template' => 'Invoice date must include time component',
                'is_active' => true,
                'priority' => 80,
            ],
            [
                'rule_code' => 'VR004',
                'rule_name' => 'Classification Code Valid Range',
                'rule_description' => 'Product classification must be 001-999999',
                'rule_type' => 'compliance',
                'validation_expression' => 'required|string|in:001,002,003,004,005,006,007,008',
                'error_message_template' => 'Classification code must be valid MyInvois code',
                'is_active' => true,
                'priority' => 85,
            ],
            [
                'rule_code' => 'VR005',
                'rule_name' => 'Total Calculation Accuracy',
                'rule_description' => 'Total amount must match line items sum',
                'rule_type' => 'business_logic',
                'validation_expression' => null,
                'error_message_template' => 'Invoice totals do not match line item calculations',
                'is_active' => true,
                'priority' => 95,
            ],
        ];
        
        foreach ($rules as $rule) {
            DB::table('validation_rules')->insert(array_merge($rule, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
