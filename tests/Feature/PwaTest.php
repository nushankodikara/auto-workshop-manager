<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PwaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->detectEnvironment(fn() => 'testing');
    }

    /**
     * Test PWA manifest structure and dynamic generic fallback icon.
     */
    public function test_manifest_returns_correct_structure_with_generic_icon_fallback()
    {
        // Temporarily rename logo.png if it exists in public/images to force generic icon fallback
        $logoPath = public_path('images/logo.png');
        $tempLogoPath = public_path('images/logo_temp_test.png');
        $logoExisted = File::exists($logoPath);

        if ($logoExisted) {
            File::move($logoPath, $tempLogoPath);
        }

        $response = $this->get('/manifest.json');

        // Restore logo
        if ($logoExisted) {
            File::move($tempLogoPath, $logoPath);
        }

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertEquals(config('app.name', 'Auto Workshop Manager'), $data['name']);
        $this->assertEquals('standalone', $data['display']);
        
        // Assert it uses the generic icon fallback
        $this->assertStringContainsString('images/generic-icon.png', $data['icons'][0]['src']);
        $this->assertStringContainsString('images/generic-icon.png', $data['icons'][1]['src']);
    }

    /**
     * Test dynamic custom logo selection in manifest.
     */
    public function test_manifest_uses_custom_logo_if_exists()
    {
        $logoPath = public_path('images/logo.png');
        $logoExisted = File::exists($logoPath);

        // Ensure logo exists for this test
        if (!$logoExisted) {
            File::put($logoPath, 'dummy data');
        }

        $response = $this->get('/manifest.json');

        // Clean up dummy logo if it didn't exist originally
        if (!$logoExisted) {
            File::delete($logoPath);
        }

        $response->assertStatus(200);
        $data = $response->json();

        // Assert it uses custom logo
        $this->assertStringContainsString('images/logo.png', $data['icons'][0]['src']);
        $this->assertStringContainsString('images/logo.png', $data['icons'][1]['src']);
    }

    /**
     * Test offline page renders successfully.
     */
    public function test_offline_page_loads()
    {
        $response = $this->get(route('offline'));
        $response->assertStatus(200);
        $response->assertSee('You are offline');
    }
}
