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

        return view('settings.index', compact('backups', 'shops', 'roles'));
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
        ]);

        foreach ($data as $key => $value) {
            \App\Models\Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
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
}

