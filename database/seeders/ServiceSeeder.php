<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceOption;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            'Поездка на квадроцикле' => [
                ['name' => '30 минут', 'duration' => 30],
                ['name' => '60 минут', 'duration' => 60],
            ],
            'Тур на эндуро' => [
                ['name' => '60 минут', 'duration' => 60],
                ['name' => '120 минут', 'duration' => 120],
            ],
        ];

        foreach ($services as $serviceName => $options) {
            $service = Service::query()->updateOrCreate(
                ['slug' => Str::slug($serviceName)],
                ['name' => $serviceName]
            );

            foreach ($options as $opt) {
                ServiceOption::query()->updateOrCreate(
                    [
                        'service_id' => $service->id,
                        'name' => $opt['name'],
                    ],
                    [
                        'duration_minutes' => $opt['duration'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
