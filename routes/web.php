<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CheckCsvController;
use App\Http\Controllers\MailgunerController;
use App\Http\Controllers\CheckTxtController;
use App\Http\Controllers\DirectController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\MetricaController;
use App\Http\Controllers\ComagicController;
use App\Http\Controllers\BigQueryController;
use App\Http\Controllers\GmailController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect()->route('checkTxtForm');
});

Route::get('/check-csv', [CheckCsvController::class, 'index'])->name('checkCsvForm');
Route::post('/check-csv', [CheckCsvController::class, 'handle'])->name('checkCsvHandle');

Route::get('/check-txt', [CheckTxtController::class, 'index'])->name('checkTxtForm');
Route::post('/check-txt', [CheckTxtController::class, 'handle'])->name('checkTxtHandle');

Route::prefix('mailganer')->group(function () {

    Route::get('/unsub', [MailgunerController::class, 'unsubForm'])->name('mailganerUnsubForm');
    Route::post('/unsub', [MailgunerController::class, 'handleUnsub'])->name('mailganerUnsubHandle');

    Route::get('/manager-email-table', [MailgunerController::class, 'ManagerEmailStatForm'])->name('ManagerEmailStatForm');
    Route::post('/manager-email-table/get-logs', [MailgunerController::class, 'logsByDate'])->name('ManagerEmailStatLogs');
});

Route::prefix('comagic')->group(function () {

    Route::get('/report/calls', [ComagicController::class, 'callsReportForm'])->name('comagicCallsReport');
    Route::post('/report/calls', [ComagicController::class, 'handleCallsReport'])->name('comagicCallsReportHandler');
});

Route::get('/direct-report', [DirectController::class, 'index'])->name('directReportForm');
Route::post('/direct-report', [DirectController::class, 'handle'])->name('directReportHandle');

Route::prefix('metrika')->group(function () {

    Route::get('/pages-report', [MetricaController::class, 'pagesReportForm'])->name('metrikaPagesReportForm');
    Route::post('/pages-report', [MetricaController::class, 'pagesReportFormHandle'])->name('metricaPagesReportHandle');

    Route::get('/print-pages-report', [MetricaController::class, 'printPagesReportForm'])->name('metrikaPrintPagesReportForm');
    Route::post('/print-pages-report', [MetricaController::class, 'printPagesReportHandle'])->name('metricaPrintPagesReportHandle');
});

Route::prefix('google')->group(function () {

    Route::get('bigquery/find-ym-uids', [BigQueryController::class, 'findYM_UIDForm'])->name('bigqueryFindYM_UIDForm');
    Route::post('bigquery/find-ym-uids', [BigQueryController::class, 'findYM_UIDHandle'])->name('bigqueryFindYM_UIDHandle');

    Route::get('analytics/report', [AnalyticsController::class, 'index'])->name('analyticReportForm');
    Route::post('analytics/report', [AnalyticsController::class, 'handle'])->name('analyticReportHandle');

    Route::get('gmail/report', [GmailController::class, 'emailsEntriesForm'])->name('emailsEntriesForm');
    Route::post('gmail/report', [GmailController::class, 'emailsEntriesHandle'])->name('emailsEntriesHandle');

    Route::get('gmail/triggers', [GmailController::class, 'triggersEntriesForm'])->name('triggersEntriesForm');
    Route::post('gmail/triggers', [GmailController::class, 'triggersEntriesHandle'])->name('triggersEntriesHandle');
});

Route::prefix('dashboard')->group(function () {

    Route::get('monthly/report', [DashboardController::class, 'monthlyReportForm'])->name('monthlyReportForm');
    Route::post('monthly/report', [DashboardController::class, 'monthlyReportHandle'])->name('monthlyReportHandle');
});
