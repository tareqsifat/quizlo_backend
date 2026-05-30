<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'avatar',
        'district',
        'division',
        'daily_goal',
        'first_session_completed',
        'is_active',
    ];

    protected $casts = [
        'first_session_completed' => 'boolean',
        'is_active' => 'boolean',
        'is_admin' => 'boolean',
        'daily_goal' => 'integer',
    ];

    // Relations
    public function examTypes()
    {
        return $this->belongsToMany(ExamType::class, 'user_exam_types')
                    ->withPivot('is_primary', 'target_year', 'enrolled_at');
    }

    public function answers()
    {
        return $this->hasMany(UserAnswer::class);
    }

    public function masteries()
    {
        return $this->hasMany(UserSubjectMastery::class);
    }

    public function dailyProgresses()
    {
        return $this->hasMany(UserDailyProgress::class);
    }

    public function lessonCompletions()
    {
        return $this->hasMany(UserLessonCompletion::class);
    }

    public function xp()
    {
        return $this->hasOne(UserXp::class);
    }

    public function xpTransactions()
    {
        return $this->hasMany(XpTransaction::class);
    }

    public function streak()
    {
        return $this->hasOne(UserStreak::class);
    }

    public function heart()
    {
        return $this->hasOne(UserHeart::class);
    }

    public function coin()
    {
        return $this->hasOne(UserCoin::class);
    }

    public function coinTransactions()
    {
        return $this->hasMany(CoinTransaction::class);
    }

    public function achievements()
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
                    ->withPivot('earned_at');
    }

    public function leagues()
    {
        return $this->hasMany(UserLeague::class);
    }

    public function findForPassport($username)
    {
        return $this->where('phone', $username)->first();
    }

    public function validateForPassportPasswordGrant($password)
    {
        return true;
    }
}
