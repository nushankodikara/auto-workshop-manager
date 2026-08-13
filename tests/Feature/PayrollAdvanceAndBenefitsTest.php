<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Account;
use App\Models\EmployeeAdvance;
use App\Models\PayrollCategory;
use App\Models\PayrollSlip;
use App\Models\JournalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PayrollAdvanceAndBenefitsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superManager;
    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed Chart of Accounts
        Account::create(['code' => '1000', 'name' => 'Cash & Bank', 'type' => 'asset']);
        Account::create(['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset']);
        Account::create(['code' => '1220', 'name' => 'Salary Advances & Emergency Loans', 'type' => 'asset']);
        Account::create(['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability']);
        Account::create(['code' => '5100', 'name' => 'Salaries & Technician Wages', 'type' => 'expense']);
        Account::create(['code' => '5150', 'name' => 'Employee Benefits & Welfare Expense', 'type' => 'expense']);

        $this->superManager = User::create([
            'name' => 'HR Manager',
            'email' => 'hrmanager@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'super-manager',
            'allowed_modules' => ['dashboard', 'payroll']
        ]);

        $this->employee = User::create([
            'name' => 'Kasun Perera',
            'email' => 'kasun@test.com',
            'password' => Hash::make('Password123!'),
            'role' => 'technician',
            'basic_salary' => 60000.00,
            'total_salary' => 60000.00,
        ]);

        PayrollCategory::create(['name' => 'Company EPF (12%)', 'type' => 'benefit', 'default_amount' => 7200.00]);
        PayrollCategory::create(['name' => 'Company ETF (3%)', 'type' => 'benefit', 'default_amount' => 1800.00]);
    }

    public function test_salary_advance_disbursement_journal_entry()
    {
        $this->actingAs($this->superManager);

        $response = $this->post(route('payroll.advances.store'), [
            'user_id' => $this->employee->id,
            'amount' => 15000.00,
            'advance_date' => date('Y-m-d'),
            'reason' => 'Mid-month medical emergency'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check advance record
        $advance = EmployeeAdvance::where('user_id', $this->employee->id)->first();
        $this->assertNotNull($advance);
        $this->assertEquals(15000.00, floatval($advance->amount));

        // Check Journal Entry (Debit 1220 Advances Asset, Credit 1000 Cashbook)
        $entry = JournalEntry::where('reference', 'ADVANCE-' . $advance->id)->first();
        $this->assertNotNull($entry);

        $advancesAcc = Account::where('code', '1220')->first();
        $cashAcc = Account::where('code', '1000')->first();

        $debitItem = $entry->items()->where('account_id', $advancesAcc->id)->first();
        $creditItem = $entry->items()->where('account_id', $cashAcc->id)->first();

        $this->assertEquals(15000.00, floatval($debitItem->debit));
        $this->assertEquals(15000.00, floatval($creditItem->credit));
    }

    public function test_salary_slip_compilation_with_advance_deduction_and_company_benefits()
    {
        $this->actingAs($this->superManager);

        // 1. Issue advance of Rs. 10,000
        $advance = EmployeeAdvance::create([
            'user_id' => $this->employee->id,
            'amount' => 10000.00,
            'advance_date' => '2026-07-15',
            'reason' => 'Advance',
            'status' => 'pending'
        ]);
        \App\Services\DoubleEntryService::postEmployeeAdvanceTransaction($advance);

        // 2. Generate Salary Slip for month 7, year 2026
        $response = $this->post(route('payroll.store'), [
            'user_id' => $this->employee->id,
            'month' => 7,
            'year' => 2026,
            'required_days' => 22,
            'attended_days' => 22,
            'overtime_hours' => 10,
            'overtime_rate' => 500,
            'overtime_amount' => 5000.00, // OT = 5,000
            'prorated_salary' => 60000.00, // Prorated = 60,000
            'total_salary' => 60000.00,
            'item_name' => ['Food Benefit', 'EPF Employee 8%', 'Advance Payment'],
            'item_type' => ['benefit', 'deduction', 'deduction'],
            'item_amount' => [3000.00, 4800.00, 10000.00],
        ]);

        $response->assertRedirect();

        // Check slip
        $slip = PayrollSlip::where('user_id', $this->employee->id)->where('month', 7)->first();
        $this->assertNotNull($slip);

        // Gross = 60,000 + 5,000 = 65,000. Deductions = 14,800 (4,800 + 10,000). Net Takehome = 65,000 - 14,800 = 50,200.
        $this->assertEquals(50200.00, floatval($slip->net_salary));
        $this->assertEquals(3000.00, floatval($slip->company_benefits));
        $this->assertEquals(14800.00, floatval($slip->deductions));

        // Mark Slip as Paid and test double entry ledger posting
        $statusResponse = $this->patch(route('payroll.update-status', $slip->id), [
            'status' => 'paid'
        ]);
        $statusResponse->assertRedirect();

        $slipEntry = JournalEntry::where('reference', 'SLIP-' . $slip->id)->first();
        $this->assertNotNull($slipEntry);

        // Full Gross Salaries Expense (5100): 65,000
        // Salary Advances Asset Credit (1220): 10,000 (clearing the advance)
        $salariesAcc = Account::where('code', '5100')->first();
        $advancesAcc = Account::where('code', '1220')->first();
        $benefitsAcc = Account::where('code', '5150')->first();
        $cashAcc = Account::where('code', '1000')->first();

        $salariesItem = $slipEntry->items()->where('account_id', $salariesAcc->id)->first();
        $advancesItem = $slipEntry->items()->where('account_id', $advancesAcc->id)->first();
        $benefitsItem = $slipEntry->items()->where('account_id', $benefitsAcc->id)->first();
        $cashItem = $slipEntry->items()->where('account_id', $cashAcc->id)->first();

        $this->assertEquals(65000.00, floatval($salariesItem->debit));
        $this->assertEquals(10000.00, floatval($advancesItem->credit));
        $this->assertEquals(3000.00, floatval($benefitsItem->debit));
        $this->assertEquals(50200.00, floatval($cashItem->credit));
    }

    public function test_paid_benefit_advance_disbursement_journal_entry()
    {
        $this->actingAs($this->superManager);

        $response = $this->post(route('payroll.advances.store'), [
            'user_id' => $this->employee->id,
            'type' => 'benefit',
            'amount' => 4500.00,
            'advance_date' => date('Y-m-d'),
            'reason' => 'Food & Meals advance perquisite'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check advance record
        $advance = EmployeeAdvance::where('user_id', $this->employee->id)->where('type', 'benefit')->first();
        $this->assertNotNull($advance);
        $this->assertEquals('benefit', $advance->type);
        $this->assertEquals(4500.00, floatval($advance->amount));

        // Check Journal Entry (Debit 5150 Employee Benefits, Credit 1000 Cashbook)
        $entry = JournalEntry::where('reference', 'ADVANCE-' . $advance->id)->first();
        $this->assertNotNull($entry);

        $benefitsAcc = Account::where('code', '5150')->first();
        $cashAcc = Account::where('code', '1000')->first();

        $debitItem = $entry->items()->where('account_id', $benefitsAcc->id)->first();
        $creditItem = $entry->items()->where('account_id', $cashAcc->id)->first();

        $this->assertEquals(4500.00, floatval($debitItem->debit));
        $this->assertEquals(4500.00, floatval($creditItem->credit));
    }

    public function test_payroll_slip_month_wise_advances_and_structure_copying()
    {
        $this->actingAs($this->superManager);

        // 1. Issue an advance in July 2026 and another in August 2026
        $julyAdvance = EmployeeAdvance::create([
            'user_id' => $this->employee->id,
            'amount' => 5000.00,
            'advance_date' => '2026-07-15',
            'reason' => 'July Advance',
            'status' => 'pending'
        ]);

        $augustAdvance = EmployeeAdvance::create([
            'user_id' => $this->employee->id,
            'amount' => 8000.00,
            'advance_date' => '2026-08-05',
            'reason' => 'August Advance',
            'status' => 'pending'
        ]);

        // 2. Load create workspace for July 2026
        $response = $this->get(route('payroll.create', [
            'user' => $this->employee->id,
            'year' => 2026,
            'month' => 7
        ]));
        $response->assertStatus(200);
        // July advance should be seen (5000)
        $response->assertSee('5000');
        // August advance should NOT be seen (8000)
        $response->assertDontSee('8000');

        // 3. Save the Payslip for July 2026
        $payslipResponse = $this->post(route('payroll.store'), [
            'user_id' => $this->employee->id,
            'month' => 7,
            'year' => 2026,
            'required_days' => 26,
            'attended_days' => 26,
            'overtime_hours' => 0,
            'overtime_rate' => 0,
            'overtime_amount' => 0,
            'prorated_salary' => 60000.00,
            'total_salary' => 60000.00,
            'item_name' => ['Advance Payment', 'Project Bonus'],
            'item_type' => ['deduction', 'addition'],
            'item_amount' => [5000.00, 3500.00]
        ]);
        $payslipResponse->assertRedirect();

        // July advance should be deducted, August should still be pending
        $julyAdvance->refresh();
        $augustAdvance->refresh();
        $this->assertEquals('deducted', $julyAdvance->status);
        $this->assertEquals('pending', $augustAdvance->status);

        // 4. Load create workspace for August 2026
        $response2 = $this->get(route('payroll.create', [
            'user' => $this->employee->id,
            'year' => 2026,
            'month' => 8
        ]));
        $response2->assertStatus(200);
        // Should copy "Project Bonus" allowance (3500) from July slip
        $response2->assertSee('Project Bonus');
        $response2->assertSee('3500');
        // Should show August advance (8000)
        $response2->assertSee('8000');
        // Should NOT show July advance (5000) anymore as it was already deducted
        $response2->assertDontSee('5000');

        // 5. Check index page summary for July 2026 vs August 2026
        $indexResponse = $this->get(route('payroll.index', [
            'year' => 2026,
            'month' => 7
        ]));
        $indexResponse->assertStatus(200);

        $indexResponse2 = $this->get(route('payroll.index', [
            'year' => 2026,
            'month' => 8
        ]));
        $indexResponse2->assertStatus(200);
        // Should see August advance amount (8,000.00) in the summary
        $indexResponse2->assertSee('8,000.00');
    }
}
