<?php

use App\Http\Controllers\PaymentInvoiceFormController;
use App\Http\Controllers\ReturnForeignCurrencyRevenueController;
use Illuminate\Support\Facades\Route;

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

Route::resource('/payment-invoices', PaymentInvoiceFormController::class)->only(['create', 'store']);
Route::resource('/return-foreign-currency-revenue', ReturnForeignCurrencyRevenueController::class)->only(['create', 'store']);
