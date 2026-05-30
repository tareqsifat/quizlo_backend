<?php

namespace App\Modules\Gamification\Services\Contracts;

use App\Models\User;

interface HeartServiceInterface
{
    public function deductHeart(User $user, int $amount = 1): array;
    
    public function refillHearts(User $user): array;
    
    public function getHeartsStatus(User $user): array;
    
    public function refillOverTime(): void;
}
