<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Achievement extends Model
{
    use HasFactory;
    protected $table = 'achievements';

    public $timestamps = true;
    const UPDATED_AT = null; // only created_at is in SQL

    protected $fillable = [
        'key',
        'title',
        'title_bn',
        'description',
        'icon',
        'xp_reward',
        'type',
        'exam_type_id',
    ];

    protected $casts = [
        'xp_reward' => 'integer',
        'exam_type_id' => 'integer',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_achievements')
                    ->withPivot('earned_at');
    }

    public function examType()
    {
        return $this->belongsTo(ExamType::class);
    }
}
