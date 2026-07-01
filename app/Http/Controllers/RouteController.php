<?php

namespace App\Http\Controllers;

use App\Services\RoutingService;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    public function __invoke(Request $request, RoutingService $routing)
    {
        $data = $request->validate([
            'from_lat' => ['required', 'numeric', 'between:-90,90'],
            'from_lng' => ['required', 'numeric', 'between:-180,180'],
            'to_lat' => ['required', 'numeric', 'between:-90,90'],
            'to_lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        return response()->json($routing->route(
            (float) $data['from_lat'],
            (float) $data['from_lng'],
            (float) $data['to_lat'],
            (float) $data['to_lng'],
        ));
    }
}
