<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superManager;
    protected User $worker;
    protected string $testLogoPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->detectEnvironment(fn() => 'testing');

        $this->superManager = User::create([
            'name' => 'Super Manager',
            'email' => 'super@test.com',
            'password' => bcrypt('Password123!'),
            'role' => 'super-manager'
        ]);

        $this->worker = User::create([
            'name' => 'Tech Worker',
            'email' => 'worker@test.com',
            'password' => bcrypt('Password123!'),
            'role' => 'worker'
        ]);

        $this->testLogoPath = public_path('images/logo.png');

        // Backup existing logo if any to prevent breaking developer environment
        if (File::exists($this->testLogoPath)) {
            File::move($this->testLogoPath, $this->testLogoPath . '.bak');
        }
    }

    protected function tearDown(): void
    {
        // Cleanup test logo
        if (File::exists($this->testLogoPath)) {
            File::delete($this->testLogoPath);
        }

        // Restore backup logo
        if (File::exists($this->testLogoPath . '.bak')) {
            File::move($this->testLogoPath . '.bak', $this->testLogoPath);
        }

        parent::tearDown();
    }

    /**
     * Test Settings index access controls.
     */
    public function test_settings_page_access_controls()
    {
        // Worker is forbidden
        $response = $this->actingAs($this->worker)->get(route('settings.index'));
        $response->assertStatus(403);

        // Super Manager is allowed
        $response = $this->actingAs($this->superManager)->get(route('settings.index'));
        $response->assertStatus(200);
    }

    /**
     * Test uploading a custom base64-encoded cropped logo.
     */
    public function test_super_manager_can_upload_and_delete_brand_logo()
    {
        $this->actingAs($this->superManager);

        // Ensure logo does not exist initially
        if (File::exists($this->testLogoPath)) {
            File::delete($this->testLogoPath);
        }

        // 1. Upload base64 cropped PNG logo (1x1 transparent pixel)
        $base64Image = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->post(route('settings.logo'), [
            'logo_base64' => $base64Image
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // Verify the file was saved on disk
        $this->assertTrue(File::exists($this->testLogoPath));
        $this->assertEquals(
            'image/png',
            mime_content_type($this->testLogoPath)
        );

        // 2. Delete the brand logo
        $response = $this->delete(route('settings.logo.delete'));
        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // Verify file was deleted from disk
        $this->assertFalse(File::exists($this->testLogoPath));
    }

    /**
     * Test worker is unauthorized from uploading logo.
     */
    public function test_worker_cannot_upload_or_delete_brand_logo()
    {
        $this->actingAs($this->worker);

        $base64Image = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->post(route('settings.logo'), [
            'logo_base64' => $base64Image
        ]);
        $response->assertStatus(403);

        $response = $this->delete(route('settings.logo.delete'));
        $response->assertStatus(403);
    }

    /**
     * Test S3 settings update.
     */
    public function test_s3_settings_update()
    {
        $response = $this->actingAs($this->superManager)->post(route('settings.update'), [
            'job_card_prefix' => 'TEST-',
            'total_shares' => 50000,
            's3_enabled' => '1',
            's3_key' => 'AKIAIOSFODNN7EXAMPLE',
            's3_secret' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            's3_region' => 'us-west-2',
            's3_bucket' => 'test-bucket',
            's3_endpoint' => 'https://custom-s3-endpoint.com'
        ]);

        $response->assertRedirect();
        
        $this->assertEquals('TEST-', \App\Models\Setting::get('job_card_prefix'));
        $this->assertEquals('50000', \App\Models\Setting::get('total_shares'));
        $this->assertEquals('1', \App\Models\Setting::get('s3_enabled'));
        $this->assertEquals('AKIAIOSFODNN7EXAMPLE', \App\Models\Setting::get('s3_key'));
        $this->assertEquals('wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY', \App\Models\Setting::get('s3_secret'));
        $this->assertEquals('us-west-2', \App\Models\Setting::get('s3_region'));
        $this->assertEquals('test-bucket', \App\Models\Setting::get('s3_bucket'));
        $this->assertEquals('https://custom-s3-endpoint.com', \App\Models\Setting::get('s3_endpoint'));
    }

    /**
     * Test downloading database backups and uploading to restore.
     */
    public function test_backup_download_and_upload_restore()
    {
        $this->actingAs($this->superManager);

        // 1. Run manual backup to create a backup file
        $response = $this->post(route('settings.backup'));
        $response->assertRedirect();

        $backupDir = env('BACKUP_DIR', base_path('backups'));
        $files = File::glob($backupDir . '/backup_*.sqlite');
        $this->assertNotEmpty($files);
        
        $latestBackup = basename($files[count($files) - 1]);

        // 2. Download backup
        $downloadResponse = $this->get(route('settings.backup.download', $latestBackup));
        $downloadResponse->assertStatus(200);
        $downloadResponse->assertHeader('Content-Disposition', 'attachment; filename=' . $latestBackup);

        // 3. Copy active DB to a temporary file and upload it to test restore
        $dbPath = config('database.connections.sqlite.database');
        $tempUploadFile = tempnam(sys_get_temp_dir(), 'bkp') . '.sqlite';
        File::copy($dbPath, $tempUploadFile);

        $uploadedFile = new \Illuminate\Http\UploadedFile(
            $tempUploadFile,
            'test_upload_backup.sqlite',
            'application/x-sqlite3',
            null,
            true
        );

        $uploadResponse = $this->post(route('settings.backup.upload-restore'), [
            'backup_file' => $uploadedFile
        ]);

        $uploadResponse->assertRedirect();
        $uploadResponse->assertSessionHas('success');

        // Cleanup temp file
        if (File::exists($tempUploadFile)) {
            File::delete($tempUploadFile);
        }
    }
}
