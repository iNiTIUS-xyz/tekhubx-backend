<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Common\StripeController;
use App\Http\Controllers\Auth\DefaultAuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


Route::get('stripe-connection/callback', [StripeController::class, 'stripeCallbackTwo'])->name('stripe.callback');
Route::get('stripe-connection/success/{id}', function ($id) {
    return redirect(rtrim(env('FRONTEND_URL'), '/') . "/stripe-connection/success/$id");
});
Route::get('stripe-connection/failed/{id}', function ($id) {
    return redirect(rtrim(env('FRONTEND_URL'), '/') . "/stripe-connection/failed/$id");
});
Route::get('/verify-email/{token}', [DefaultAuthController::class, 'verifyEmail']);
