<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tax_type' => $this->tax_type,
            'tax_type_name' => $this->tax_type_name,
            'taxable_amount' => number_format($this->taxable_amount, 2),
            'tax_rate' => $this->tax_rate,
            'tax_amount' => number_format($this->tax_amount, 2),
            'tax_exempted_amount' => number_format($this->tax_exempted_amount ?? 0, 2),
        ];
    }
}