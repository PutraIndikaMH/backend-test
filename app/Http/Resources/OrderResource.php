<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'customer_name'  => $this->customer_name,
            'customer_email' => $this->customer_email,
            'status'         => $this->status,
            'total_price'    => $this->total_price,
            'items'          => OrderItemResource::collection($this->whenLoaded('orderItems')),
            'created_at'     => $this->created_at?->toISOString(),
            'updated_at'     => $this->updated_at?->toISOString(),
        ];
    }
}
