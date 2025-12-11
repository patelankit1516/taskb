<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DiscountDemoController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root to discount demo
Route::get('/', function () {
    return redirect('/discount-demo');
});

Route::prefix('discount-demo')->name('discount-demo.')->group(function () {
    Route::get('/', [DiscountDemoController::class, 'index'])->name('index');
    Route::post('/create-samples', [DiscountDemoController::class, 'createSamples'])->name('create-samples');
    Route::post('/create-users', [DiscountDemoController::class, 'createUsers'])->name('create-users');
    Route::delete('/delete-samples', [DiscountDemoController::class, 'deleteSamples'])->name('delete-samples');
    Route::delete('/delete-users', [DiscountDemoController::class, 'deleteUsers'])->name('delete-users');
    Route::post('/assign', [DiscountDemoController::class, 'assign'])->name('assign');
    Route::post('/apply', [DiscountDemoController::class, 'apply'])->name('apply');
    Route::post('/calculate', [DiscountDemoController::class, 'calculate'])->name('calculate');
    Route::post('/revoke', [DiscountDemoController::class, 'revoke'])->name('revoke');
    Route::get('/eligible', [DiscountDemoController::class, 'eligible'])->name('eligible');
    Route::get('/audits', [DiscountDemoController::class, 'audits'])->name('audits');
    Route::get('/user/{userId}', [DiscountDemoController::class, 'userStatus'])->name('user-status');
    
    // Debug route
    Route::get('/debug', function() {
        $discounts = \TaskB\UserDiscounts\Models\Discount::all();
        return response()->json([
            'count' => $discounts->count(),
            'discounts' => $discounts->toArray()
        ]);
    })->name('debug');
});
