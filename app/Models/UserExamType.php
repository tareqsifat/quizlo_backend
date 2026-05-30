<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class UserExamType extends Pivot
{
    protected $table = 'user_exam_types';
    
    public $incrementing = true;
    
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'exam_type_id',
        'is_primary',
        'target_year',
        'enrolled_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'exam_type_id' => 'integer',
        'is_primary' => 'boolean',
        'target_year' => 'integer',
        'enrolled_at' => 'datetime',
    ];
}
