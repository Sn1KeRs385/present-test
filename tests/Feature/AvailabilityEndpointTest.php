<?php

use App\Models\Service;
use App\Models\ServiceOption;
use App\Models\ServiceSchedule;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\getJson;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('availability endpoint responds', function () {
    $service = Service::create(['name' => 'S', 'slug' => Str::uuid()]);
    $option = ServiceOption::create(['service_id' => $service->id, 'name' => '30', 'duration_minutes' => 30, 'slot_step_minutes' => 30, 'is_active' => true]);
    ServiceSchedule::create(['service_id' => $service->id, 'day_of_week' => 4, 'start_time' => '10:00:00', 'end_time' => '20:00:00']);
    $response = getJson(route('availability', ['service_option_id' => $option->id, 'date' => '2025-10-16']));
    $response->assertOk();
});

it('returns available slots for atv 30 on 2025-10-16', function () {
    $service = Service::create(['name' => 'S2', 'slug' => Str::uuid()]);
    $option = ServiceOption::create(['service_id' => $service->id, 'name' => '30', 'duration_minutes' => 30, 'slot_step_minutes' => 30, 'is_active' => true]);
    ServiceSchedule::create(['service_id' => $service->id, 'day_of_week' => 4, 'start_time' => '10:00:00', 'end_time' => '20:00:00']);
    $response = getJson(route('availability', ['service_option_id' => $option->id, 'date' => '2025-10-16']));
    $response->assertOk();
    $slots = $response->json('slots');
    expect($slots)->toBeArray();
    expect($slots)->not->toContain('13:00');
    expect($slots)->not->toContain('16:00');
});

it('returns empty slots on sunday', function () {
    $service = Service::create(['name' => 'S3', 'slug' => Str::uuid()]);
    $option = ServiceOption::create(['service_id' => $service->id, 'name' => '30', 'duration_minutes' => 30, 'slot_step_minutes' => 30, 'is_active' => true]);
    $response = getJson(route('availability', ['service_option_id' => $option->id, 'date' => '2025-10-19']));
    $response->assertOk();
    expect($response->json('slots'))->toBeArray()->toHaveCount(0);
});

it('does not include late start beyond 20:00 window', function () {
    $service = Service::create(['name' => 'S4', 'slug' => Str::uuid()]);
    $option = ServiceOption::create(['service_id' => $service->id, 'name' => '30', 'duration_minutes' => 30, 'slot_step_minutes' => 30, 'is_active' => true]);
    ServiceSchedule::create(['service_id' => $service->id, 'day_of_week' => 4, 'start_time' => '10:00:00', 'end_time' => '20:00:00']);
    $response = getJson(route('availability', ['service_option_id' => $option->id, 'date' => '2025-10-16']));
    $response->assertOk();
    $slots = $response->json('slots');
    expect($slots)->not->toContain('19:30');
});


