<?php

namespace App\Modules\Gamification\Jobs;

use App\Models\User;
use App\Modules\Gamification\Services\Contracts\XpServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessXpAward implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected User $user,
        protected int $amount,
        protected string $reason,
        protected ?int $examTypeId = null,
        protected ?int $referenceId = null
    ) {}

    public function handle(XpServiceInterface $xpService): void
    {
        $xpService->awardXp($this->user, $this->amount, $this->reason, $this->examTypeId, $this->referenceId);
    }
}
