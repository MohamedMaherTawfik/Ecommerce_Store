<?php

namespace App\Http\Resources\Home;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReturnRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'status' => $this->status,
            'admin_note' => $this->admin_note,
            'items' => $this->whenLoaded('items', fn () => $this->items),
            'order' => $this->whenLoaded('order', fn () => $this->order),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
