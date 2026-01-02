<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ValidationResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rule' => [
                'code' => $this->rule->rule_code ?? null,
                'name' => $this->rule->rule_name ?? null,
                'type' => $this->rule->rule_type ?? null,
            ],
            'result_type' => $this->result_type,
            'validation_message' => $this->validation_message,
            'suggested_fix' => $this->suggested_fix,
            'is_resolved' => $this->is_resolved,
            'validated_at' => $this->validated_at->toIso8601String(),
        ];
    }
}