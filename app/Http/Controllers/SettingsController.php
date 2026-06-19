<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class SettingsController extends Controller
{
    /**
     * Show settings and backups console.
     */
    public function index()
    {
        if (!Auth::user()->isSuperManager()) {
            abort(403, 'Unauthorized module access.');
        }

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

        return view('settings.index', compact('backups'));
    }

    /**
     * Trigger a database backup.
     */
    public function backup()
    {
        if (!Auth::user()->isSuperManager()) {
            abort(403, 'Unauthorized action.');
        }

        Artisan::call('db:backup');

        return back()->with('success', 'Manual database backup generated successfully.');
    }

    /**
     * Restore from a database backup.
     */
    public function restore(Request $request)
    {
        if (!Auth::user()->isSuperManager()) {
            abort(403, 'Unauthorized action.');
        }

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
        if (!Auth::user()->isSuperManager()) {
            abort(403, 'Unauthorized action.');
        }

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
        if (!Auth::user()->isSuperManager()) {
            abort(403, 'Unauthorized action.');
        }

        $logoPath = public_path('images/logo.png');

        if (File::exists($logoPath)) {
            File::delete($logoPath);
            return back()->with('success', 'Custom brand logo deleted. Reverted to default SVG logo.');
        }

        return back()->with('info', 'No custom logo exists to delete.');
    }
}
