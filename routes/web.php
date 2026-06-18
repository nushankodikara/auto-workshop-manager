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

    // Job Cards Kanban & Allocation
    Route::get('/job-cards', [JobCardController::class, 'board'])->name('job-cards.board');
    Route::get('/job-cards/{jobCard}', [JobCardController::class, 'show'])->name('job-cards.show');
    Route::post('/job-cards', [JobCardController::class, 'store'])->name('job-cards.store');
    Route::patch('/job-cards/{jobCard}/status', [JobCardController::class, 'updateStatus'])->name('job-cards.update-status');
    Route::post('/job-cards/{jobCard}/comments', [JobCardController::class, 'addComment'])->name('job-cards.comment');
    Route::post('/job-cards/{jobCard}/workers', [JobCardController::class, 'assignWorkers'])->name('job-cards.workers');
    Route::post('/job-cards/{jobCard}/parts', [JobCardController::class, 'allocateParts'])->name('job-cards.allocate-parts');

    // Clients & Vehicles CRUD
    Route::get('/clients', [ClientVehicleController::class, 'clientsIndex'])->name('clients.index');
    Route::get('/clients/{client}', [ClientVehicleController::class, 'clientShow'])->name('clients.show');
    Route::post('/clients', [ClientVehicleController::class, 'clientStore'])->name('clients.store');
    Route::put('/clients/{client}', [ClientVehicleController::class, 'clientUpdate'])->name('clients.update');
    Route::delete('/clients/{client}', [ClientVehicleController::class, 'clientDestroy'])->name('clients.destroy');
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

    // System Settings & Database Backups (Super Manager Only)
    Route::get('/settings', function () {
        if (!auth()->user()->isSuperManager()) {
            abort(403, 'Unauthorized module access.');
        }
        $backupDir = env('BACKUP_DIR', base_path('backups'));
        $backups = [];
        if (\Illuminate\Support\Facades\File::exists($backupDir)) {
            $files = \Illuminate\Support\Facades\File::glob($backupDir . '/backup_*.sqlite');
            $backups = array_map(function ($file) {
                return [
                    'name' => basename($file),
                    'size' => round(filesize($file) / 1024, 2) . ' KB',
                    'time' => date('Y-m-d H:i:s', filemtime($file))
                ];
            }, $files);
            usort($backups, function ($a, $b) {
                return strcmp($b['time'], $a['time']);
            });
        }
        return view('settings.index', compact('backups'));
    })->name('settings.index');

    Route::post('/settings/backup', function () {
        if (!auth()->user()->isSuperManager()) {
            abort(403, 'Unauthorized action.');
        }
        \Illuminate\Support\Facades\Artisan::call('db:backup');
        return back()->with('success', 'Manual database backup generated successfully.');
    })->name('settings.backup');

    Route::post('/settings/restore', function (Request $request) {
        if (!auth()->user()->isSuperManager()) {
            abort(403, 'Unauthorized action.');
        }
        $filename = $request->input('filename');
        \Illuminate\Support\Facades\Artisan::call('db:restore', ['filename' => $filename, '--no-interaction' => true]);
        return back()->with('success', "Database successfully restored from: {$filename}");
    })->name('settings.restore');
});
