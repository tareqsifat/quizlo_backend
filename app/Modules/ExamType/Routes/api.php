<?php

use Illuminate\Support\Facades\Route;
use App\Modules\ExamType\Controllers\ExamTypeController;

Route::get('/', [ExamTypeController::class, 'index']);
