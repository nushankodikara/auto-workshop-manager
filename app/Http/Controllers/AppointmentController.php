<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Vehicle;
use App\Models\JobCard;
use App\Services\FitSmsService;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    protected FitSmsService $smsService;
    protected EmailService  $emailService;

    public function __construct(FitSmsService $smsService, EmailService $emailService)
    {
        $this->smsService   = $smsService;
        $this->emailService = $emailService;
    }

    // ── Index ─────────────────────────────────────────────────────

    /**
     * Display paginated appointments with optional filters.
     */
    public function index(Request $request)
    {
        $status    = $request->input('status');
        $dateFrom  = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo    = $request->input('date_to',   now()->endOfMonth()->toDateString());

        $appointments = Appointment::with(['client', 'vehicle', 'jobCard'])
            ->when($status, fn($q) => $q->where('status', $status))
            ->whereBetween('appointment_date', [$dateFrom, $dateTo])
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->paginate(25)
            ->appends($request->query());

        $clients = Client::with('vehicles')->orderBy('name')->get();

        // Count today's + tomorrow's un-notified active appointments for the badge
        $pendingNotifications = Appointment::whereIn('status', ['pending', 'confirmed'])
            ->whereIn('appointment_date', [now()->toDateString(), now()->addDay()->toDateString()])
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereDate('appointment_date', now()->toDateString())
                       ->where('notified_morning', false);
                })->orWhere(function ($q2) {
                    $q2->whereDate('appointment_date', now()->addDay()->toDateString())
                       ->where('notified_day_prior', false);
                });
            })
            ->count();

        return view('appointments.index', compact(
            'appointments', 'clients', 'status', 'dateFrom', 'dateTo', 'pendingNotifications'
        ));
    }

    // ── Store ─────────────────────────────────────────────────────

    /**
     * Create a new appointment and send creation notification.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id'          => 'required|exists:clients,id',
            'vehicle_id'         => 'nullable|exists:vehicles,id',
            'appointment_date'   => 'required|date|after_or_equal:today',
            'appointment_time'   => 'required|date_format:H:i',
            'service_type'       => 'required|string|max:255',
            'estimated_duration' => 'nullable|integer|min:15|max:480',
            'notes'              => 'nullable|string',
            'status'             => 'nullable|in:pending,confirmed',
        ]);

        $data['created_by']          = Auth::id();
        $data['status']              = $data['status'] ?? 'pending';
        $data['estimated_duration']  = $data['estimated_duration'] ?? 60;

        $appointment = Appointment::create($data);
        $appointment->load('client', 'vehicle');

        // Send creation notification
        $this->sendCreationNotification($appointment);

        return redirect()->route('appointments.index')
            ->with('success', "Appointment booked for {$appointment->client->name} on "
                . Carbon::parse($appointment->appointment_date)->format('D, d M Y')
                . " at {$appointment->appointment_time}.");
    }

    // ── Update ────────────────────────────────────────────────────

    /**
     * Update appointment details or status.
     */
    public function update(Request $request, Appointment $appointment)
    {
        $data = $request->validate([
            'client_id'          => 'required|exists:clients,id',
            'vehicle_id'         => 'nullable|exists:vehicles,id',
            'appointment_date'   => 'required|date',
            'appointment_time'   => 'required|date_format:H:i',
            'service_type'       => 'required|string|max:255',
            'estimated_duration' => 'nullable|integer|min:15|max:480',
            'notes'              => 'nullable|string',
            'status'             => 'required|in:pending,confirmed,completed,no-show,cancelled',
        ]);

        $appointment->update($data);

        return back()->with('success', 'Appointment updated successfully.');
    }

    // ── Destroy ───────────────────────────────────────────────────

    /**
     * Delete an appointment.
     */
    public function destroy(Appointment $appointment)
    {
        $appointment->delete();
        return redirect()->route('appointments.index')
            ->with('success', 'Appointment deleted.');
    }

    // ── Convert to Job Card ───────────────────────────────────────

    /**
     * Convert an appointment into a Job Card.
     * Requires a vehicle to be linked.
     */
    public function convertToJobCard(Request $request, Appointment $appointment)
    {
        if (! $appointment->vehicle_id) {
            return back()->withErrors(['vehicle' => 'A vehicle must be linked to this appointment before converting to a Job Card.']);
        }

        if ($appointment->job_card_id) {
            return redirect()->route('job-cards.show', $appointment->job_card_id)
                ->with('info', 'This appointment is already linked to a Job Card.');
        }

        $data = $request->validate([
            'shop_id' => 'required|exists:shops,id',
        ]);

        $jobCard = DB::transaction(function () use ($appointment, $data) {
            $jobCard = JobCard::create([
                'vehicle_id'     => $appointment->vehicle_id,
                'shop_id'        => $data['shop_id'],
                'notes'          => $appointment->notes,
                'estimated_cost' => 0.00,
                'status'         => 'received-vehicle',
            ]);

            $appointment->update([
                'job_card_id' => $jobCard->id,
                'status'      => 'confirmed',
            ]);

            return $jobCard;
        });

        return redirect()->route('job-cards.show', $jobCard->id)
            ->with('success', 'Appointment converted to Job Card #' . str_pad($jobCard->id, 4, '0', STR_PAD_LEFT) . ' successfully.');
    }

    // ── Morning Notifications (Manual Trigger) ────────────────────

    /**
     * Send reminder notifications for today's and tomorrow's active appointments.
     * Notification flags prevent duplicate sends if pressed multiple times on the same day.
     */
    public function sendMorningNotifications()
    {
        $today    = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();

        // ── Today's appointments (morning-of reminder) ──
        $todayAppointments = Appointment::with(['client', 'vehicle'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereDate('appointment_date', $today)
            ->where('notified_morning', false)
            ->get();

        // ── Tomorrow's appointments (day-prior reminder) ──
        $tomorrowAppointments = Appointment::with(['client', 'vehicle'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereDate('appointment_date', $tomorrow)
            ->where('notified_day_prior', false)
            ->get();

        $smsSent   = 0;
        $emailSent = 0;
        $failed    = 0;

        // Process today
        foreach ($todayAppointments as $appt) {
            [$s, $e, $f] = $this->sendReminderNotification($appt, 'today');
            $smsSent   += $s;
            $emailSent += $e;
            $failed    += $f;
            $appt->update(['notified_morning' => true]);
        }

        // Process tomorrow
        foreach ($tomorrowAppointments as $appt) {
            [$s, $e, $f] = $this->sendReminderNotification($appt, 'tomorrow');
            $smsSent   += $s;
            $emailSent += $e;
            $failed    += $f;
            $appt->update(['notified_day_prior' => true]);
        }

        $totalAppts = $todayAppointments->count() + $tomorrowAppointments->count();

        $msg = $totalAppts === 0
            ? 'No pending notifications to send today. All appointments are already notified or there are none scheduled.'
            : "Notifications sent for {$totalAppts} appointment(s): {$smsSent} SMS, {$emailSent} emails."
              . ($failed > 0 ? " {$failed} failed (missing contact info or API error)." : '');

        return redirect()->route('appointments.index')->with('success', $msg);
    }

    // ── Private helpers ───────────────────────────────────────────

    /**
     * Send the on-creation confirmation notification.
     */
    private function sendCreationNotification(Appointment $appt): void
    {
        if (! $appt->client) return;

        $dateStr = Carbon::parse($appt->appointment_date)->format('D, d M Y');
        $timeStr = $appt->appointment_time;

        try {
            if ($appt->client->phone) {
                $phone = Appointment::normalisePhone($appt->client->phone);
                $sms   = "Your appointment at Total Drive Care is confirmed for {$dateStr} at {$timeStr}. Service: {$appt->service_type}. See you then! – TDC";
                $this->smsService->sendSms($phone, $sms);
            }

            if ($appt->client->email) {
                $subject = "Appointment Confirmed – Total Drive Care";
                $body    = "Dear {$appt->client->name},\n\nYour vehicle service appointment has been booked.\n\n"
                         . "Date: {$dateStr}\nTime: {$timeStr}\nService: {$appt->service_type}\n"
                         . ($appt->vehicle ? "Vehicle: {$appt->vehicle->make} {$appt->vehicle->model} ({$appt->vehicle->plate_number})\n" : '')
                         . "\nPlease arrive 10 minutes early. If you need to reschedule, call us.\n\nTotal Drive Care Solutions";
                $this->emailService->sendEmail($appt->client->email, $subject, $body);
            }

            $appt->update(['notified_on_create' => true]);
        } catch (\Exception $e) {
            Log::error("Appointment creation notification failed for appointment #{$appt->id}: " . $e->getMessage());
        }
    }

    /**
     * Send a reminder notification (today or tomorrow).
     * Returns [smsCount, emailCount, failCount].
     */
    private function sendReminderNotification(Appointment $appt, string $when): array
    {
        if (! $appt->client) return [0, 0, 1];

        $dateStr = Carbon::parse($appt->appointment_date)->format('D, d M Y');
        $timeStr = $appt->appointment_time;
        $when    = $when === 'today' ? 'TODAY' : 'TOMORROW (' . $dateStr . ')';

        $smsSent   = 0;
        $emailSent = 0;
        $failed    = 0;

        try {
            if ($appt->client->phone) {
                $phone = Appointment::normalisePhone($appt->client->phone);
                $sms   = "Reminder: Your vehicle service at Total Drive Care is {$when} at {$timeStr}. Service: {$appt->service_type}. Please arrive 10 mins early. – TDC";
                $this->smsService->sendSms($phone, $sms);
                $smsSent++;
            }

            if ($appt->client->email) {
                $subject = "Service Appointment {$when} – Total Drive Care";
                $body    = "Dear {$appt->client->name},\n\nThis is a reminder that your vehicle service appointment is scheduled for {$when} at {$timeStr}.\n\n"
                         . "Service: {$appt->service_type}\n"
                         . ($appt->vehicle ? "Vehicle: {$appt->vehicle->make} {$appt->vehicle->model} ({$appt->vehicle->plate_number})\n" : '')
                         . "\nPlease arrive 10 minutes early. To reschedule, call us.\n\nTotal Drive Care Solutions";
                $this->emailService->sendEmail($appt->client->email, $subject, $body);
                $emailSent++;
            }
        } catch (\Exception $e) {
            $failed++;
            Log::error("Appointment reminder failed for appointment #{$appt->id}: " . $e->getMessage());
        }

        return [$smsSent, $emailSent, $failed];
    }
}
