<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthentiactionController;
use App\Http\Controllers\RequestManagementController;
use App\Http\Controllers\SuperadminController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [AuthentiactionController::class, 'index'])->name('login');
Route::post('/login-process', [AuthentiactionController::class, 'authenticate'])->name('login-process');

// Route::get();

Route::middleware(['auth', 'role:requester,admin,superadmin,manager'])->group(function () {
    Route::post('/logout', [AuthentiactionController::class, 'logout'])->name('logout');
});


// Requester Routes
Route::middleware(['auth', 'role:requester'])->group(function () {
    Route::get('/requester/view-request-bbm', [RequestManagementController::class, 'requester_index'])->name('requester-index');
    Route::post('/requester/create-request-bbm', [RequestManagementController::class, 'requester_create'])->name('requester-create');
    Route::get('/requester/bbm-history', [RequestManagementController::class, 'requester_history'])->name('requester-history');
    Route::get('/requester/request/{id}', [RequestManagementController::class, 'requester_show'])->name('requester-show');
    Route::post('/requester/request/{id}/upload-proof', [RequestManagementController::class, 'requestper_upload_proof'])->name('requester-upload-proof');

    // Submit Report Route
    Route::post('/requester/request/{id}/submit-report', [RequestManagementController::class, 'requester_submit_report'])->name('requester.submit-report');
});

// Admin Routes
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [RequestManagementController::class, 'admin_index'])->name('admin-dashboard');
    Route::get('/admin/approval', [RequestManagementController::class, 'admin_approval'])->name('admin-approval');
    Route::post('/admin/approval/{id}/process', [RequestManagementController::class, 'admin_process_request'])->name('admin-process');
    Route::post('/admin/approval/bulk-process', [RequestManagementController::class, 'admin_bulk_process'])->name('admin-bulk-process');
});

// Route::middleware(['auth', 'role:superadmin'])->group(function () {
//     Route::get('/superadmin/dashboard', function () {
//         $title = "View Request BBM";
//         return view('superadmin.index', compact('title'));
//     })->name('superadmin-dashboard');


// });

Route::middleware(['auth', 'role:superadmin'])->group(function () {

    // Dashboard
    Route::get('/sa/dashboard', [SuperadminController::class, 'index'])->name('superadmin-dashboard');
    Route::get('/dashboard/stats', [SuperadminController::class, 'getDashboardStats'])->name('dashboard.stats');
    Route::get('/dashboard/driver-stats', [SuperadminController::class, 'getDetailedDriverStats'])->name('dashboard.driver-stats');

    // User Management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [SuperadminController::class, 'getAllUsers'])->name('index');
        Route::post('/', [SuperadminController::class, 'storeUser'])->name('store');
        Route::put('/{id}', [SuperadminController::class, 'updateUser'])->name('update');
        Route::delete('/{id}', [SuperadminController::class, 'deleteUser'])->name('delete');
        Route::post('/{id}/reset-password', [SuperadminController::class, 'resetPassword'])->name('reset-password');
    });

    // Vehicle Management
    Route::prefix('vehicles')->name('vehicles.')->group(function () {
        Route::get('/', [SuperadminController::class, 'getAllVehicles'])->name('index');
        Route::post('/', [SuperadminController::class, 'storeVehicle'])->name('store');
        Route::put('/{id}', [SuperadminController::class, 'updateVehicle'])->name('update');
        Route::delete('/{id}', [SuperadminController::class, 'deleteVehicle'])->name('delete');
    });

    // Request Management
    Route::prefix('requests')->name('requests.')->group(function () {
        Route::get('/', [SuperadminController::class, 'getAllRequests'])->name('index');
        Route::put('/{id}', [SuperadminController::class, 'updateRequest'])->name('update');
        Route::delete('/{id}', [SuperadminController::class, 'deleteRequest'])->name('delete');
    });

    // Transaction Proof Management
    Route::prefix('proofs')->name('proofs.')->group(function () {
        Route::get('/', [SuperadminController::class, 'getAllTransactionProofs'])->name('index');
        Route::post('/{id}', [SuperadminController::class, 'updateTransactionProof'])->name('update');
        Route::delete('/{id}', [SuperadminController::class, 'deleteTransactionProof'])->name('delete');
    });

    // Analytics
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/fuel-consumption', [SuperadminController::class, 'getVehicleFuelConsumption'])->name('fuel-consumption');
        Route::get('/km-performance', [SuperadminController::class, 'getVehicleKmPerformance'])->name('km-performance');
        Route::get('/pivot-table', [SuperadminController::class, 'getCompletePivotTable'])->name('pivot-table');
        Route::get('/request-analysis', [SuperadminController::class, 'getRequestAnalysis'])->name('request-analysis');
        Route::get('/latest-odo', [SuperadminController::class, 'getLatestOdoPerVehicle'])->name('latest-odo');
        Route::get('/monthly-summary', [SuperadminController::class, 'getMonthlyReportSummary'])->name('monthly-summary');
    });

    // Activity Log
    Route::get('/activity-log', [SuperadminController::class, 'getActivityLog'])->name('activity-log');

    // Export
    Route::prefix('export')->name('export.')->group(function () {
        Route::post('/csv', [SuperadminController::class, 'exportCSV'])->name('csv');
        Route::post('/pdf', [SuperadminController::class, 'exportPDF'])->name('pdf');
    });
});

Route::middleware(['auth', 'role:manager'])->group(function () {
    Route::get('/manager/view-request-bbm', function () {
        $title = "View Request BBM";
        return view('manager.index', compact('title'));
    })->name('manager-dashboard');


});
