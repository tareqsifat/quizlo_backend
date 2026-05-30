<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ExamTypeSubject extends Pivot
{
    protected $table = 'exam_type_subject';
    
    public $incrementing = true;
    
    public $timestamps = false;

    protected $fillable = [
        'exam_type_id',
        'subject_id',
        'is_active',
        'sort_order',
        'total_marks',
        'syllabus_note',
    ];

    protected $casts = [
        'exam_type_id' => 'integer',
        'subject_id' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'total_marks' => 'integer',
    ];
}
