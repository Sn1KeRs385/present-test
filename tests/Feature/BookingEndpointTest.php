<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\ServiceOption;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

it('creates booking successfully when slot is free', function () {
    $this->seed();
    $service = Service::where('name', 'Поездка на квадроцикле')->firstOrFail();
    $option = ServiceOption::where('service_id', $service->id)->where('duration_minutes', 30)->firstOrFail();
    $payload = [
        'service_option_id' => $option->id,
        'date' => '2025-10-16',
        'time' => '12:00',
        'name' => 'Иван',
        'phone' => '+79998887766',
    ];
    $res = postJson(route('bookings.store'), $payload);
    $res->assertOk();
    $msk = Carbon::createFromFormat('Y-m-d H:i', '2025-10-16 12:00', 'Europe/Moscow');
    $exists = Booking::where('service_option_id', $option->id)
        ->where('starts_at', $msk->clone()->utc())->exists();
    expect($exists)->toBeTrue();
});

it('rejects booking on sunday', function () {
    $this->seed();
    $service = Service::where('name', 'Поездка на квадроцикле')->firstOrFail();
    $option = ServiceOption::where('service_id', $service->id)->where('duration_minutes', 30)->firstOrFail();
    $payload = [
        'service_option_id' => $option->id,
        'date' => '2025-10-19',
        'time' => '12:00',
        'name' => 'Иван',
        'phone' => '+79998887766',
    ];
    $res = postJson(route('bookings.store'), $payload);
    $res->assertStatus(422);
    expect($res->json('errors.time'))->toBeArray();
});

it('prevents race condition double booking', function () {
    $this->seed();
    $service = Service::where('name', 'Поездка на квадроцикле')->firstOrFail();
    $option = ServiceOption::where('service_id', $service->id)->where('duration_minutes', 60)->firstOrFail();
    $payload1 = [
        'service_option_id' => $option->id,
        'date' => '2025-10-16',
        'time' => '12:00',
        'name' => 'A',
        'phone' => '1',
    ];
    $payload2 = [
        'service_option_id' => $option->id,
        'date' => '2025-10-16',
        'time' => '12:00',
        'name' => 'B',
        'phone' => '2',
    ];

    $before = Booking::count();
    $res1 = postJson(route('bookings.store'), $payload1);
    $res2 = postJson(route('bookings.store'), $payload2);

    $codes = collect([$res1->getStatusCode(), $res2->getStatusCode()])->sort()->values()->all();
    $ok = $codes === [200, 422] || $codes === [422, 422];
    expect($ok)->toBeTrue();
    $after = Booking::count();
    expect($after - $before)->toBe(1);
});


