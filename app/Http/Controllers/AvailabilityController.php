<?php

namespace App\Http\Controllers;

use App\Models\ServiceOption;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function __construct(private AvailabilityService $availability) {}

    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'service_option_id' => ['required','integer','exists:service_options,id'],
            'date' => ['required','date_format:Y-m-d'],
        ]);

        $option = ServiceOption::with('service.schedules')->findOrFail($data['service_option_id']);
        $dateMsk = Carbon::createFromFormat('Y-m-d', $data['date'], 'Europe/Moscow');
        $slots = $this->availability->getAvailableSlots($dateMsk, $option);
        return response()->json(['slots' => $slots]);
    }
}
