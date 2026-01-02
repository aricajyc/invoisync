<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MyInvoisTinFormat implements ValidationRule
{
    protected array $validPrefixes = [
        'C', // Company
        'IG', // Individual
        'EI', // General identifiers (e.g., EI00000000010 for general public)
    ];
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // TIN must be exactly 20 characters
        if (strlen($value) !== 20) {
            $fail('The :attribute must be exactly 20 characters.');
            return;
        }

        // Check prefix
        $prefix = substr($value, 0, 2);
        if (!in_array($prefix, $this->validPrefixes) && !in_array($value[0], ['C', 'I'])) {
            $fail('The :attribute must start with a valid prefix (C for company, IG for individual).');
            return;
        }

        // Rest must be digits
        $rest = substr($value, strlen($prefix));
        if (!ctype_digit($rest)) {
            $fail('The :attribute must contain only digits after the prefix.');
        }
    }
}
