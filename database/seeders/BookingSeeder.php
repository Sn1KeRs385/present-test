<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Service;
use App\Models\ServiceOption;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $date = fn(int $y, int $m, int $d, int $h, int $i) => Carbon::create($y, $m, $d, $h, $i, 0, 'Europe/Moscow');

        $svcAtv = Service::where('name', 'Поездка на квадроцикле')->first();
        $svcEnduro = Service::where('name', 'Тур на эндуро')->first();
        if (!$svcAtv || !$svcEnduro) return;

        $optAtv30 = ServiceOption::where('service_id', $svcAtv->id)->where('duration_minutes', 30)->first();
        $optAtv60 = ServiceOption::where('service_id', $svcAtv->id)->where('duration_minutes', 60)->first();
        $optEnd60 = ServiceOption::where('service_id', $svcEnduro->id)->where('duration_minutes', 60)->first();
        $optEnd120 = ServiceOption::where('service_id', $svcEnduro->id)->where('duration_minutes', 120)->first();

        $slots = [
            [$optAtv30, [
                [2025,10,16,13,0],
                [2025,10,16,16,0],
                [2025,10,17,10,0],
                [2025,10,17,11,0],
                [2025,10,17,13,0],
                [2025,10,17,18,0],
            ]],
            [$optAtv60, [
                [2025,10,16,10,0],
            ]],
            [$optEnd60, [
                [2025,10,16,10,0],
                [2025,10,16,11,30],
                [2025,10,16,18,30],
            ]],
            [$optEnd120, [
                [2025,10,17,14,0],
            ]],
        ];

        foreach ($slots as [$option, $times]) {
            if (!$option) continue;
            foreach ($times as [$y,$m,$d,$h,$i]) {
                $startMsk = $date($y,$m,$d,$h,$i);
                $duration = $option->duration_minutes + 30;
                $endMsk = $startMsk->clone()->addMinutes($duration);
                Booking::query()->updateOrCreate(
                    [
                        'service_option_id' => $option->id,
                        'starts_at' => $startMsk->clone()->utc(),
                    ],
                    [
                        'ends_at' => $endMsk->clone()->utc(),
                        'customer_name' => 'Demo',
                        'customer_phone' => '+70000000000',
                        'status' => 'active',
                    ]
                );
            }
        }
    }
}
