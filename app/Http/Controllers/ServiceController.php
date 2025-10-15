<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceOption;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $services = Service::with(['options' => function($q){ $q->where('is_active', true)->orderBy('duration_minutes'); }])->get();

        $selectedOption = null;
        if ($request->integer('option')) {
            $selectedOption = ServiceOption::with('service')->find($request->integer('option'));
        }

        return Inertia::render('Services/Index', [
            'services' => $services,
            'selectedOption' => $selectedOption,
        ]);
    }
}
