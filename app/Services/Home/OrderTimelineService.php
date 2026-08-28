<?php

namespace App\Services\Home;

use App\Models\Orders;
use App\Models\OrderStatusLog;

class OrderTimelineService
{
    public function log(Orders $order, string $toStatus, ?string $fromStatus = null, ?int $changedBy = null, ?string $note = null): void
    {
        OrderStatusLog::create([
            'order_id' => $order->id,
            'changed_by' => $changedBy,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'note' => $note,
        ]);
    }
}
