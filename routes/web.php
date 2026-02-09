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

Route::get('/', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Project is up and running',
    ]);
});

Route::get('stripe-connection/callback', [StripeController::class, 'stripeCallbackTwo'])->name('stripe.callback');
Route::get('stripe-connection/success/{id}', function ($id) {
    $frontendUrl = rtrim(env('FRONTEND_URL'), '/');
    if (!$frontendUrl) {
        return response('FRONTEND_URL is not set.', 500);
    }
    if (!preg_match('~^https?://~i', $frontendUrl)) {
        $frontendUrl = 'https://' . $frontendUrl;
    }
    $frontendHost = parse_url($frontendUrl, PHP_URL_HOST);
    if ($frontendHost && $frontendHost === request()->getHost()) {
        return response('FRONTEND_URL points to the backend domain. Update it to the frontend domain.', 500);
    }

    return redirect($frontendUrl . "/stripe-connection/success/$id");
});
Route::get('stripe-connection/failed/{id}', function ($id) {
    $frontendUrl = rtrim(env('FRONTEND_URL'), '/');
    if (!$frontendUrl) {
        return response('FRONTEND_URL is not set.', 500);
    }
    if (!preg_match('~^https?://~i', $frontendUrl)) {
        $frontendUrl = 'https://' . $frontendUrl;
    }
    $frontendHost = parse_url($frontendUrl, PHP_URL_HOST);
    if ($frontendHost && $frontendHost === request()->getHost()) {
        return response('FRONTEND_URL points to the backend domain. Update it to the frontend domain.', 500);
    }

    return redirect($frontendUrl . "/stripe-connection/failed/$id");
});
Route::get('/verify-email/{token}', [DefaultAuthController::class, 'verifyEmail']);
