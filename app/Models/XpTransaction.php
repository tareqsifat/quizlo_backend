<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class XpTransaction extends Model
{
    protected $table = 'xp_transactions';

    public $timestamps = true;
    const UPDATED_AT = null;
    const CREATED_AT = 'earned_at';

    protected $fillable = [
        'user_id',
        'exam_type_id',
        'amount',
        'reason',
        'reference_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'exam_type_id' => 'integer',
        'amount' => 'integer',
        'reference_id' => 'integer',
        'earned_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function examType()
    {
        return $this->belongsTo(ExamType::class);
    }
}
