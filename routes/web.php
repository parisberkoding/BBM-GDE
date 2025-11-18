<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthentiactionController;
use App\Http\Controllers\RequestManagementController;

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

Route::middleware(['auth', 'role:superadmin'])->group(function () {
    Route::get('/superadmin/dashboard', function () {
        $title = "View Request BBM";
        return view('superadmin.index', compact('title'));
    })->name('superadmin-dashboard');


});

Route::middleware(['auth', 'role:manager'])->group(function () {
    Route::get('/manager/view-request-bbm', function () {
        $title = "View Request BBM";
        return view('manager.index', compact('title'));
    })->name('manager-dashboard');


});
