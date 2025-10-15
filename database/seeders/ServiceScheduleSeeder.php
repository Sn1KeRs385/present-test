<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceSchedule;
use Illuminate\Database\Seeder;

class ServiceScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $services = Service::all();
        foreach ($services as $service) {
            for ($dow = 1; $dow <= 6; $dow++) {
                ServiceSchedule::query()->updateOrCreate(
                    [
                        'service_id' => $service->id,
                        'day_of_week' => $dow,
                    ],
                    [
                        'start_time' => '10:00:00',
                        'end_time' => '20:00:00',
                    ]
                );
            }
        }
    }
}
