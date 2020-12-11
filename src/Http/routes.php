<?php

use Ghost\Geetest\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::get("auth/register", Controllers\GeetestController::class."@register");
Route::get('auth/login', Controllers\GeetestController::class.'@getLogin');
Route::post('auth/login', Controllers\GeetestController::class.'@postLogin');