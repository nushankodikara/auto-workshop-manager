<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\EmployeeAdvance;
use App\Models\PayrollCategory;
use App\Models\PayrollSlip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeAdvanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $superManager;
    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        // Create standard Chart of Accounts
        Account::create(['code' => '1000', 'name' => 'Cash & Bank', 'type' => 'asset']);
        Account::create(['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset']);
        Account::create(['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability']);
        Account::create(['code' => '5100', 'name' => 'Salaries Expense', 'type' => 'expense']);
        Account::create(['code' => '1220', 'name' => 'Salary Advances', 'type' => 'asset']);

        $this->superManager = User::create([
            'name' => 'Super Admin',
            'email' => 'super@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'super-manager',
            'basic_salary' => 50000.00,
            'total_salary' => 60000.00
        ]);

        $this->employee = User::create([
            'name' => 'Technician Jack',
            'email' => 'jack@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'worker',
            'basic_salary' => 30000.00,
            'total_salary' => 35000.00
        ]);

        PayrollCategory::create([
            'name' => 'Advance Payment',
            'type' => 'deduction',
            'default_amount' => null
        ]);
    }

    public function test_salary_advance_recording_and_ledger_posting()
    {
        $this->actingAs($this->superManager);

        // 1. Record an emergency advance payment of 15000.00
        $response = $this->post(route('payroll.advances.store'), [
            'user_id' => $this->employee->id,
            'amount' => 15000.00,
            'advance_date' => '2026-07-19',
            'reason' => 'Emergency medical expense'
        ]);

        $response->assertRedirect();
        $advance = EmployeeAdvance::first();
        $this->assertNotNull($advance);
        $this->assertEquals(15000.00, floatval($advance->amount));
        $this->assertEquals('pending', $advance->status);

        // Assert Double-Entry Journal Entry
        $entry = JournalEntry::where('reference', 'ADVANCE-' . $advance->id)->first();
        $this->assertNotNull($entry);
        // Debit Advances (1220) 15000.00, Credit Cash (1000) 15000.00
        $this->assertEquals(15000.00, floatval($entry->items()->where('account_id', Account::where('code', '1220')->first()->id)->first()->debit));
        $this->assertEquals(15000.00, floatval($entry->items()->where('account_id', Account::where('code', '1000')->first()->id)->first()->credit));

        // 2. Load the create Workspace for the employee and check advances pre-fill
        $workspaceResponse = $this->get(route('payroll.create', ['user' => $this->employee->id, 'year' => 2026, 'month' => 7]));
        $workspaceResponse->assertStatus(200);
        $workspaceResponse->assertSee('15000');

        // 3. Save the Payslip (Deducting the advance recovery)
        $payslipResponse = $this->post(route('payroll.store'), [
            'user_id' => $this->employee->id,
            'month' => 7,
            'year' => 2026,
            'required_days' => 26,
            'attended_days' => 26,
            'overtime_hours' => 0,
            'overtime_rate' => 0,
            'overtime_amount' => 0,
            'prorated_salary' => 30000.00,
            'total_salary' => 35000.00,
            'item_name' => ['Advance Payment'],
            'item_type' => ['deduction'],
            'item_amount' => [15000.00]
        ]);

        $payslipResponse->assertRedirect();
        
        // Verify advance status changed to deducted and linked
        $advance->refresh();
        $this->assertEquals('deducted', $advance->status);
        $this->assertNotNull($advance->payroll_slip_id);

        $slip = PayrollSlip::first();
        $this->assertNotNull($slip);
        $this->assertEquals(15000.00, floatval($slip->deductions));
        $this->assertEquals(15000.00, floatval($slip->net_salary)); // 30000 - 15000 = 15000 net payout

        // 4. Post the payslip to bookkeeping and check ledger re-balance
        \App\Services\DoubleEntryService::postPayrollSlipTransaction($slip);

        $slipEntry = JournalEntry::where('reference', 'SLIP-' . $slip->id)->first();
        $this->assertNotNull($slipEntry);

        // Debit Salaries Expense (5100) = 30000.00 (Gross Salary)
        $this->assertEquals(30000.00, floatval($slipEntry->items()->where('account_id', Account::where('code', '5100')->first()->id)->first()->debit));

        // Credit Accounts Payable (2000) for Net Payout = 15000.00
        $this->assertEquals(15000.00, floatval($slipEntry->items()->where('account_id', Account::where('code', '2000')->first()->id)->first()->credit));

        // Credit Salary Advances (1220) to clear the loan = 15000.00
        $this->assertEquals(15000.00, floatval($slipEntry->items()->where('account_id', Account::where('code', '1220')->first()->id)->first()->credit));
    }
}
