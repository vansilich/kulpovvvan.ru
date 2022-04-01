<?php

use App\Http\Controllers\Api\GmailNewEventController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\GoogleManagerEmailReport;
use App\Http\Controllers\Api\GmailOauthCallback;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/google/manager-email-report', GoogleManagerEmailReport::class);
Route::get('/google/gmail/handle-oauth', [GmailOauthCallback::class, 'printCode'])->name('gmailOauth');
Route::post('/google/gmail/new-event', GmailNewEventController::class);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
