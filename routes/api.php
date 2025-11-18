<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PixController;
use App\Http\Controllers\WithdrawController;
use App\Http\Controllers\WebhookController;

Route::post('pix', [PixController::class, 'store']);
Route::post('withdraw', [WithdrawController::class, 'store']);
Route::post('webhook/receive', [WebhookController::class, 'receive']);
