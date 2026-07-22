<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountManagementTest extends TestCase
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
            'role' => 'super-manager'
        ]);
    }

    public function test_account_details_update_successfully()
    {
        $this->actingAs($this->superManager);

        $account = Account::create([
            'code' => '5005',
            'name' => 'Original Account Name',
            'type' => 'expense',
            'description' => 'Original description'
        ]);

        $response = $this->put(route('finance.accounts.update', $account->id), [
            'code' => '5010',
            'name' => 'Updated Account Name',
            'type' => 'expense',
            'description' => 'Updated description'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $account->refresh();
        $this->assertEquals('5010', $account->code);
        $this->assertEquals('Updated Account Name', $account->name);
        $this->assertEquals('Updated description', $account->description);
    }

    public function test_account_update_validates_code_uniqueness()
    {
        $this->actingAs($this->superManager);

        $account1 = Account::create([
            'code' => '5001',
            'name' => 'Account One',
            'type' => 'expense'
        ]);

        $account2 = Account::create([
            'code' => '5002',
            'name' => 'Account Two',
            'type' => 'expense'
        ]);

        // Attempting to update account2 to code '5001' should fail
        $response = $this->put(route('finance.accounts.update', $account2->id), [
            'code' => '5001',
            'name' => 'Account Two Updated',
            'type' => 'expense'
        ]);

        $response->assertSessionHasErrors('code');
        $account2->refresh();
        $this->assertEquals('5002', $account2->code);
    }

    public function test_account_update_synchronizes_settings_mappings()
    {
        $this->actingAs($this->superManager);

        $account = Account::where('code', '1000')->first();
        $this->assertNotNull($account);

        Setting::set('account_cashbook', '1000');

        $response = $this->put(route('finance.accounts.update', $account->id), [
            'code' => '1005',
            'name' => 'Cash Drawer Renamed',
            'type' => 'asset'
        ]);

        $response->assertRedirect();
        $this->assertEquals('1005', Setting::get('account_cashbook'));
    }

    public function test_account_deletion_blocked_when_mapped_in_settings()
    {
        $this->actingAs($this->superManager);

        $account = Account::where('code', '1000')->first();
        $this->assertNotNull($account);

        Setting::set('account_cashbook', '1000');

        $response = $this->delete(route('finance.accounts.destroy', $account->id));

        $response->assertSessionHasErrors('error');
        $this->assertDatabaseHas('accounts', ['id' => $account->id]);
    }

    public function test_account_deletion_blocked_when_journal_items_exist()
    {
        $this->actingAs($this->superManager);

        $account1 = Account::where('code', '1000')->first();
        $account2 = Account::where('code', '4000')->first();
        $this->assertNotNull($account1);
        $this->assertNotNull($account2);

        $entry = JournalEntry::create([
            'entry_date' => '2026-07-20',
            'description' => 'Test Transaction'
        ]);

        JournalItem::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $account1->id,
            'debit' => 100.00,
            'credit' => 0.00
        ]);

        JournalItem::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $account2->id,
            'debit' => 0.00,
            'credit' => 100.00
        ]);

        // Attempting to delete account1 should be blocked because of transaction lines
        $response = $this->delete(route('finance.accounts.destroy', $account1->id));

        $response->assertSessionHasErrors('error');
        $this->assertDatabaseHas('accounts', ['id' => $account1->id]);
    }

    public function test_account_deletion_succeeds_when_not_mapped_and_no_transactions()
    {
        $this->actingAs($this->superManager);

        $account = Account::create([
            'code' => '9999',
            'name' => 'Temporary Account',
            'type' => 'expense'
        ]);

        $response = $this->delete(route('finance.accounts.destroy', $account->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
    }
}
