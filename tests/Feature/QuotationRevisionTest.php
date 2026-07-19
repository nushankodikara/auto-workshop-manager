<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\QuotationRevision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class QuotationRevisionTest extends TestCase
{
    use RefreshDatabase;

    protected User $superManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superManager = User::create([
            'name' => 'Super Admin',
            'email' => 'super@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'super-manager',
        ]);
    }

    public function test_quotation_revision_creation_and_snapshot_archiving()
    {
        $this->actingAs($this->superManager);

        // 1. Create a Quotation
        $quotation = Quotation::create([
            'quotation_number' => 'QT-20260719-0001',
            'customer_name' => 'Alice Smith',
            'customer_phone' => '94777111222',
            'customer_email' => 'alice@test.com',
            'customer_address' => 'Galle Road, Colombo',
            'tax' => 10.00,
            'discount_percent' => 5.00,
            'total_amount' => 950.00 // initial estimated amount
        ]);

        QuotationItem::create([
            'quotation_id' => $quotation->id,
            'type' => 'part',
            'description' => 'Original Brake Pads',
            'quantity' => 1,
            'unit_price' => 1000.00,
            'total_price' => 1000.00
        ]);

        // Verify initial state
        $this->assertEquals('Alice Smith', $quotation->customer_name);
        $this->assertEquals(0, $quotation->revisions()->count());

        // 2. Submit PUT request to edit/revise the quotation
        $response = $this->put(route('quotations.update', $quotation->id), [
            'customer_name' => 'Alice Smith Revised',
            'customer_phone' => '94777111333',
            'customer_email' => 'alice.revised@test.com',
            'customer_address' => 'Flower Road, Colombo',
            'tax' => 8.00,
            'discount_percent' => 10.00,
            'revision_reason' => 'Customer requested OEM brake disc replacement instead of pads',
            'items' => [
                [
                    'description' => 'OEM Brake Disc Rotor',
                    'type' => 'part',
                    'quantity' => 2,
                    'unit_price' => 3000.00
                ],
                [
                    'description' => 'Brake Replacement Labor',
                    'type' => 'labor',
                    'quantity' => 1,
                    'unit_price' => 1500.00
                ]
            ]
        ]);

        $response->assertRedirect(route('quotations.show', $quotation->id));

        // 3. Verify Quotation model is updated
        $quotation->refresh();
        $this->assertEquals('Alice Smith Revised', $quotation->customer_name);
        $this->assertEquals(8.00, floatval($quotation->tax));
        $this->assertEquals(10.00, floatval($quotation->discount_percent));

        // Subtotal = 2 * 3000 + 1500 = 7500
        // Discount 10% = 750
        // Subtotal after discount = 6750
        // Tax 8% = 540
        // Total amount = 7290.00
        $this->assertEquals(7290.00, floatval($quotation->total_amount));

        // Verify old items are deleted and replaced
        $this->assertEquals(2, $quotation->items()->count());
        $this->assertDatabaseMissing('quotation_items', ['description' => 'Original Brake Pads']);
        $this->assertDatabaseHas('quotation_items', ['description' => 'OEM Brake Disc Rotor']);

        // 4. Verify Revision History Snapshot
        $this->assertEquals(1, $quotation->revisions()->count());
        $revision = $quotation->revisions()->first();
        $this->assertEquals(1, $revision->revision_number);
        $this->assertEquals($this->superManager->id, $revision->revised_by);
        $this->assertEquals('Customer requested OEM brake disc replacement instead of pads', $revision->reason);
        $this->assertEquals(950.00, floatval($revision->total_amount));

        // Verify JSON metadata contains correct historical values
        $metadata = $revision->metadata;
        $this->assertEquals('Alice Smith', $metadata['customer_name']);
        $this->assertEquals(10.00, $metadata['tax']);
        $this->assertEquals(5.00, $metadata['discount_percent']);
        $this->assertCount(1, $metadata['items']);
        $this->assertEquals('Original Brake Pads', $metadata['items'][0]['description']);
        $this->assertEquals(1000.00, $metadata['items'][0]['unit_price']);
    }
}
