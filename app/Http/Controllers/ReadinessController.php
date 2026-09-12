<?php

namespace App\Http\Controllers;

use App\Monitoring\ReadinessProbe;
use Illuminate\Http\JsonResponse;

class ReadinessController extends Controller
{
    public function __invoke(ReadinessProbe $probe): JsonResponse
    {
        $result = $probe->run();

        return response()->json([
            'status' => $result['ready'] ? 'ready' : 'unavailable',
            'checks' => $result['checks'],
            'timestamp' => now()->toIso8601String(),
        ], $result['ready'] ? 200 : 503);
    }
}
