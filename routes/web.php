<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JobCardController;
use App\Http\Controllers\ClientVehicleController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\PayrollController;
use Illuminate\Http\Request;

// Guest Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::match(['get', 'post'], '/insights', [DashboardController::class, 'insights'])->name('dashboard.insights');

    // Job Cards Kanban & Allocation
    Route::get('/job-cards', [JobCardController::class, 'board'])->name('job-cards.board');
    Route::get('/job-cards/{jobCard}', [JobCardController::class, 'show'])->name('job-cards.show');
    Route::post('/job-cards', [JobCardController::class, 'store'])->name('job-cards.store');
    Route::put('/job-cards/{jobCard}', [JobCardController::class, 'update'])->name('job-cards.update');
    Route::patch('/job-cards/{jobCard}/status', [JobCardController::class, 'updateStatus'])->name('job-cards.update-status');
    Route::post('/job-cards/{jobCard}/comments', [JobCardController::class, 'addComment'])->name('job-cards.comment');
    Route::post('/job-cards/{jobCard}/workers', [JobCardController::class, 'assignWorkers'])->name('job-cards.workers');
    Route::post('/job-cards/{jobCard}/parts', [JobCardController::class, 'allocateParts'])->name('job-cards.allocate-parts');
    Route::post('/job-cards/{jobCard}/services', [JobCardController::class, 'addService'])->name('job-cards.add-service');
    Route::delete('/job-cards/services/{service}', [JobCardController::class, 'deleteService'])->name('job-cards.delete-service');

    // Clients & Vehicles CRUD
    Route::get('/clients', [ClientVehicleController::class, 'clientsIndex'])->name('clients.index');
    Route::get('/clients/{client}', [ClientVehicleController::class, 'clientShow'])->name('clients.show');
    Route::post('/clients', [ClientVehicleController::class, 'clientStore'])->name('clients.store');
    Route::put('/clients/{client}', [ClientVehicleController::class, 'clientUpdate'])->name('clients.update');
    Route::delete('/clients/{client}', [ClientVehicleController::class, 'clientDestroy'])->name('clients.destroy');
    Route::get('/vehicles', [ClientVehicleController::class, 'vehiclesIndex'])->name('vehicles.index');
    Route::get('/vehicles/{vehicle}/history', [ClientVehicleController::class, 'vehicleHistory'])->name('vehicles.history');
    Route::post('/vehicles', [ClientVehicleController::class, 'vehicleStore'])->name('vehicles.store');
    Route::put('/vehicles/{vehicle}', [ClientVehicleController::class, 'vehicleUpdate'])->name('vehicles.update');
    Route::delete('/vehicles/{vehicle}', [ClientVehicleController::class, 'vehicleDestroy'])->name('vehicles.destroy');

    // Inventory Stock & Movements
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
    Route::put('/inventory/{item}', [InventoryController::class, 'update'])->name('inventory.update');
    Route::patch('/inventory/{item}/adjust', [InventoryController::class, 'adjustStock'])->name('inventory.adjust');
    Route::delete('/inventory/{item}', [InventoryController::class, 'destroy'])->name('inventory.destroy');

    // Billing & Invoices
    Route::get('/job-cards/{jobCard}/billing/workspace', [BillingController::class, 'showWorkspace'])->name('billing.workspace');
    Route::post('/job-cards/{jobCard}/billing', [BillingController::class, 'store'])->name('billing.store');
    Route::get('/job-cards/{jobCard}/billing/invoice', [BillingController::class, 'show'])->name('billing.show');
    Route::patch('/billing/{bill}/status', [BillingController::class, 'updateStatus'])->name('billing.update-status');

    // Payroll Salary Slips
    Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::get('/payroll/create/{user}', [PayrollController::class, 'createWorkspace'])->name('payroll.create');
    Route::post('/payroll', [PayrollController::class, 'store'])->name('payroll.store');
    Route::get('/payroll/{payrollSlip}', [PayrollController::class, 'show'])->name('payroll.show');
    Route::patch('/payroll/{payrollSlip}/status', [PayrollController::class, 'updateStatus'])->name('payroll.update-status');

    // Attendance Tracker
    Route::post('/payroll/attendance', [PayrollController::class, 'attendanceStore'])->name('payroll.attendance.store');
    Route::get('/payroll/attendance/user/{user}', [PayrollController::class, 'employeeAttendanceIndex'])->name('payroll.attendance.employee');
    Route::post('/payroll/attendance/user/{user}', [PayrollController::class, 'employeeAttendanceStore'])->name('payroll.attendance.employee.store');

    // Employee CRUD
    Route::post('/employees', [PayrollController::class, 'employeeStore'])->name('employees.store');
    Route::put('/employees/{user}', [PayrollController::class, 'employeeUpdate'])->name('employees.update');
    Route::delete('/employees/{user}', [PayrollController::class, 'employeeDestroy'])->name('employees.destroy');

    // System Settings & Database Backups (Super Manager Only)
    Route::get('/settings', [App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/backup', [App\Http\Controllers\SettingsController::class, 'backup'])->name('settings.backup');
    Route::post('/settings/restore', [App\Http\Controllers\SettingsController::class, 'restore'])->name('settings.restore');
    Route::post('/settings/logo', [App\Http\Controllers\SettingsController::class, 'uploadLogo'])->name('settings.logo');
    Route::delete('/settings/logo', [App\Http\Controllers\SettingsController::class, 'deleteLogo'])->name('settings.logo.delete');
});
