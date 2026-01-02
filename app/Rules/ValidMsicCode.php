<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Cache;

class ValidMsicCode implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check if it's 5 digits
        if (!preg_match('/^[0-9]{5}$/', $value)) {
            $fail('The :attribute must be a 5-digit MSIC code.');
            return;
        }

        // Optionally: Validate against actual MSIC code list
        $validMsicCodes = Cache::remember('valid_msic_codes', 86400, function () {
            // Load from database or config
            return config('myinvois.valid_msic_codes', []);
        });

        if (!empty($validMsicCodes) && !in_array($value, $validMsicCodes)) {
            $fail('The :attribute is not a valid MSIC code.');
        }
    }
}
