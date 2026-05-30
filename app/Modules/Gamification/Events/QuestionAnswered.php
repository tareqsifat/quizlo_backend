<?php

namespace App\Modules\Gamification\Events;

use App\Models\UserAnswer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuestionAnswered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly UserAnswer $userAnswer
    ) {}
}
