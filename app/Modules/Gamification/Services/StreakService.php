<?php

namespace App\Modules\Gamification\Services;

use App\Models\User;
use App\Modules\Gamification\Services\Contracts\StreakServiceInterface;
use App\Modules\Gamification\Repositories\Contracts\StreakRepositoryInterface;
use App\Modules\Gamification\Events\StreakMilestoneReached;
use Carbon\Carbon;

class StreakService implements StreakServiceInterface
{
    private const MILESTONES = [7, 14, 30, 60, 100, 365];

    public function __construct(
        private readonly StreakRepositoryInterface $streakRepository
    ) {}

    public function processUserActivity(User $user): array
    {
        $streak = $this->streakRepository->findByUser($user->id);
        $today  = now()->toDateString();
        
        $lastDateStr = null;
        if ($streak?->last_activity_date) {
            $lastDateStr = $streak->last_activity_date instanceof \DateTimeInterface
                ? $streak->last_activity_date->format('Y-m-d')
                : (string) $streak->last_activity_date;
        }

        if ($lastDateStr === $today) {
            return $this->buildResponse($streak);
        }

        $yesterday = Carbon::yesterday()->toDateString();

        if ($lastDateStr === $yesterday || $lastDateStr === null) {
            $streak = $this->streakRepository->incrementStreak($user->id);
            $this->checkMilestone($user, $streak->current_streak);
            return $this->buildResponse($streak, updated: true);
        }

        if ($this->canUseFreeze($streak)) {
            $this->streakRepository->useStreakFreeze($user->id);
            $streak = $this->streakRepository->incrementStreak($user->id);
            return $this->buildResponse($streak, updated: true, freezeUsed: true);
        }

        $this->streakRepository->resetStreak($user->id);
        $streak = $this->streakRepository->incrementStreak($user->id);
        return $this->buildResponse($streak, updated: true, wasReset: true);
    }

    public function getStreakStatus(User $user): array
    {
        $streak = $this->streakRepository->findByUser($user->id);
        return $this->buildResponse($streak);
    }

    public function useStreakFreeze(User $user): bool
    {
        return $this->streakRepository->useStreakFreeze($user->id);
    }

    public function addStreakFreeze(User $user, int $count = 1): void
    {
        $this->streakRepository->addStreakFreeze($user->id, $count);
    }

    private function canUseFreeze($streak): bool
    {
        if (!$streak || $streak->freeze_used_today) return false;
        if ($streak->streak_freeze_count < 1) return false;

        $lastDate = $streak->last_activity_date;
        if ($lastDate) {
            $lastCarbon = $lastDate instanceof \DateTimeInterface
                ? Carbon::instance($lastDate)
                : Carbon::parse($lastDate);
            $daysMissed = $lastCarbon->startOfDay()->diffInDays(now()->startOfDay());
        } else {
            $daysMissed = 999;
        }

        return (int) $daysMissed === 2;
    }

    private function checkMilestone(User $user, int $currentStreak): void
    {
        if (in_array($currentStreak, self::MILESTONES)) {
            event(new StreakMilestoneReached($user, $currentStreak));
        }
    }

    private function buildResponse(
        $streak,
        bool $updated = false,
        bool $freezeUsed = false,
        bool $wasReset = false
    ): array {
        return [
            'current_streak' => $streak?->current_streak ?? 0,
            'longest_streak' => $streak?->longest_streak ?? 0,
            'freeze_count'   => $streak?->streak_freeze_count ?? 0,
            'streak_updated' => $updated,
            'freeze_used'    => $freezeUsed,
            'was_reset'      => $wasReset,
        ];
    }
}
