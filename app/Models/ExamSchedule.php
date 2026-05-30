<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamSchedule extends Model
{
    protected $table = 'exam_schedules';

    public $timestamps = true;
    const UPDATED_AT = null; // only created_at is in SQL

    protected $fillable = [
        'exam_type_id',
        'batch_label',
        'exam_stage',
        'scheduled_date',
        'is_confirmed',
        'note',
    ];

    protected $casts = [
        'exam_type_id' => 'integer',
        'scheduled_date' => 'date',
        'is_confirmed' => 'boolean',
    ];

    public function examType()
    {
        return $this->belongsTo(ExamType::class);
    }
}
