<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\EquipmentBookingRequest;
use App\Models\EquipmentBooking;
use App\Models\Project;
use App\Services\Project\EquipmentBookingService;
use Illuminate\Http\RedirectResponse;
use Exception;

class EquipmentBookingController extends Controller
{
    public function __construct(protected EquipmentBookingService $bookingService)
    {
    }

    public function store(EquipmentBookingRequest $request, Project $project): RedirectResponse
    {
        $data = $request->validated();

        try {
            $result = $this->bookingService->book($project, $data['inventory_id'], $data['qty']);
        } catch (Exception $e) {
            return redirect()->route('projects.show', $project)->with('error', $e->getMessage());
        }

        $message = $result['warning'] ?? 'Booking alat tersimpan.';

        return redirect()->route('projects.show', $project)->with('success', $message);
    }

    public function destroy(Project $project, EquipmentBooking $booking): RedirectResponse
    {
        abort_unless($booking->project_id === $project->id, 404);

        try {
            $this->bookingService->cancel($booking);
        } catch (Exception $e) {
            return redirect()->route('projects.show', $project)->with('error', $e->getMessage());
        }

        return redirect()->route('projects.show', $project)->with('success', 'Booking dibatalkan.');
    }
}
