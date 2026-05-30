<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeagueSeason extends Model
{
    use HasFactory;
    protected $table = 'league_seasons';

    public $timestamps = false;

    protected $fillable = [
        'exam_type_id',
        'week_number',
        'year',
        'starts_at',
        'ends_at',
        'is_active',
        'processed',
    ];

    protected $casts = [
        'exam_type_id' => 'integer',
        'week_number' => 'integer',
        'year' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'processed' => 'boolean',
    ];

    public function examType()
    {
        return $this->belongsTo(ExamType::class);
    }

    public function userLeagues()
    {
        return $this->hasMany(UserLeague::class, 'league_season_id');
    }
}
