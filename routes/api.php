<?php

use App\Http\Controllers\Api\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', LoginController::class)
    ->middleware('throttle:api-login');
