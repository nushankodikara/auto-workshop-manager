<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Summary - {{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }}</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1e293b;
            background-color: #ffffff;
            margin: 0;
            padding: 40px;
            font-size: 12px;
            line-height: 1.5;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
        }
        .header h1 {
            font-size: 20px;
            margin: 0 0 5px 0;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header p {
            margin: 0;
            color: #64748b;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 50px;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 10px 12px;
            text-align: left;
        }
        th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
        }
        tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-right {
            text-align: right;
        }
        .font-mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }
        .footer {
            margin-top: 100px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 20px;
        }
        .signature-block {
            flex: 1;
            text-align: center;
            font-size: 11px;
            color: #475569;
        }
        .signature-block .title {
            font-weight: 600;
            margin-bottom: 55px;
            color: #0f172a;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.5px;
        }
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    @php
        $defaultPrepared = $preparedBy;
        $defaultChecked = isset($managers[0]) ? $managers[0]->name : '';
        $defaultApproved = isset($managers[1]) ? $managers[1]->name : (isset($managers[0]) ? $managers[0]->name : '');
        
        // If prepared by matches checked by, shift defaults to be different
        if ($defaultChecked === $defaultPrepared && isset($managers[1])) {
            $defaultChecked = $managers[1]->name;
            $defaultApproved = isset($managers[2]) ? $managers[2]->name : $managers[0]->name;
        }
    @endphp

    <!-- Print Control Panel (Hidden from printout via display: none !important) -->
    <div class="no-print" style="margin-bottom: 30px; background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 20px; border-radius: 12px; display: flex; flex-direction: column; gap: 20px;">
        <div style="display: flex; gap: 20px; flex-wrap: wrap;">
            <!-- Prepared By Group -->
            <div style="flex: 1; min-width: 220px; display: flex; flex-direction: column; gap: 8px;">
                <h4 style="margin: 0; font-size: 11px; text-transform: uppercase; color: #475569; font-weight: bold; border-b: 1px solid #e2e8f0; padding-bottom: 5px;">1. Prepared By</h4>
                <div>
                    <label for="select-prepared" style="display: block; font-size: 9px; font-weight: bold; text-transform: uppercase; color: #64748b; margin-bottom: 4px;">Select Name</label>
                    <select id="select-prepared" style="width: 100%; padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12px;">
                        @foreach($managers as $manager)
                            <option value="{{ $manager->name }}" {{ $defaultPrepared === $manager->name ? 'selected' : '' }}>{{ $manager->name }} ({{ $manager->role }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="input-prepared-title" style="display: block; font-size: 9px; font-weight: bold; text-transform: uppercase; color: #64748b; margin-bottom: 4px;">Custom Title</label>
                    <input type="text" id="input-prepared-title" value="HR / Administrative Officer" style="width: 90%; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12px;">
                </div>
            </div>

            <!-- Checked By Group -->
            <div style="flex: 1; min-width: 220px; display: flex; flex-direction: column; gap: 8px;">
                <h4 style="margin: 0; font-size: 11px; text-transform: uppercase; color: #475569; font-weight: bold; border-b: 1px solid #e2e8f0; padding-bottom: 5px;">2. Checked By</h4>
                <div>
                    <label for="select-checked" style="display: block; font-size: 9px; font-weight: bold; text-transform: uppercase; color: #64748b; margin-bottom: 4px;">Select Name</label>
                    <select id="select-checked" style="width: 100%; padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12px;">
                        @foreach($managers as $manager)
                            <option value="{{ $manager->name }}" {{ $defaultChecked === $manager->name ? 'selected' : '' }}>{{ $manager->name }} ({{ $manager->role }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="input-checked-title" style="display: block; font-size: 9px; font-weight: bold; text-transform: uppercase; color: #64748b; margin-bottom: 4px;">Custom Title</label>
                    <input type="text" id="input-checked-title" value="Manager / Coordinator" style="width: 90%; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12px;">
                </div>
            </div>

            <!-- Approved By Group -->
            <div style="flex: 1; min-width: 220px; display: flex; flex-direction: column; gap: 8px;">
                <h4 style="margin: 0; font-size: 11px; text-transform: uppercase; color: #475569; font-weight: bold; border-b: 1px solid #e2e8f0; padding-bottom: 5px;">3. Approved By</h4>
                <div>
                    <label for="select-approved" style="display: block; font-size: 9px; font-weight: bold; text-transform: uppercase; color: #64748b; margin-bottom: 4px;">Select Name</label>
                    <select id="select-approved" style="width: 100%; padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12px;">
                        @foreach($managers as $manager)
                            <option value="{{ $manager->name }}" {{ $defaultApproved === $manager->name ? 'selected' : '' }}>{{ $manager->name }} ({{ $manager->role }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="input-approved-title" style="display: block; font-size: 9px; font-weight: bold; text-transform: uppercase; color: #64748b; margin-bottom: 4px;">Custom Title</label>
                    <input type="text" id="input-approved-title" value="Director / Authorized Officer" style="width: 90%; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12px;">
                </div>
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; border-top: 1px solid #e2e8f0; padding-top: 15px;">
            <button onclick="window.print()" style="padding: 10px 24px; background-color: #0284c7; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 13px; transition: background-color 0.15s;">
                Print Document
            </button>
        </div>
    </div>

    <div class="header">
        <h1>Total Drive Care Solutions (PVT) LTD</h1>
        <p>Monthly Attendance & Advance Summary — {{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Employee Name</th>
                <th>Role</th>
                <th class="text-right">Number of Days (Present)</th>
                <th class="text-right">Salary Advance</th>
                <th class="text-right">Benefit Advance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
                @php
                    $uRecords = $attendanceData->get($user->id, collect());
                    $presentCount = 0;
                    foreach ($uRecords as $record) {
                        if ($record->status === 'present') {
                            $presentCount += 1.0;
                        } elseif ($record->status === 'half_day') {
                            $presentCount += 0.5;
                        }
                    }
                    $userAdvances = $advancesThisMonth->get($user->id, collect());
                    $salaryAdvances = $userAdvances->where('type', 'salary')->sum('amount');
                    $benefitAdvances = $userAdvances->where('type', 'benefit')->sum('amount');
                @endphp
                <tr>
                    <td style="font-weight: 600; text-transform: capitalize;">{{ $user->name }}</td>
                    <td style="text-transform: capitalize; color: #475569;">{{ $user->role }}</td>
                    <td class="text-right font-mono" style="font-weight: 600;">{{ $presentCount }}</td>
                    <td class="text-right font-mono">
                        {{ $salaryAdvances > 0 ? 'Rs. ' . number_format($salaryAdvances, 2) : '-' }}
                    </td>
                    <td class="text-right font-mono">
                        {{ $benefitAdvances > 0 ? 'Rs. ' . number_format($benefitAdvances, 2) : '-' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <div class="signature-block">
            <div class="title">Prepared By</div>
            <div style="border-top: 1px solid #475569; padding-top: 8px;">
                <strong id="sig-prepared-name">{{ $defaultPrepared }}</strong>
                <div id="sig-prepared-title" style="font-size: 10px; color: #64748b; margin-top: 2px;">HR / Administrative Officer</div>
            </div>
        </div>
        <div class="signature-block">
            <div class="title">Checked By</div>
            <div style="border-top: 1px solid #475569; padding-top: 8px;">
                <strong id="sig-checked-name">{{ $defaultChecked ?: 'Not Selected' }}</strong>
                <div id="sig-checked-title" style="font-size: 10px; color: #64748b; margin-top: 2px;">Manager / Coordinator</div>
            </div>
        </div>
        <div class="signature-block">
            <div class="title">Approved By</div>
            <div style="border-top: 1px solid #475569; padding-top: 8px;">
                <strong id="sig-approved-name">{{ $defaultApproved ?: 'Not Selected' }}</strong>
                <div id="sig-approved-title" style="font-size: 10px; color: #64748b; margin-top: 2px;">Director / Authorized Officer</div>
            </div>
        </div>
    </div>

    <script>
        // Synchronize dropdown selectors and text inputs with signature blocks
        const selectPrepared = document.getElementById('select-prepared');
        const selectChecked = document.getElementById('select-checked');
        const selectApproved = document.getElementById('select-approved');

        const inputPreparedTitle = document.getElementById('input-prepared-title');
        const inputCheckedTitle = document.getElementById('input-checked-title');
        const inputApprovedTitle = document.getElementById('input-approved-title');

        const sigPrepared = document.getElementById('sig-prepared-name');
        const sigChecked = document.getElementById('sig-checked-name');
        const sigApproved = document.getElementById('sig-approved-name');

        const sigPreparedTitle = document.getElementById('sig-prepared-title');
        const sigCheckedTitle = document.getElementById('sig-checked-title');
        const sigApprovedTitle = document.getElementById('sig-approved-title');

        selectPrepared.addEventListener('change', function() {
            sigPrepared.innerText = this.value;
        });
        selectChecked.addEventListener('change', function() {
            sigChecked.innerText = this.value;
        });
        selectApproved.addEventListener('change', function() {
            sigApproved.innerText = this.value;
        });

        inputPreparedTitle.addEventListener('input', function() {
            sigPreparedTitle.innerText = this.value;
        });
        inputCheckedTitle.addEventListener('input', function() {
            sigCheckedTitle.innerText = this.value;
        });
        inputApprovedTitle.addEventListener('input', function() {
            sigApprovedTitle.innerText = this.value;
        });

        // Trigger print dialog automatically when target=print is passed
        window.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('print')) {
                window.print();
            }
        });
    </script>
</body>
</html>
