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
            'basic_salary' => 5000.00
        ]);

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
            'basic_salary' => 3000.00,
            'required_days' => 26,
            'overtime_rate' => 20.00
        ]);

        $technician1 = User::create([
            'name' => 'Alex Mechanic',
            'email' => 'alex@workshop.com',
            'password' => Hash::make('Password123!'),
            'role' => 'worker',
            'basic_salary' => 2000.00,
            'required_days' => 26,
            'overtime_rate' => 15.00
        ]);

        $technician2 = User::create([
            'name' => 'Bob Electrician',
            'email' => 'bob@workshop.com',
            'password' => Hash::make('Password123!'),
            'role' => 'worker',
            'basic_salary' => 2200.00,
            'required_days' => 26,
            'overtime_rate' => 18.00
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

        // 6. Create Generic Inventory items
        $parts = [
            ['name' => 'Synthetic Engine Oil 5W-30', 'sku' => 'OIL-5W30-1L', 'quantity' => 150, 'price' => 25.00, 'unit' => 'liters'],
            ['name' => 'Premium Oil Filter', 'sku' => 'FLT-OIL-001', 'quantity' => 45, 'price' => 12.50, 'unit' => 'pcs'],
            ['name' => 'Ceramic Brake Pads (Front)', 'sku' => 'BRK-PAD-F02', 'quantity' => 20, 'price' => 65.00, 'unit' => 'pcs'],
            ['name' => 'OEM Spark Plug', 'sku' => 'SPK-PLG-NGK', 'quantity' => 80, 'price' => 8.50, 'unit' => 'pcs'],
            ['name' => 'Cabin Air Filter', 'sku' => 'FLT-CAB-004', 'quantity' => 30, 'price' => 18.00, 'unit' => 'pcs'],
        ];

        foreach ($parts as $part) {
            Inventory::create($part);
        }

        // 7. Create Default Payroll Categories
        $categories = [
            ['name' => 'Performance Bonus', 'type' => 'addition', 'default_amount' => 150.00],
            ['name' => 'OT Allowance', 'type' => 'addition', 'default_amount' => null],
            ['name' => 'EPF Employee Share (8%)', 'type' => 'deduction', 'default_amount' => null],
            ['name' => 'Advance Payment', 'type' => 'deduction', 'default_amount' => null],
        ];

        foreach ($categories as $cat) {
            PayrollCategory::create($cat);
        }

        // 8. Create Sample Job Cards at various Kanban stages
        // Job Card 1: Received
        $jc1 = JobCard::create([
            'vehicle_id' => $vehicle1->id,
            'shop_id' => $shopA->id,
            'status' => 'received-vehicle',
            'notes' => 'Routine 40,000km service and inspect abnormal brake squeal.',
            'estimated_cost' => 150.00
        ]);
        $jc1->workers()->attach([$technician1->id]);

        // Job Card 2: Ongoing
        $jc2 = JobCard::create([
            'vehicle_id' => $vehicle2->id,
            'shop_id' => $shopA->id,
            'status' => 'on-going',
            'notes' => 'Engine check light is on. Diagnose and replace faulty spark plugs.',
            'estimated_cost' => 250.00
        ]);
        $jc2->workers()->attach([$technician1->id, $technician2->id]);

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
    }
}
