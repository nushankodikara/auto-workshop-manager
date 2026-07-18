<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class SettingsController extends Controller
{
    private function checkAccess()
    {
        if (!Auth::user() || !Auth::user()->hasModuleAccess('settings')) {
            abort(403, 'Unauthorized module access.');
        }
    }

    /**
     * Show settings and backups console.
     */
    public function index()
    {
        $this->checkAccess();


        $backupDir = env('BACKUP_DIR', base_path('backups'));
        $backups = [];

        if (File::exists($backupDir)) {
            $files = File::glob($backupDir . '/backup_*.sqlite');
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

        $shops = \App\Models\Shop::all();
        $roles = \App\Models\Role::all();
        $accounts = \App\Models\Account::orderBy('code')->get();

        return view('settings.index', compact('backups', 'shops', 'roles', 'accounts'));
    }


    /**
     * Trigger a database backup.
     */
    public function backup()
    {
        $this->checkAccess();


        Artisan::call('db:backup');

        return back()->with('success', 'Manual database backup generated successfully.');
    }

    /**
     * Restore from a database backup.
     */
    public function restore(Request $request)
    {
        $this->checkAccess();


        $filename = $request->input('filename');

        Artisan::call('db:restore', [
            'filename' => $filename,
            '--no-interaction' => true
        ]);

        return back()->with('success', "Database successfully restored from: {$filename}");
    }

    /**
     * Download database backup file.
     */
    public function downloadBackup($filename)
    {
        $this->checkAccess();

        $backupDir = env('BACKUP_DIR', base_path('backups'));
        $filePath = $backupDir . '/' . basename($filename);

        if (!File::exists($filePath)) {
            abort(404, 'Backup file not found.');
        }

        return response()->download($filePath);
    }

    /**
     * Upload backup sqlite database and trigger restore.
     */
    public function uploadRestore(Request $request)
    {
        $this->checkAccess();

        $request->validate([
            'backup_file' => 'required|file|max:20480' // max 20MB
        ]);

        $file = $request->file('backup_file');
        
        $backupDir = env('BACKUP_DIR', base_path('backups'));
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0777, true, true);
        }

        $timestamp = date('Y-m-d_H-i-s');
        $filename = "uploaded_backup_{$timestamp}.sqlite";
        $filePath = $backupDir . '/' . $filename;
        
        $file->move($backupDir, $filename);

        try {
            Artisan::call('db:restore', [
                'filename' => $filePath,
                '--no-interaction' => true
            ]);
            
            return back()->with('success', 'Backup file successfully uploaded and restored! Active database replaced.');
        } catch (\Exception $e) {
            return back()->withErrors(['backup_file' => 'Restore failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Upload and save a cropped custom brand logo.
     */
    public function uploadLogo(Request $request)
    {
        $this->checkAccess();


        $request->validate([
            'logo_base64' => 'required|string',
        ]);

        $base64Data = $request->input('logo_base64');

        if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $matches)) {
            $imageType = strtolower($matches[1]);
            $decodedData = base64_decode(substr($base64Data, strpos($base64Data, ',') + 1));

            if ($decodedData === false) {
                return back()->withErrors(['logo' => 'Failed to decode uploaded image data.']);
            }

            $imagesDir = public_path('images');
            if (!File::exists($imagesDir)) {
                File::makeDirectory($imagesDir, 0755, true);
            }

            // Enforce saving as PNG logo
            File::put($imagesDir . '/logo.png', $decodedData);

            return back()->with('success', 'Workshop brand logo updated successfully.');
        }

        return back()->withErrors(['logo' => 'Invalid image format uploaded.']);
    }

    /**
     * Delete the custom brand logo.
     */
    public function deleteLogo()
    {
        $this->checkAccess();


        $logoPath = public_path('images/logo.png');

        if (File::exists($logoPath)) {
            File::delete($logoPath);
            return back()->with('success', 'Custom brand logo deleted. Reverted to default SVG logo.');
        }

        return back()->with('info', 'No custom logo exists to delete.');
    }

    /**
     * Update application settings.
     */
    public function updateSettings(Request $request)
    {
        $this->checkAccess();


        $data = $request->validate([
            'job_card_prefix' => 'required|string|max:50',
            'total_shares' => 'nullable|integer|min:1',
            's3_key' => 'nullable|string|max:255',
            's3_secret' => 'nullable|string|max:255',
            's3_region' => 'nullable|string|max:50',
            's3_bucket' => 'nullable|string|max:255',
            's3_endpoint' => 'nullable|string|max:255',
            'account_cashbook' => 'required|exists:accounts,code',
            'account_receivable' => 'required|exists:accounts,code',
            'account_inventory' => 'required|exists:accounts,code',
            'account_payable' => 'required|exists:accounts,code',
            'account_service_revenue' => 'required|exists:accounts,code',
            'account_parts_revenue' => 'required|exists:accounts,code',
            'account_cogs' => 'required|exists:accounts,code',
            'account_salaries' => 'required|exists:accounts,code',
            'account_consumables' => 'required|exists:accounts,code',
            'account_transportation' => 'required|exists:accounts,code',
            'account_transportation_revenue' => 'required|exists:accounts,code',
            'account_transportation_hire_expense' => 'required|exists:accounts,code',
        ]);

        $data['s3_enabled'] = $request->has('s3_enabled') ? '1' : '0';

        foreach ($data as $key => $value) {
            \App\Models\Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '']
            );
        }

        return back()->with('success', 'Settings updated successfully.');
    }

    /**
     * Add a shop location.
     */
    public function storeShop(Request $request)
    {
        $this->checkAccess();


        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
        ]);

        \App\Models\Shop::create($data);

        return back()->with('success', 'Shop location added successfully.');
    }

    /**
     * Delete a shop location.
     */
    public function deleteShop(\App\Models\Shop $shop)
    {
        $this->checkAccess();


        if ($shop->jobCards()->exists()) {
            return back()->withErrors(['shop' => 'Cannot delete shop location because it is linked to existing job cards.']);
        }

        $shop->delete();

        return back()->with('success', 'Shop location deleted successfully.');
    }

    /**
     * Create a custom user role.
     */
    public function storeRole(Request $request)
    {
        if (!Auth::user()->isSuperManager()) {
            abort(403, 'Unauthorized action.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:50|unique:roles,name|regex:/^[a-z0-9\-]+$/',
            'label' => 'required|string|max:255',
            'allowed_modules' => 'nullable|array',
        ]);

        \App\Models\Role::create([
            'name' => $data['name'],
            'label' => $data['label'],
            'allowed_modules' => $data['allowed_modules'] ?? [],
            'is_custom' => true
        ]);

        return back()->with('success', 'Custom user role created successfully.');
    }

    /**
     * Update permissions/allowed modules for a role.
     */
    public function updateRole(Request $request, \App\Models\Role $role)
    {
        if (!Auth::user()->isSuperManager()) {
            abort(403, 'Unauthorized action.');
        }

        $data = $request->validate([
            'label' => 'required|string|max:255',
            'allowed_modules' => 'nullable|array',
        ]);

        // Don't allow changing name slug of system roles, only update label & modules
        $role->update([
            'label' => $data['label'],
            'allowed_modules' => $data['allowed_modules'] ?? []
        ]);

        return back()->with('success', "Permissions for role '{$role->label}' updated successfully.");
    }

    /**
     * Delete a custom role.
     */
    public function destroyRole(\App\Models\Role $role)
    {
        if (!Auth::user()->isSuperManager()) {
            abort(403, 'Unauthorized action.');
        }

        if (!$role->is_custom) {
            return back()->withErrors(['role' => 'Cannot delete system default roles.']);
        }

        // Check if role is in use
        if (\App\Models\User::where('role', $role->name)->exists()) {
            return back()->withErrors(['role' => "Cannot delete role '{$role->label}' because it is assigned to one or more employees."]);
        }

        $role->delete();

        return back()->with('success', "Role '{$role->label}' has been deleted.");
    }

    /**
     * Scan and convert historical labor transportation line items into the formal transportation_fee field.
     */
    public function reconcileHistoricalTransportation(Request $request)
    {
        $this->checkAccess();

        $count = \Illuminate\Support\Facades\DB::transaction(function () {
            $updatedJobCardIds = [];
            $updatedBillIds = [];

            // 1. Process unbilled (or draft) Job Card Service items
            $services = \App\Models\JobCardService::where(function ($query) {
                $query->where('name', 'like', '%transport%')
                      ->orWhere('name', 'like', '%towing%')
                      ->orWhere('name', 'like', '%transportation%');
            })->get();

            foreach ($services as $service) {
                $jobCard = $service->jobCard;
                if (!$jobCard) continue;

                $jobCard->update([
                    'transportation_fee' => floatval($jobCard->transportation_fee) + floatval($service->price),
                    'transportation_type' => 'provided'
                ]);

                $updatedJobCardIds[] = $jobCard->id;
                $service->delete();
            }

            // 2. Process Bill Items (already generated invoices)
            $billItems = \App\Models\BillItem::where('type', 'labor')
                ->where(function ($query) {
                    $query->where('description', 'like', '%transport%')
                          ->orWhere('description', 'like', '%towing%')
                          ->orWhere('description', 'like', '%transportation%');
                })->get();

            foreach ($billItems as $item) {
                $bill = $item->bill;
                if (!$bill) continue;
                $jobCard = $bill->jobCard;
                if (!$jobCard) continue;

                $jobCard->update([
                    'transportation_fee' => floatval($jobCard->transportation_fee) + floatval($item->total_price),
                    'transportation_type' => 'provided'
                ]);

                $updatedBillIds[] = $bill->id;
                $item->delete();
            }

            // 3. Re-post and sync bookkeeping for affected bills
            $affectedBills = \App\Models\Bill::whereIn('id', array_unique($updatedBillIds))->get();
            foreach ($affectedBills as $bill) {
                \App\Services\DoubleEntryService::postBillTransaction($bill);
            }

            return count(array_unique(array_merge($updatedJobCardIds, $updatedBillIds)));
        });

        return back()->with('success', "Successfully reconciled {$count} historical transportation record(s) and re-balanced the bookkeeping ledger.");
    }
}

