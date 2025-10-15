<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\ServiceOption;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'service_option_id' => ['required','integer','exists:service_options,id'],
            'date' => ['required','date_format:Y-m-d'],
            'time' => ['required','date_format:H:i'],
            'name' => ['required','string','max:255'],
            'phone' => ['required','string','max:50'],
        ]);

        $option = ServiceOption::findOrFail($data['service_option_id']);
        $startMsk = Carbon::createFromFormat('Y-m-d H:i', $data['date'].' '.$data['time'], 'Europe/Moscow');
        if ($startMsk->isSunday()) {
            throw ValidationException::withMessages(['time' => 'Нельзя бронировать на воскресенье']);
        }
        if ($startMsk->copy()->lt($startMsk->copy()->setTime(10,0)) || $startMsk->copy()->gte($startMsk->copy()->setTime(20,0))) {
            throw ValidationException::withMessages(['time' => 'Бронирование доступно 10:00–20:00']);
        }

        $duration = $option->duration_minutes + 30;
        $endMsk = $startMsk->copy()->addMinutes($duration);
        $startsAtUtc = $startMsk->copy()->tz('UTC');
        $endsAtUtc = $endMsk->copy()->tz('UTC');

        DB::transaction(function () use ($option, $startsAtUtc, $endsAtUtc, $data) {
            $conflict = Booking::query()
                ->where('service_option_id', $option->id)
                ->where('status', 'active')
                ->where('starts_at', '<', $endsAtUtc)
                ->where('ends_at', '>', $startsAtUtc)
                ->lockForUpdate()
                ->exists();

            if ($conflict) {
                throw ValidationException::withMessages(['time' => 'Слот уже занят']);
            }

            Booking::create([
                'service_option_id' => $option->id,
                'customer_name' => $data['name'],
                'customer_phone' => $data['phone'],
                'starts_at' => $startsAtUtc,
                'ends_at' => $endsAtUtc,
                'status' => 'active',
            ]);
        });

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        return back()->with('success', true);
    }
}
