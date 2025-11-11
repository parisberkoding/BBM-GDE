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


Route::middleware(['auth', 'role:requester'])->group(function () {
    Route::get('/requester/view-request-bbm',[RequestManagementController::class, 'requester_index'])->name('requester-index');
    Route::post('/requester/create-request-bbm',[RequestManagementController::class, 'requester_create'])->name('requester-create');
    route::get('/requester/bbm-history',[RequestManagementController::class, 'requester_history'])->name('requester-history');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', function () {
        $title = "View Request BBM";
        return view('admin.index', compact('title'));
    })->name('admin-dashboard');


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
