<?php

return [

    'cache' => [
        'leaderboard_ttl'           => 300,     // 5 min
        'league_standings_ttl'      => 120,     // 2 min
        'user_streak_ttl'           => 60,      // 1 min
        'subject_list_ttl'          => 86400,   // 24 hours
        'exam_type_subjects_ttl'    => 86400,   // 24 hours (rarely changes)
        'user_stats_ttl'            => 300,     // 5 min
        'exam_countdown_ttl'        => 3600,    // 1 hour
        'user_mastery_ttl'          => 300,     // 5 min
    ],

    'gamification' => [
        'xp_per_correct_answer'     => 10,
        'xp_lesson_complete'        => 20,
        'xp_daily_goal_bonus'       => 50,
        'xp_streak_7_bonus'         => 100,
        'xp_streak_30_bonus'        => 300,
        'xp_league_promotion'       => 200,
        'coins_lesson_complete'     => 10,
        'coins_daily_goal'          => 25,
        'max_hearts'                => 5,
        'heart_refill_minutes'      => 30,
        'streak_grace_days'         => 1,
    ],

    'league' => [
        'group_size'                => 30,
        'promotion_spots'           => 10,
        'relegation_spots'          => 5,
        'reset_day'                 => 0,       // Sunday
        'reset_time'                => '00:00',
    ],

];
