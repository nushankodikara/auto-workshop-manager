<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Shop;
use App\Models\Client;
use App\Models\Vehicle;
use App\Models\JobCard;
use App\Models\Inventory;
use App\Models\PayrollCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Default Super Manager from env
        $adminEmail = env('ADMIN_EMAIL', 'admin@workshop.com');
        $adminPassword = env('ADMIN_PASSWORD', 'Password123!');

        $superManager = User::create([
            'name' => 'System Administrator',
            'email' => $adminEmail,
            'password' => Hash::make($adminPassword),
            'role' => 'super-manager',
            'allowed_modules' => ['dashboard', 'job-cards', 'clients', 'inventory', 'billing', 'payroll', 'settings'],
            'basic_salary' => 120000.00
        ]);

        // 1.5. Create Default Settings
        \App\Models\Setting::create([
            'key' => 'job_card_prefix',
            'value' => 'TDC-'
        ]);

        // 7. Create Default Payroll Categories (Base system categories)
        $categories = [
            ['name' => 'Performance Bonus', 'type' => 'addition', 'default_amount' => 5000.00],
            ['name' => 'OT Allowance', 'type' => 'addition', 'default_amount' => null],
            ['name' => 'EPF Employee Share (8%)', 'type' => 'deduction', 'default_amount' => null],
            ['name' => 'Advance Payment', 'type' => 'deduction', 'default_amount' => null],
        ];

        foreach ($categories as $cat) {
            PayrollCategory::create($cat);
        }

        // Check if we should seed sample/demo data (default is true in local development)
        $seedDemo = filter_var(env('SEED_DEMO_DATA', true), FILTER_VALIDATE_BOOLEAN);

        if ($seedDemo) {
            // 2. Create Shop locations
            $shopA = Shop::create([
                'name' => 'Main Workshop (Bay 1)',
                'address' => '123 Engine Lane, Auto City'
            ]);

            $shopB = Shop::create([
                'name' => 'Express Service Station',
                'address' => '456 Rapid Road, Auto City'
            ]);

            // 3. Create Workshop Managers and Workers
            $manager = User::create([
                'name' => 'John Manager',
                'email' => 'manager@workshop.com',
                'password' => Hash::make('Password123!'),
                'role' => 'manager',
                'allowed_modules' => ['dashboard', 'job-cards', 'clients', 'inventory', 'billing'],
                'basic_salary' => 85000.00,
                'required_days' => 26,
                'overtime_rate' => 500.00
            ]);

            $technician1 = User::create([
                'name' => 'Alex Mechanic',
                'email' => 'alex@workshop.com',
                'password' => Hash::make('Password123!'),
                'role' => 'worker',
                'basic_salary' => 60000.00,
                'required_days' => 26,
                'overtime_rate' => 350.00
            ]);

            $technician2 = User::create([
                'name' => 'Bob Electrician',
                'email' => 'bob@workshop.com',
                'password' => Hash::make('Password123!'),
                'role' => 'worker',
                'basic_salary' => 65000.00,
                'required_days' => 26,
                'overtime_rate' => 400.00
            ]);

            // 4. Create Sample Clients
            $client1 = Client::create([
                'name' => 'Alice Smith',
                'email' => 'alice@gmail.com',
                'phone' => '+94771234567',
                'address' => '78 High Street, Colombo'
            ]);

            $client2 = Client::create([
                'name' => 'David Miller',
                'email' => 'david@yahoo.com',
                'phone' => '+94776543210',
                'address' => '12 Flower Road, Colombo'
            ]);

            // 5. Create Sample Vehicles
            $vehicle1 = Vehicle::create([
                'client_id' => $client1->id,
                'make' => 'Toyota',
                'model' => 'Prius',
                'year' => 2018,
                'plate_number' => 'WP CAD-1234',
                'vin' => 'JTDKN3DU9J2034912'
            ]);

            $vehicle2 = Vehicle::create([
                'client_id' => $client2->id,
                'make' => 'Honda',
                'model' => 'Civic',
                'year' => 2020,
                'plate_number' => 'WP CBB-5678',
                'vin' => '1HGFC2F84LH019345'
            ]);

            // 6. Create Generic Inventory items with Rupees prices
            $parts = [
                ['name' => 'Synthetic Engine Oil 5W-30', 'sku' => 'OIL-5W30-1L', 'quantity' => 150, 'cost_price' => 3500.00, 'selling_price' => 4800.00, 'unit' => 'liters'],
                ['name' => 'Premium Oil Filter', 'sku' => 'FLT-OIL-001', 'quantity' => 45, 'cost_price' => 1800.00, 'selling_price' => 2500.00, 'unit' => 'pcs'],
                ['name' => 'Ceramic Brake Pads (Front)', 'sku' => 'BRK-PAD-F02', 'quantity' => 20, 'cost_price' => 8500.00, 'selling_price' => 12000.00, 'unit' => 'pcs'],
                ['name' => 'OEM Spark Plug', 'sku' => 'SPK-PLG-NGK', 'quantity' => 80, 'cost_price' => 1200.00, 'selling_price' => 1800.00, 'unit' => 'pcs'],
                ['name' => 'Cabin Air Filter', 'sku' => 'FLT-CAB-004', 'quantity' => 30, 'cost_price' => 2200.00, 'selling_price' => 3200.00, 'unit' => 'pcs'],
            ];

            foreach ($parts as $part) {
                $item = Inventory::create([
                    'name' => $part['name'],
                    'sku' => $part['sku'],
                    'quantity' => $part['quantity'],
                    'cost_price' => $part['cost_price'],
                    'selling_price' => $part['selling_price'],
                    'unit' => $part['unit'],
                ]);

                // Create initial batches for each item (old batch and newer batch)
                $qtyOld = (int)($part['quantity'] * 0.4);
                $qtyNew = $part['quantity'] - $qtyOld;

                $oldCost = round($part['cost_price'] * 0.9, 2);
                $oldSelling = round($part['selling_price'] * 0.9, 2);

                $batch1 = \App\Models\PurchaseBatch::create([
                    'inventory_id' => $item->id,
                    'batch_code' => 'BAT-20260115-' . $item->id,
                    'quantity_received' => $qtyOld,
                    'quantity_remaining' => $qtyOld,
                    'cost_price' => $oldCost,
                    'selling_price' => $oldSelling,
                    'supplier' => 'AutoParts Distributors Ltd',
                    'purchased_at' => '2026-01-15'
                ]);

                $batch2 = \App\Models\PurchaseBatch::create([
                    'inventory_id' => $item->id,
                    'batch_code' => 'BAT-20260510-' . $item->id,
                    'quantity_received' => $qtyNew,
                    'quantity_remaining' => $qtyNew,
                    'cost_price' => $part['cost_price'],
                    'selling_price' => $part['selling_price'],
                    'supplier' => 'Lanka Parts Wholesale',
                    'purchased_at' => '2026-05-10'
                ]);

                // Log stock movement inflows
                if ($qtyOld > 0) {
                    \App\Models\StockMovement::create([
                        'inventory_id' => $item->id,
                        'purchase_batch_id' => $batch1->id,
                        'type' => 'in',
                        'quantity' => $qtyOld,
                        'cost_price' => $oldCost,
                        'notes' => 'Supplier Delivery (Batch: ' . $batch1->batch_code . ')'
                    ]);
                }

                if ($qtyNew > 0) {
                    \App\Models\StockMovement::create([
                        'inventory_id' => $item->id,
                        'purchase_batch_id' => $batch2->id,
                        'type' => 'in',
                        'quantity' => $qtyNew,
                        'cost_price' => $part['cost_price'],
                        'notes' => 'Supplier Delivery (Batch: ' . $batch2->batch_code . ')'
                    ]);
                }
            }

            // 8. Create Sample Job Cards at various Kanban stages
            // Job Card 1: Received
            $jc1 = JobCard::create([
                'vehicle_id' => $vehicle1->id,
                'shop_id' => $shopA->id,
                'status' => 'received-vehicle',
                'notes' => 'Routine 40,000km service and inspect abnormal brake squeal.',
                'estimated_cost' => 25000.00
            ]);
            $jc1->workers()->attach([$technician1->id]);
            \App\Models\JobCardAssignment::create([
                'job_card_id' => $jc1->id,
                'user_id' => $technician1->id,
                'assigned_at' => $jc1->created_at ?: now()
            ]);

            // Job Card 2: Ongoing
            $jc2 = JobCard::create([
                'vehicle_id' => $vehicle2->id,
                'shop_id' => $shopA->id,
                'status' => 'on-going',
                'notes' => 'Engine check light is on. Diagnose and replace faulty spark plugs.',
                'estimated_cost' => 45000.00
            ]);
            $jc2->workers()->attach([$technician1->id, $technician2->id]);
            \App\Models\JobCardAssignment::create([
                'job_card_id' => $jc2->id,
                'user_id' => $technician1->id,
                'assigned_at' => $jc2->created_at ?: now()
            ]);
            \App\Models\JobCardAssignment::create([
                'job_card_id' => $jc2->id,
                'user_id' => $technician2->id,
                'assigned_at' => $jc2->created_at ?: now()
            ]);

            // 9. Create Sample Attendance records for the current month
            $year = (int)date('Y');
            $month = (int)date('m');
            $daysInMonth = (int)date('t', mktime(0, 0, 0, $month, 1, $year));

            for ($day = 1; $day <= 24; $day++) {
                $dateString = sprintf('%04d-%02d-%02d', $year, $month, $day);
                
                // Alex Mechanic attendance
                \App\Models\Attendance::create([
                    'user_id' => $technician1->id,
                    'date' => $dateString,
                    'status' => 'present',
                    'overtime_hours' => ($day % 5 === 0) ? 2.00 : 0.00
                ]);

                // Bob Electrician attendance
                \App\Models\Attendance::create([
                    'user_id' => $technician2->id,
                    'date' => $dateString,
                    'status' => 'present',
                    'overtime_hours' => ($day % 4 === 0) ? 1.50 : 0.00
                ]);
            }
            
            for ($day = 25; $day <= min(26, $daysInMonth); $day++) {
                $dateString = sprintf('%04d-%02d-%02d', $year, $month, $day);
                \App\Models\Attendance::create([
                    'user_id' => $technician1->id,
                    'date' => $dateString,
                    'status' => 'absent',
                    'overtime_hours' => 0.00
                ]);
                \App\Models\Attendance::create([
                    'user_id' => $technician2->id,
                    'date' => $dateString,
                    'status' => 'leave',
                    'overtime_hours' => 0.00
                ]);
            }
            
            // 10. Create a Completed Job Card with a Paid Invoice to seed financial stats
            $jc3_created = now()->subDays(2)->setTime(9, 0, 0); // 9:00 AM 2 days ago
            $jc3_completed = now()->subDays(1)->setTime(18, 30, 0); // 6:30 PM 1 day ago (1.5 hours past working day end)
            $jc3 = JobCard::create([
                'vehicle_id' => $vehicle1->id,
                'shop_id' => $shopA->id,
                'status' => 'waiting-to-pickup',
                'notes' => 'Brake pad replacement and spark plug check.',
                'estimated_cost' => 30000.00,
                'created_at' => $jc3_created,
                'updated_at' => $jc3_completed,
                'completed_at' => $jc3_completed,
                'last_sms' => "Dear Alice Smith, your vehicle Toyota Prius (Plate: WP CAD-1234) is ready to be picked up from Total Drive Care. Thank you!",
                'last_email' => "Hello Alice Smith,\n\nWe are pleased to inform you that your vehicle Toyota Prius (Plate: WP CAD-1234) has successfully passed all quality control tests and is ready to be picked up at your convenience.\n\nFinal Cost Summary: Rs.30,000.00\n\nThank you for choosing Total Drive Care!\n\nBest regards,\nTotal Drive Care Team"
            ]);
            $jc3->workers()->attach([$technician1->id]);
            \App\Models\JobCardAssignment::create([
                'job_card_id' => $jc3->id,
                'user_id' => $technician1->id,
                'assigned_at' => $jc3_created,
                'unassigned_at' => $jc3_completed,
                'created_at' => $jc3_created,
                'updated_at' => $jc3_completed
            ]);

            $brakePads = Inventory::where('sku', 'BRK-PAD-F02')->first();
            $sparkPlugs = Inventory::where('sku', 'SPK-PLG-NGK')->first();

            // FIFO batch fetch and allocation
            $bpBatch = $brakePads->purchaseBatches()->orderBy('purchased_at', 'asc')->first();
            if ($bpBatch) {
                $bpBatch->quantity_remaining -= 2;
                $bpBatch->save();
                $brakePads->quantity -= 2;
                $brakePads->save();

                \App\Models\StockMovement::create([
                    'inventory_id' => $brakePads->id,
                    'purchase_batch_id' => $bpBatch->id,
                    'job_card_id' => $jc3->id,
                    'type' => 'out',
                    'quantity' => -2,
                    'cost_price' => $bpBatch->cost_price,
                    'notes' => 'Allocated from batch: ' . $bpBatch->batch_code
                ]);
            }

            $spBatch = $sparkPlugs->purchaseBatches()->orderBy('purchased_at', 'asc')->first();
            if ($spBatch) {
                $spBatch->quantity_remaining -= 4;
                $spBatch->save();
                $sparkPlugs->quantity -= 4;
                $sparkPlugs->save();

                \App\Models\StockMovement::create([
                    'inventory_id' => $sparkPlugs->id,
                    'purchase_batch_id' => $spBatch->id,
                    'job_card_id' => $jc3->id,
                    'type' => 'out',
                    'quantity' => -4,
                    'cost_price' => $spBatch->cost_price,
                    'notes' => 'Allocated from batch: ' . $spBatch->batch_code
                ]);
            }

            $bpCost = $bpBatch ? $bpBatch->cost_price : $brakePads->cost_price;
            $bpSelling = $bpBatch ? $bpBatch->selling_price : $brakePads->selling_price;
            $spCost = $spBatch ? $spBatch->cost_price : $sparkPlugs->cost_price;
            $spSelling = $spBatch ? $spBatch->selling_price : $sparkPlugs->selling_price;

            $bill = \App\Models\Bill::create([
                'job_card_id' => $jc3->id,
                'bill_number' => 'BILL-' . date('Ymd') . '-001',
                'tax' => 0.00,
                'status' => 'paid',
                'total_amount' => (2 * $bpSelling) + (4 * $spSelling) + 8500.00,
            ]);

            \App\Models\BillItem::create([
                'bill_id' => $bill->id,
                'inventory_id' => $brakePads->id,
                'type' => 'part',
                'description' => $brakePads->name,
                'quantity' => 2,
                'cost_price' => $bpCost,
                'unit_price' => $bpSelling,
                'total_price' => 2 * $bpSelling
            ]);

            \App\Models\BillItem::create([
                'bill_id' => $bill->id,
                'inventory_id' => $sparkPlugs->id,
                'type' => 'part',
                'description' => $sparkPlugs->name,
                'quantity' => 4,
                'cost_price' => $spCost,
                'unit_price' => $spSelling,
                'total_price' => 4 * $spSelling
            ]);

            \App\Models\BillItem::create([
                'bill_id' => $bill->id,
                'inventory_id' => null,
                'type' => 'labor',
                'description' => 'Standard replacement & service labor',
                'quantity' => 1,
                'cost_price' => 0.00,
                'unit_price' => 8500.00,
                'total_price' => 8500.00
            ]);

            // 11. Create a Paid Payroll Slip for current month
            $netSalary = $technician1->basic_salary + 5000.00 - 2000.00;
            \App\Models\PayrollSlip::create([
                'user_id' => $technician1->id,
                'month' => $month,
                'year' => $year,
                'basic_salary' => $technician1->basic_salary,
                'allowance' => 5000.00,
                'deductions' => 2000.00,
                'net_salary' => $netSalary,
                'status' => 'paid',
                'required_days' => 26,
                'attended_days' => 24,
                'overtime_hours' => 4.00,
                'overtime_rate' => $technician1->overtime_rate,
                'overtime_amount' => 4.00 * $technician1->overtime_rate,
                'prorated_salary' => round(($technician1->basic_salary / 26) * 24, 2)
            ]);
        }
    }
}
