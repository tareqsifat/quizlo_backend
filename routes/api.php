<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // 1. Auth Module
    Route::prefix('auth')->group(base_path('app/Modules/Auth/Routes/api.php'));

    // 2. ExamType Module
    Route::prefix('exam-types')->group(base_path('app/Modules/ExamType/Routes/api.php'));

    // 3. User Module (Profile, Daily Goal, Enrollments, Social)
    Route::prefix('user')->group(base_path('app/Modules/User/Routes/api.php'));

    // 4. Content Module (Subjects, Lessons, Questions)
    Route::group([], base_path('app/Modules/Content/Routes/api.php'));

    // 5. Gamification Module (XP, Streak, Hearts, Coins, Dashboard, Progress, Achievements)
    Route::group([], base_path('app/Modules/Gamification/Routes/api.php'));

    // 6. League Module
    Route::prefix('league')->group(base_path('app/Modules/League/Routes/api.php'));

    // 7. Exam Module (Model Tests, Sessions, Countdown)
    Route::group([], base_path('app/Modules/Exam/Routes/api.php'));

    // 8. Admin Module
    Route::prefix('admin')->group(base_path('app/Modules/Admin/Routes/api.php'));
});
