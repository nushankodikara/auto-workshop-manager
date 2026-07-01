<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\EmailService;
use App\Services\FitSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BroadcastController extends Controller
{
    protected FitSmsService $smsService;
    protected EmailService $emailService;

    public function __construct(FitSmsService $smsService, EmailService $emailService)
    {
        $this->smsService = $smsService;
        $this->emailService = $emailService;
    }

    private function checkAccess()
    {
        if (!Auth::user() || !Auth::user()->isSuperManager()) {
            abort(403, 'Unauthorized module access.');
        }
    }

    public function index(Request $request)
    {
        $this->checkAccess();

        $target = $request->input('target', 'customers');
        $timeframe = $request->input('timeframe', 'all');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $clients = [];
        $employees = [];

        if ($target === 'customers') {
            $query = Client::query();

            // Check if filter is active
            if ($timeframe !== 'all') {
                $dateLimit = null;
                if ($timeframe === 'last_week') {
                    $dateLimit = now()->subDays(7)->startOfDay();
                } elseif ($timeframe === 'last_month') {
                    $dateLimit = now()->subDays(30)->startOfDay();
                } elseif ($timeframe === 'last_3_months') {
                    $dateLimit = now()->subDays(90)->startOfDay();
                } elseif ($timeframe === 'last_6_months') {
                    $dateLimit = now()->subDays(180)->startOfDay();
                }

                if ($dateLimit) {
                    $query->whereHas('jobCards', function ($q) use ($dateLimit) {
                        $q->where('job_cards.created_at', '>=', $dateLimit);
                    });
                } elseif ($timeframe === 'custom' && $startDate && $endDate) {
                    $start = Carbon::parse($startDate)->startOfDay();
                    $end = Carbon::parse($endDate)->endOfDay();
                    $query->whereHas('jobCards', function ($q) use ($start, $end) {
                        $q->whereBetween('job_cards.created_at', [$start, $end]);
                    });
                }
            }

            $clients = $query->select('clients.*')
                ->selectSub(function ($q) {
                    $q->select('job_cards.created_at')
                        ->from('job_cards')
                        ->join('vehicles', 'vehicles.id', '=', 'job_cards.vehicle_id')
                        ->whereColumn('vehicles.client_id', 'clients.id')
                        ->orderBy('job_cards.created_at', 'desc')
                        ->limit(1);
                }, 'last_service_date')
                ->orderBy('name')
                ->get();
        } else {
            // Employees broadcast
            $employees = \App\Models\User::where('is_archived', false)
                ->orderBy('name')
                ->get();
        }

        return view('broadcast.index', compact('clients', 'employees', 'target', 'timeframe', 'startDate', 'endDate'));
    }

    public function send(Request $request)
    {
        $this->checkAccess();

        $data = $request->validate([
            'target' => 'nullable|in:customers,employees',
            'clients' => 'nullable|array',
            'clients.*' => 'exists:clients,id',
            'recipients' => 'nullable|array',
            'type' => 'required|in:sms,email',
            'subject' => 'required_if:type,email|nullable|string|max:255',
            'message' => 'required|string',
        ]);

        $target = $data['target'] ?? 'customers';
        $recipientIds = $target === 'customers' 
            ? ($data['recipients'] ?? $data['clients'] ?? []) 
            : ($data['recipients'] ?? []);

        if (empty($recipientIds)) {
            return back()->withErrors(['recipients' => 'At least one recipient must be selected.']);
        }

        $successCount = 0;
        $failCount = 0;

        if ($target === 'customers') {
            $clients = Client::whereIn('id', $recipientIds)->get();
            foreach ($clients as $client) {
                try {
                    if ($data['type'] === 'sms') {
                        if (empty($client->phone)) {
                            $failCount++;
                            continue;
                        }
                        $this->smsService->sendSms($client->phone, $data['message']);
                        $successCount++;
                    } else {
                        if (empty($client->email)) {
                            $failCount++;
                            continue;
                        }
                        $this->emailService->sendEmail($client->email, $data['subject'], $data['message']);
                        $successCount++;
                    }
                } catch (\Exception $e) {
                    $failCount++;
                    Log::error("Broadcast sending failed for client ID {$client->id}: " . $e->getMessage());
                }
            }
        } else {
            // Employees
            $users = \App\Models\User::whereIn('id', $recipientIds)->get();
            foreach ($users as $user) {
                try {
                    if ($data['type'] === 'sms') {
                        if (empty($user->contact_number)) {
                            $failCount++;
                            continue;
                        }
                        $this->smsService->sendSms($user->contact_number, $data['message']);
                        $successCount++;
                    } else {
                        if (empty($user->email)) {
                            $failCount++;
                            continue;
                        }
                        $this->emailService->sendEmail($user->email, $data['subject'], $data['message']);
                        $successCount++;
                    }
                } catch (\Exception $e) {
                    $failCount++;
                    Log::error("Broadcast sending failed for employee ID {$user->id}: " . $e->getMessage());
                }
            }
        }

        $methodName = strtoupper($data['type']);
        $targetName = $target === 'customers' ? 'customers' : 'employees';
        $message = "Broadcast completed! Successfully sent via {$methodName} to {$successCount} {$targetName}.";
        if ($failCount > 0) {
            $message .= " Failed or skipped for {$failCount} {$targetName} (missing contact info or API error).";
        }

        return redirect()->route('broadcast.index', ['target' => $target])->with('success', $message);
    }
}
