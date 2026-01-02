<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceLineItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'line_number' => $this->line_number,
            'classification_code' => $this->classification_code,
            'classification_code_name' => $this->classification_code_name,
            'product_service_description' => $this->product_service_description,
            
            'quantity' => number_format($this->quantity, 4),
            'unit_of_measure' => $this->unit_of_measure,
            'unit_price' => number_format($this->unit_price, 2),
            'subtotal' => number_format($this->subtotal, 2),
            
            'discount' => [
                'rate' => $this->discount_rate,
                'amount' => number_format($this->discount_amount ?? 0, 2),
            ],
            
            'tax' => [
                'type' => $this->tax_type,
                'type_name' => $this->tax_type_name,
                'rate' => $this->tax_rate,
                'amount' => number_format($this->tax_amount, 2),
                'exemption_reason' => $this->tax_exemption_reason,
                'exempted_amount' => number_format($this->tax_exempted_amount ?? 0, 2),
            ],
            
            'charge_fee_amount' => number_format($this->charge_fee_amount ?? 0, 2),
            'country_of_origin' => $this->country_of_origin,
            'product_tariff_code' => $this->product_tariff_code,
            
            'totals' => [
                'excluding_tax' => number_format($this->total_excluding_tax_per_line, 2),
                'including_tax' => number_format($this->total_including_tax_per_line, 2),
            ],
            
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}