<?php

namespace App\Queue;

use DateTimeInterface;

trait QueueRetryPolicy
{
    public int $tries = 3;

    public int $timeout = 60;

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addMinutes(30);
    }
}
