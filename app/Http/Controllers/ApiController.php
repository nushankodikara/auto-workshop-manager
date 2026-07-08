<?php

namespace App\Http\Controllers;

use App\Models\JobCard;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    /**
     * Get the public status of a ticket.
     */
    public function getTicketStatus(Request $request)
    {
        // Handle OPTIONS request for CORS preflight
        if ($request->isMethod('options')) {
            return response()->json([], 200)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, Authorization');
        }

        $vehicleNumber = $request->input('vehicle_number');
        $phone = $request->input('phone');
        $ticketId = $request->input('ticket_id');

        if (empty($phone) || (empty($vehicleNumber) && empty($ticketId))) {
            return response()->json([
                'error' => 'Missing required parameters. Please provide registered mobile number and either vehicle license plate or ticket ID.'
            ], 400)->header('Access-Control-Allow-Origin', '*');
        }

        $inputPhone = preg_replace('/[^0-9]/', '', $phone);
        $jobCard = null;

        if (!empty($vehicleNumber)) {
            $cleanPlateInput = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $vehicleNumber));

            // Query vehicles matching the plate number
            $vehicles = Vehicle::with('client')
                ->where(function($q) use ($cleanPlateInput) {
                    $q->where('plate_number', 'like', '%' . substr($cleanPlateInput, -4) . '%');
                })
                ->get();

            if ($vehicles->isEmpty()) {
                $vehicles = Vehicle::with('client')->get();
            }

            $matchingVehicle = null;
            foreach ($vehicles as $vehicle) {
                $cleanDbPlate = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $vehicle->plate_number));
                if ($cleanDbPlate === $cleanPlateInput) {
                    if ($vehicle->client) {
                        $cleanDbPhone = preg_replace('/[^0-9]/', '', $vehicle->client->phone);
                        $phoneMatched = ($cleanDbPhone === $inputPhone) || 
                                        (strlen($cleanDbPhone) >= 9 && strlen($inputPhone) >= 9 && substr($cleanDbPhone, -9) === substr($inputPhone, -9));
                        if ($phoneMatched) {
                            $matchingVehicle = $vehicle;
                            break;
                        }
                    }
                }
            }

            if (!$matchingVehicle) {
                return response()->json([
                    'error' => 'No vehicle matching plate number and registered phone found.'
                ], 404)->header('Access-Control-Allow-Origin', '*');
            }

            // Find the latest job card for this vehicle
            $jobCard = JobCard::where('vehicle_id', $matchingVehicle->id)
                ->with(['vehicle.client', 'shop'])
                ->orderBy('id', 'desc')
                ->first();

            if (!$jobCard) {
                return response()->json([
                    'error' => 'No active repair job found for this vehicle.'
                ], 404)->header('Access-Control-Allow-Origin', '*');
            }
        } else {
            // Find by ticket_id + phone (legacy/fallback support)
            $jobCard = JobCard::where(function ($query) use ($ticketId) {
                    $query->where('card_number', $ticketId)
                          ->orWhere('id', $ticketId);
                })
                ->with(['vehicle.client', 'shop'])
                ->first();

            if (!$jobCard || !$jobCard->vehicle || !$jobCard->vehicle->client) {
                return response()->json([
                    'error' => 'Ticket not found.'
                ], 404)->header('Access-Control-Allow-Origin', '*');
            }

            $clientPhone = preg_replace('/[^0-9]/', '', $jobCard->vehicle->client->phone);
            $matched = ($clientPhone === $inputPhone) || 
                       (strlen($clientPhone) >= 9 && strlen($inputPhone) >= 9 && substr($clientPhone, -9) === substr($inputPhone, -9));

            if (!$matched) {
                return response()->json([
                    'error' => 'Unauthorized or phone number mismatch.'
                ], 403)->header('Access-Control-Allow-Origin', '*');
            }
        }

        // Map status to numeric stage (1 to 5)
        $statusMapping = [
            'received-vehicle' => 1,
            'blocked' => 2,
            'on-going' => 3,
            'testing' => 4,
            'waiting-to-pickup' => 5,
        ];
        $stage = $statusMapping[$jobCard->status] ?? 1;

        return response()->json([
            'ticket_id' => $jobCard->id,
            'card_number' => $jobCard->card_number,
            'state' => $jobCard->status,
            'stage' => $stage,
            'last_email' => $jobCard->last_email ?: 'No Email alerts sent yet.',
            'last_message' => $jobCard->last_sms ?: 'No SMS alerts sent yet.',
            'vehicle' => ($jobCard->vehicle->make . ' ' . $jobCard->vehicle->model),
            'customer' => $jobCard->vehicle->client->name,
            'regNo' => $jobCard->vehicle->plate_number,
            'registered' => $jobCard->created_at ? $jobCard->created_at->format('F d, Y') : '',
            'issue' => $jobCard->notes ?: 'No diagnostic notes available.',
        ])->header('Access-Control-Allow-Origin', '*')
          ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
          ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With, Authorization');
    }
}
