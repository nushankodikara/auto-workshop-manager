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
        }
        .signature-block {
            width: 250px;
            border-top: 1px solid #475569;
            padding-top: 8px;
            text-align: center;
            font-size: 11px;
            color: #475569;
        }
        .signature-block .title {
            font-weight: 600;
            margin-top: 4px;
            color: #0f172a;
        }
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 8px 16px; background-color: #0284c7; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 12px;">
            Print Document
        </button>
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
                <th class="text-right">Advances Paid</th>
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
                    $advancesPaid = $advancesThisMonth->get($user->id, 0.00);
                @endphp
                <tr>
                    <td style="font-weight: 600; text-transform: capitalize;">{{ $user->name }}</td>
                    <td style="text-transform: capitalize; color: #475569;">{{ $user->role }}</td>
                    <td class="text-right font-mono" style="font-weight: 600;">{{ $presentCount }}</td>
                    <td class="text-right font-mono">
                        {{ $advancesPaid > 0 ? 'Rs. ' . number_format($advancesPaid, 2) : '-' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <div class="signature-block">
            Prepared By: <strong>{{ $preparedBy }}</strong>
            <div class="title">HR / Administrative Officer</div>
        </div>
        <div class="signature-block">
            <br>
            <div class="title">Authorized Signature / Date</div>
        </div>
    </div>

    <script>
        // Trigger print dialog automatically when print param is passed
        window.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('print')) {
                window.print();
            }
        });
    </script>
</body>
</html>
