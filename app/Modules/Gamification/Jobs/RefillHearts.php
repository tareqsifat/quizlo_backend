<?php

namespace App\Modules\Gamification\Jobs;

use App\Modules\Gamification\Services\Contracts\HeartServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefillHearts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(HeartServiceInterface $heartService): void
    {
        $heartService->refillOverTime();
    }
}
