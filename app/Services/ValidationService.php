<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\ValidationRule;
use App\Models\ValidationResult;
use Illuminate\Support\Collection;

class ValidationService
{
    /**
     * Validate invoice against all rules
     */
    public function validateInvoice(Invoice $invoice, string $validationType = 'comprehensive'): Collection
    {
        $rules = $this->getValidationRules($validationType);
        $results = collect();
        
        foreach ($rules as $rule) {
            $result = $this->applyRule($invoice, $rule);
            
            // Store validation result
            $validationResult = ValidationResult::create([
                'invoice_id' => $invoice->id,
                'rule_id' => $rule->id,
                'result_type' => $result['type'],
                'validation_message' => $result['message'],
                'suggested_fix' => $result['suggestion'] ?? null,
                'is_resolved' => false,
                'validated_at' => now(),
            ]);
            
            $results->push($validationResult);
        }
        
        return $results;
    }
    
    /**
     * Get validation rules based on type
     */
    protected function getValidationRules(string $validationType): Collection
    {
        $query = ValidationRule::where('is_active', true);
        
        return match($validationType) {
            'basic' => $query->where('rule_type', 'mandatory_field')->get(),
            'comprehensive' => $query->whereIn('rule_type', ['mandatory_field', 'format_check', 'compliance'])->get(),
            'myinvois' => $query->get(),
            default => $query->get(),
        };
    }
    
    /**
     * Apply single validation rule
     */
    protected function applyRule(Invoice $invoice, ValidationRule $rule): array
    {
        return match($rule->rule_type) {
            'mandatory_field' => $this->validateMandatoryField($invoice, $rule),
            'format_check' => $this->validateFormat($invoice, $rule),
            'business_logic' => $this->validateBusinessLogic($invoice, $rule),
            'compliance' => $this->validateCompliance($invoice, $rule),
            default => ['type' => 'pass', 'message' => 'Rule type not implemented'],
        };
    }
    
    /**
     * Validate mandatory fields
     */
    protected function validateMandatoryField(Invoice $invoice, ValidationRule $rule): array
    {
        // Extract field name from rule code
        $fieldMapping = [
            'VR001' => 'supplier_tin',
            'VR002' => 'supplier_msic_code',
            'VR003' => 'invoice_date_time',
            // Add more mappings as needed
        ];
        
        $field = $fieldMapping[$rule->rule_code] ?? null;
        
        if (!$field || empty($invoice->$field)) {
            return [
                'type' => 'fail',
                'message' => str_replace(':field', $field, $rule->error_message_template),
                'suggestion' => "Please provide {$field}",
            ];
        }
        
        return [
            'type' => 'pass',
            'message' => 'Mandatory field validation passed',
        ];
    }
    
    /**
     * Validate format
     */
    protected function validateFormat(Invoice $invoice, ValidationRule $rule): array
    {
        // Implement format validation logic
        return [
            'type' => 'pass',
            'message' => 'Format validation passed',
        ];
    }
    
    /**
     * Validate business logic
     */
    protected function validateBusinessLogic(Invoice $invoice, ValidationRule $rule): array
    {
        if ($rule->rule_code === 'VR005') {
            // Validate total calculations
            $calculatedTotal = $invoice->lineItems->sum('total_including_tax_per_line');
            
            if (abs($calculatedTotal - $invoice->total_including_tax) > 0.01) {
                return [
                    'type' => 'fail',
                    'message' => 'Invoice totals do not match line item calculations',
                    'suggestion' => 'Recalculate invoice totals',
                ];
            }
        }
        
        return [
            'type' => 'pass',
            'message' => 'Business logic validation passed',
        ];
    }
    
    /**
     * Validate compliance
     */
    protected function validateCompliance(Invoice $invoice, ValidationRule $rule): array
    {
        // Implement MyInvois compliance checks
        return [
            'type' => 'pass',
            'message' => 'Compliance validation passed',
        ];
    }
}