<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Auth
use App\Modules\Auth\Services\Contracts\AuthServiceInterface;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Repositories\Contracts\AuthRepositoryInterface;
use App\Modules\Auth\Repositories\AuthRepository;

// ExamType
use App\Modules\ExamType\Services\Contracts\ExamTypeServiceInterface;
use App\Modules\ExamType\Services\ExamTypeService;
use App\Modules\ExamType\Repositories\Contracts\ExamTypeRepositoryInterface;
use App\Modules\ExamType\Repositories\ExamTypeRepository;

// User
use App\Modules\User\Services\Contracts\UserServiceInterface;
use App\Modules\User\Services\UserService;
use App\Modules\User\Repositories\Contracts\UserRepositoryInterface;
use App\Modules\User\Repositories\UserRepository;

// Content
use App\Modules\Content\Services\Contracts\SubjectServiceInterface;
use App\Modules\Content\Services\SubjectService;
use App\Modules\Content\Repositories\Contracts\SubjectRepositoryInterface;
use App\Modules\Content\Repositories\SubjectRepository;
use App\Modules\Content\Services\Contracts\LessonServiceInterface;
use App\Modules\Content\Services\LessonService;
use App\Modules\Content\Repositories\Contracts\LessonRepositoryInterface;
use App\Modules\Content\Repositories\LessonRepository;
use App\Modules\Content\Services\Contracts\QuestionServiceInterface;
use App\Modules\Content\Services\QuestionService;
use App\Modules\Content\Repositories\Contracts\QuestionRepositoryInterface;
use App\Modules\Content\Repositories\QuestionRepository;

// Gamification
use App\Modules\Gamification\Services\Contracts\XpServiceInterface;
use App\Modules\Gamification\Services\XpService;
use App\Modules\Gamification\Repositories\Contracts\XpRepositoryInterface;
use App\Modules\Gamification\Repositories\XpRepository;
use App\Modules\Gamification\Services\Contracts\StreakServiceInterface;
use App\Modules\Gamification\Services\StreakService;
use App\Modules\Gamification\Repositories\Contracts\StreakRepositoryInterface;
use App\Modules\Gamification\Repositories\StreakRepository;
use App\Modules\Gamification\Services\Contracts\HeartServiceInterface;
use App\Modules\Gamification\Services\HeartService;
use App\Modules\Gamification\Repositories\Contracts\HeartRepositoryInterface;
use App\Modules\Gamification\Repositories\HeartRepository;
use App\Modules\Gamification\Services\Contracts\CoinServiceInterface;
use App\Modules\Gamification\Services\CoinService;
use App\Modules\Gamification\Repositories\Contracts\CoinRepositoryInterface;
use App\Modules\Gamification\Repositories\CoinRepository;
use App\Modules\Gamification\Services\Contracts\MasteryServiceInterface;
use App\Modules\Gamification\Services\MasteryService;
use App\Modules\Gamification\Repositories\Contracts\MasteryRepositoryInterface;
use App\Modules\Gamification\Repositories\MasteryRepository;

// League
use App\Modules\League\Services\Contracts\LeagueServiceInterface;
use App\Modules\League\Services\LeagueService;
use App\Modules\League\Repositories\Contracts\LeagueRepositoryInterface;
use App\Modules\League\Repositories\LeagueRepository;

// Exam
use App\Modules\Exam\Services\Contracts\ExamServiceInterface;
use App\Modules\Exam\Services\ExamService;
use App\Modules\Exam\Repositories\Contracts\ExamRepositoryInterface;
use App\Modules\Exam\Repositories\ExamRepository;

// Notification
use App\Modules\Notification\Services\Contracts\NotificationServiceInterface;
use App\Modules\Notification\Services\PushNotificationService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ── Auth ──────────────────────────────────────────
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);

        // ── ExamType ──────────────────────────────────────
        $this->app->bind(ExamTypeServiceInterface::class, ExamTypeService::class);
        $this->app->bind(ExamTypeRepositoryInterface::class, ExamTypeRepository::class);

        // ── User ──────────────────────────────────────────
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);

        // ── Content ───────────────────────────────────────
        $this->app->bind(SubjectServiceInterface::class, SubjectService::class);
        $this->app->bind(SubjectRepositoryInterface::class, SubjectRepository::class);
        $this->app->bind(LessonServiceInterface::class, LessonService::class);
        $this->app->bind(LessonRepositoryInterface::class, LessonRepository::class);
        $this->app->bind(QuestionServiceInterface::class, QuestionService::class);
        $this->app->bind(QuestionRepositoryInterface::class, QuestionRepository::class);

        // ── Gamification ──────────────────────────────────
        $this->app->bind(XpServiceInterface::class, XpService::class);
        $this->app->bind(XpRepositoryInterface::class, XpRepository::class);
        $this->app->bind(StreakServiceInterface::class, StreakService::class);
        $this->app->bind(StreakRepositoryInterface::class, StreakRepository::class);
        $this->app->bind(HeartServiceInterface::class, HeartService::class);
        $this->app->bind(HeartRepositoryInterface::class, HeartRepository::class);
        $this->app->bind(CoinServiceInterface::class, CoinService::class);
        $this->app->bind(CoinRepositoryInterface::class, CoinRepository::class);
        $this->app->bind(MasteryServiceInterface::class, MasteryService::class);
        $this->app->bind(MasteryRepositoryInterface::class, MasteryRepository::class);

        // ── League ────────────────────────────────────────
        $this->app->bind(LeagueServiceInterface::class, LeagueService::class);
        $this->app->bind(LeagueRepositoryInterface::class, LeagueRepository::class);

        // ── Exam ──────────────────────────────────────────
        $this->app->bind(ExamServiceInterface::class, ExamService::class);
        $this->app->bind(ExamRepositoryInterface::class, ExamRepository::class);

        // ── Notification ──────────────────────────────────
        $this->app->bind(NotificationServiceInterface::class, PushNotificationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Laravel\Passport\Passport::tokensCan([
            'user'         => 'Standard user access',
            'admin'        => 'Admin panel access',
            'read-content' => 'Read-only public content',
        ]);

        \Laravel\Passport\Passport::enablePasswordGrant();

        \Laravel\Passport\Passport::setDefaultScope(['user']);

        \Laravel\Passport\Passport::tokensExpireIn(now()->addDays(15));
        \Laravel\Passport\Passport::refreshTokensExpireIn(now()->addDays(30));
        \Laravel\Passport\Passport::personalAccessTokensExpireIn(now()->addMonths(6));

        \App\Models\User::observe(\App\Observers\UserObserver::class);

        // Event-Listener bindings
        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Gamification\Events\QuestionAnswered::class,
            \App\Modules\Gamification\Listeners\AwardXpOnAnswer::class
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Gamification\Events\QuestionAnswered::class,
            \App\Modules\Gamification\Listeners\UpdateStreakOnAnswer::class
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Gamification\Events\QuestionAnswered::class,
            \App\Modules\Gamification\Listeners\DeductHeartOnWrongAnswer::class
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Gamification\Events\QuestionAnswered::class,
            \App\Modules\Gamification\Listeners\UpdateSubjectMastery::class
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Gamification\Events\QuestionAnswered::class,
            \App\Modules\Gamification\Listeners\UpdateDailyProgress::class
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Gamification\Events\QuestionAnswered::class,
            \App\Modules\Gamification\Listeners\UpdateLeagueXp::class
        );

        \Illuminate\Support\Facades\Event::listen(
            \App\Modules\Gamification\Events\LessonCompleted::class,
            \App\Modules\Gamification\Listeners\AwardCoinOnLessonComplete::class
        );
    }
}
