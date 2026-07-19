<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JobCardController;
use App\Http\Controllers\ClientVehicleController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ConsumablesController;
use Illuminate\Http\Request;

// Guest Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetCode'])->name('password.email');
Route::get('/reset-password', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

// PWA Routes
Route::get('/manifest.json', function () {
    $hasCustomLogo = file_exists(public_path('images/logo.png'));
    $iconPath = $hasCustomLogo ? asset('images/logo.png') : asset('images/generic-icon.png');

    return response()->json([
        'name' => config('app.name', 'Auto Workshop Manager'),
        'short_name' => config('app.name', 'Workshop'),
        'start_url' => '/',
        'display' => 'standalone',
        'background_color' => '#0f172a',
        'theme_color' => '#3b82f6',
        'icons' => [
            [
                'src' => $iconPath,
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any maskable'
            ],
            [
                'src' => $iconPath,
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any maskable'
            ]
        ]
    ]);
});

Route::get('/offline', function () {
    return view('errors.offline');
})->name('offline');



// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::match(['get', 'post'], '/insights', [DashboardController::class, 'insights'])->name('dashboard.insights');
    Route::get('/statistics', [DashboardController::class, 'statistics'])->name('dashboard.statistics');

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
    Route::patch('/job-cards/allocated-parts/{stockMovement}', [JobCardController::class, 'updateAllocatedPart'])->name('job-cards.update-allocated-part');
    Route::delete('/job-cards/allocated-parts/{stockMovement}', [JobCardController::class, 'deallocateParts'])->name('job-cards.deallocate-parts');
    // Outsourcing (specialist services) on job card
    Route::delete('/job-cards/outsourcing/{outsourcingItem}', [JobCardController::class, 'deleteOutsourcing'])->name('job-cards.delete-outsourcing');
    // Misc parts (dealer-direct) on job card
    Route::delete('/job-cards/misc-parts/{miscPart}', [JobCardController::class, 'deleteMiscPart'])->name('job-cards.delete-misc-part');
    // These POST routes use {jobCard} so must come AFTER the static delete routes above
    Route::post('/job-cards/{jobCard}/outsourcing', [JobCardController::class, 'addOutsourcing'])->name('job-cards.add-outsourcing');
    Route::post('/job-cards/{jobCard}/misc-parts', [JobCardController::class, 'addMiscPart'])->name('job-cards.add-misc-part');
    Route::post('/job-cards/{jobCard}/advanced-payments', [JobCardController::class, 'addAdvancedPayment'])->name('job-cards.add-advanced-payment');
    Route::delete('/job-cards/advanced-payments/{payment}', [JobCardController::class, 'deleteAdvancedPayment'])->name('job-cards.delete-advanced-payment');
    Route::post('/job-cards/{jobCard}/transportations', [JobCardController::class, 'addTransportation'])->name('job-cards.add-transportation');
    Route::delete('/job-cards/transportations/{transportation}', [JobCardController::class, 'deleteTransportation'])->name('job-cards.delete-transportation');

    // Appointments
    // Static sub-routes MUST precede the {appointment} wildcard
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::post('/appointments/notify-morning', [AppointmentController::class, 'sendMorningNotifications'])->name('appointments.notify-morning');
    Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
    Route::delete('/appointments/{appointment}', [AppointmentController::class, 'destroy'])->name('appointments.destroy');
    Route::post('/appointments/{appointment}/convert', [AppointmentController::class, 'convertToJobCard'])->name('appointments.convert');

    // Clients & Vehicles CRUD
    Route::get('/clients', [ClientVehicleController::class, 'clientsIndex'])->name('clients.index');
    // Duplicate-detection routes MUST come before the {client} wildcard
    Route::get('/clients/duplicates', [ClientVehicleController::class, 'clientsDuplicates'])->name('clients.duplicates');
    Route::post('/clients/merge', [ClientVehicleController::class, 'clientsMerge'])->name('clients.merge');
    Route::get('/clients/{client}', [ClientVehicleController::class, 'clientShow'])->name('clients.show');
    Route::post('/clients', [ClientVehicleController::class, 'clientStore'])->name('clients.store');
    Route::put('/clients/{client}', [ClientVehicleController::class, 'clientUpdate'])->name('clients.update');
    Route::delete('/clients/{client}', [ClientVehicleController::class, 'clientDestroy'])->name('clients.destroy');
    Route::post('/clients/sync-all', [ClientVehicleController::class, 'clientsSyncAll'])->name('clients.sync-all');
    Route::post('/clients/{client}/sync', [ClientVehicleController::class, 'clientSync'])->name('clients.sync');
    Route::get('/vehicles', [ClientVehicleController::class, 'vehiclesIndex'])->name('vehicles.index');
    Route::get('/vehicles/duplicates', [ClientVehicleController::class, 'vehiclesDuplicates'])->name('vehicles.duplicates');
    Route::post('/vehicles/merge', [ClientVehicleController::class, 'vehiclesMerge'])->name('vehicles.merge');
    Route::get('/vehicles/{vehicle}/history', [ClientVehicleController::class, 'vehicleHistory'])->name('vehicles.history');
    Route::post('/vehicles', [ClientVehicleController::class, 'vehicleStore'])->name('vehicles.store');
    Route::put('/vehicles/{vehicle}', [ClientVehicleController::class, 'vehicleUpdate'])->name('vehicles.update');
    Route::delete('/vehicles/{vehicle}', [ClientVehicleController::class, 'vehicleDestroy'])->name('vehicles.destroy');

    // Inventory Stock & Movements
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/forecast', [InventoryController::class, 'forecast'])->name('inventory.forecast');
    Route::get('/inventory/forecast/export', [InventoryController::class, 'exportForecastCsv'])->name('inventory.forecast.export');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
    Route::get('/inventory/{item}', [InventoryController::class, 'show'])->name('inventory.show');
    Route::put('/inventory/{item}', [InventoryController::class, 'update'])->name('inventory.update');
    Route::patch('/inventory/{item}/adjust', [InventoryController::class, 'adjustStock'])->name('inventory.adjust');
    Route::post('/inventory/{item}/batch', [InventoryController::class, 'addBatch'])->name('inventory.add-batch');
    Route::delete('/inventory/{item}', [InventoryController::class, 'destroy'])->name('inventory.destroy');

    // Consumables Supplies
    Route::get('/consumables', [ConsumablesController::class, 'index'])->name('consumables.index');
    Route::get('/consumables/forecast', [ConsumablesController::class, 'forecast'])->name('consumables.forecast');
    Route::post('/consumables', [ConsumablesController::class, 'store'])->name('consumables.store');
    Route::get('/consumables/{consumable}', [ConsumablesController::class, 'show'])->name('consumables.show');
    Route::post('/consumables/{consumable}/purchase', [ConsumablesController::class, 'storePurchase'])->name('consumables.purchase.store');
    Route::delete('/consumables/purchase/{purchase}', [ConsumablesController::class, 'deletePurchase'])->name('consumables.purchase.delete');
    Route::post('/consumables/{consumable}/usage', [ConsumablesController::class, 'storeUsage'])->name('consumables.usage.store');
    Route::delete('/consumables/usage/{usage}', [ConsumablesController::class, 'deleteUsage'])->name('consumables.usage.delete');

    // Billing & Invoices
    Route::get('/job-cards/{jobCard}/billing/workspace', [BillingController::class, 'showWorkspace'])->name('billing.workspace');
    Route::post('/job-cards/{jobCard}/billing', [BillingController::class, 'store'])->name('billing.store');
    Route::get('/job-cards/{jobCard}/billing/invoice', [BillingController::class, 'show'])->name('billing.show');
    Route::patch('/billing/{bill}/status', [BillingController::class, 'updateStatus'])->name('billing.update-status');

    // Quotations CRUD
    Route::get('/quotations', [App\Http\Controllers\QuotationController::class, 'index'])->name('quotations.index');
    Route::get('/quotations/create', [App\Http\Controllers\QuotationController::class, 'create'])->name('quotations.create');
    Route::post('/quotations', [App\Http\Controllers\QuotationController::class, 'store'])->name('quotations.store');
    Route::get('/quotations/{quotation}', [App\Http\Controllers\QuotationController::class, 'show'])->name('quotations.show');
    Route::get('/quotations/{quotation}/edit', [App\Http\Controllers\QuotationController::class, 'edit'])->name('quotations.edit');
    Route::put('/quotations/{quotation}', [App\Http\Controllers\QuotationController::class, 'update'])->name('quotations.update');
    Route::delete('/quotations/{quotation}', [App\Http\Controllers\QuotationController::class, 'destroy'])->name('quotations.destroy');

    // Payroll Salary Slips
    Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::get('/payroll/create/{user}', [PayrollController::class, 'createWorkspace'])->name('payroll.create');
    Route::post('/payroll', [PayrollController::class, 'store'])->name('payroll.store');
    Route::get('/payroll/{payrollSlip}', [PayrollController::class, 'show'])->name('payroll.show');
    Route::get('/payroll/{payrollSlip}/edit', [PayrollController::class, 'edit'])->name('payroll.edit');
    Route::put('/payroll/{payrollSlip}', [PayrollController::class, 'update'])->name('payroll.update');
    Route::post('/payroll/advances', [PayrollController::class, 'storeAdvance'])->name('payroll.advances.store');
    Route::delete('/payroll/advances/{advance}', [PayrollController::class, 'destroyAdvance'])->name('payroll.advances.destroy');
    Route::delete('/payroll/{payrollSlip}', [PayrollController::class, 'destroy'])->name('payroll.destroy');
    Route::patch('/payroll/{payrollSlip}/status', [PayrollController::class, 'updateStatus'])->name('payroll.update-status');

    // Attendance Tracker
    Route::post('/payroll/attendance', [PayrollController::class, 'attendanceStore'])->name('payroll.attendance.store');
    Route::get('/payroll/attendance/user/{user}', [PayrollController::class, 'employeeAttendanceIndex'])->name('payroll.attendance.employee');
    Route::post('/payroll/attendance/user/{user}', [PayrollController::class, 'employeeAttendanceStore'])->name('payroll.attendance.employee.store');

    // Employee CRUD
    Route::post('/employees', [PayrollController::class, 'employeeStore'])->name('employees.store');
    Route::put('/employees/{user}', [PayrollController::class, 'employeeUpdate'])->name('employees.update');
    Route::delete('/employees/{user}', [PayrollController::class, 'employeeDestroy'])->name('employees.destroy');

    // Employee Profile Utilization View
    Route::get('/employees/{user}', [PayrollController::class, 'employeeShow'])->name('employees.show');

    // Employee Archive / Unarchive
    Route::post('/employees/{user}/archive', [PayrollController::class, 'employeeArchive'])->name('employees.archive');
    Route::post('/employees/{user}/unarchive', [PayrollController::class, 'employeeUnarchive'])->name('employees.unarchive');

    // Outsourcing CRUD (Super Manager Only)
    Route::get('/outsourcing', [App\Http\Controllers\OutsourcingController::class, 'index'])->name('outsourcing.index');
    Route::post('/outsourcing', [App\Http\Controllers\OutsourcingController::class, 'store'])->name('outsourcing.store');
    Route::put('/outsourcing/{company}', [App\Http\Controllers\OutsourcingController::class, 'update'])->name('outsourcing.update');
    Route::delete('/outsourcing/{company}', [App\Http\Controllers\OutsourcingController::class, 'destroy'])->name('outsourcing.destroy');

    // Predefined Services CRUD (Super Manager Only)
    Route::get('/predefined-services', [App\Http\Controllers\PredefinedServiceController::class, 'index'])->name('services.index');
    Route::post('/predefined-services', [App\Http\Controllers\PredefinedServiceController::class, 'store'])->name('services.store');
    Route::put('/predefined-services/{service}', [App\Http\Controllers\PredefinedServiceController::class, 'update'])->name('services.update');
    Route::delete('/predefined-services/{service}', [App\Http\Controllers\PredefinedServiceController::class, 'destroy'])->name('services.destroy');

    // Double-entry accounting ledger & finance (Super Manager Only)
    Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');
    Route::post('/finance/accounts', [FinanceController::class, 'storeAccount'])->name('finance.accounts.store');
    Route::post('/finance/ledger', [FinanceController::class, 'storeJournalEntry'])->name('finance.ledger.store');
    Route::put('/finance/entries/{journalEntry}', [FinanceController::class, 'updateJournalEntry'])->name('finance.entries.update');
    Route::delete('/finance/entries/{journalEntry}', [FinanceController::class, 'destroyJournalEntry'])->name('finance.entries.destroy');
    Route::get('/finance/export/accounts', [FinanceController::class, 'exportAccountsCsv'])->name('finance.export.accounts');
    Route::get('/finance/export/ledger', [FinanceController::class, 'exportLedgerCsv'])->name('finance.export.ledger');
    Route::get('/finance/export/customers', [FinanceController::class, 'exportCustomerBooksCsv'])->name('finance.export.customers');
    Route::post('/finance/reconcile', [FinanceController::class, 'reconcile'])->name('finance.reconcile');

    // System Settings & Database Backups (Super Manager Only)
    Route::get('/settings', [App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/backup', [App\Http\Controllers\SettingsController::class, 'backup'])->name('settings.backup');
    Route::post('/settings/restore', [App\Http\Controllers\SettingsController::class, 'restore'])->name('settings.restore');
    Route::get('/settings/backup/download/{filename}', [App\Http\Controllers\SettingsController::class, 'downloadBackup'])->name('settings.backup.download');
    Route::post('/settings/backup/upload-restore', [App\Http\Controllers\SettingsController::class, 'uploadRestore'])->name('settings.backup.upload-restore');
    Route::post('/settings/logo', [App\Http\Controllers\SettingsController::class, 'uploadLogo'])->name('settings.logo');
    Route::delete('/settings/logo', [App\Http\Controllers\SettingsController::class, 'deleteLogo'])->name('settings.logo.delete');
    Route::post('/settings/reconcile-transportation', [App\Http\Controllers\SettingsController::class, 'reconcileHistoricalTransportation'])->name('settings.reconcile-transportation');
    Route::post('/settings/update', [App\Http\Controllers\SettingsController::class, 'updateSettings'])->name('settings.update');
    Route::post('/settings/shops', [App\Http\Controllers\SettingsController::class, 'storeShop'])->name('settings.shops.store');
    Route::delete('/settings/shops/{shop}', [App\Http\Controllers\SettingsController::class, 'deleteShop'])->name('settings.shops.delete');
    Route::post('/settings/roles', [App\Http\Controllers\SettingsController::class, 'storeRole'])->name('settings.roles.store');
    Route::put('/settings/roles/{role}', [App\Http\Controllers\SettingsController::class, 'updateRole'])->name('settings.roles.update');
    Route::delete('/settings/roles/{role}', [App\Http\Controllers\SettingsController::class, 'destroyRole'])->name('settings.roles.destroy');


    // Customer Broadcast Messaging (Super Manager Only)
    Route::get('/broadcast', [BroadcastController::class, 'index'])->name('broadcast.index');
    Route::post('/broadcast/send', [BroadcastController::class, 'send'])->name('broadcast.send');

    // Tracker Telemetry & Sync (Super Manager / Allowed roles)
    Route::get('/telemetry', [App\Http\Controllers\TrackerSyncController::class, 'telemetryIndex'])->name('telemetry.index');
    Route::post('/telemetry/sync', [App\Http\Controllers\TrackerSyncController::class, 'telemetrySync'])->name('telemetry.sync');
    Route::post('/telemetry/approve/{trackerVehicle}', [App\Http\Controllers\TrackerSyncController::class, 'telemetryApprove'])->name('telemetry.approve');

});

// Public API Route (Exempt from auth & CSRF)
Route::match(['get', 'post', 'options'], '/api/tickets/status', [App\Http\Controllers\ApiController::class, 'getTicketStatus'])->name('api.tickets.status');

// Tracker Sync API Routes (Exempt from auth & CSRF, verified with shared key)
Route::match(['post', 'options'], '/api/tracker/new-client', [App\Http\Controllers\TrackerSyncController::class, 'newClient']);
Route::match(['post', 'options'], '/api/tracker/update-odometer', [App\Http\Controllers\TrackerSyncController::class, 'updateOdometer']);


