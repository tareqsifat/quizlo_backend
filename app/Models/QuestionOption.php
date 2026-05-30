<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QuestionOption extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'question_id',
        'option_text',
        'option_text_bn',
        'is_correct',
        'sort_order',
    ];

    protected $casts = [
        'question_id' => 'integer',
        'is_correct' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
