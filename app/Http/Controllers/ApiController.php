<?php

namespace App\Http\Controllers;

use App\Models\JobCard;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    /**
     * Get the public status of a ticket.
     */
    public function getTicketStatus(Request $request)
    {
        $ticketId = $request->input('ticket_id');
        $phone = $request->input('phone');

        if (empty($ticketId) || empty($phone)) {
            return response()->json([
                'error' => 'Missing ticket_id or phone parameters.'
            ], 400);
        }

        // Find the job card matching the card_number or ID
        $jobCard = JobCard::where(function ($query) use ($ticketId) {
                $query->where('card_number', $ticketId)
                      ->orWhere('id', $ticketId);
            })
            ->with('vehicle.client')
            ->first();

        if (!$jobCard || !$jobCard->vehicle || !$jobCard->vehicle->client) {
            return response()->json([
                'error' => 'Ticket not found.'
            ], 404);
        }

        // Normalize phone numbers to compare digits only
        $clientPhone = preg_replace('/[^0-9]/', '', $jobCard->vehicle->client->phone);
        $inputPhone = preg_replace('/[^0-9]/', '', $phone);

        // Security check: Match exactly or check if last 9 digits are the same
        $matched = ($clientPhone === $inputPhone) || 
                   (strlen($clientPhone) >= 9 && strlen($inputPhone) >= 9 && substr($clientPhone, -9) === substr($inputPhone, -9));

        if (!$matched) {
            return response()->json([
                'error' => 'Unauthorized or phone number mismatch.'
            ], 403);
        }

        return response()->json([
            'ticket_id' => $jobCard->id,
            'card_number' => $jobCard->card_number,
            'state' => $jobCard->status,
            'last_email' => $jobCard->last_email,
            'last_message' => $jobCard->last_sms,
        ]);
    }
}
