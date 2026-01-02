<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidClassificationCode implements ValidationRule
{
    protected array $validCodes = [
        '001' => 'Normal Product/Service',
        '002' => 'Product/Service subject to specific tax treatment',
        '003' => 'SST Exempt',
        '004' => 'Zero-rated',
        '005' => 'Not subject to SST',
        '006' => 'Tourism Tax',
        '007' => 'Service Tax on Digital',
        '008' => 'Others',
    ];

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Basic range check: 001-999999
        if (!preg_match('/^[0-9]{3,6}$/', $value)) {
            $fail('The :attribute must be a 3-6 digit classification code.');
            return;
        }

        // Check against known valid codes (optional)
        $codePrefix = substr($value, 0, 3);
        if (!isset($this->validCodes[$codePrefix])) {
            $fail('The :attribute does not match any known classification code.');
        }
    }
}
