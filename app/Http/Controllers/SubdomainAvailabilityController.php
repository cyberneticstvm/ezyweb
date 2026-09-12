<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubdomainAvailabilityRequest;
use App\Tenancy\SubdomainService;
use Illuminate\Http\JsonResponse;

class SubdomainAvailabilityController extends Controller
{
    public function __invoke(SubdomainAvailabilityRequest $request, SubdomainService $subdomains): JsonResponse
    {
        $subdomain = $request->string('subdomain')->toString();
        $valid = $subdomains->isValidFormat($subdomain);
        $reserved = $subdomains->isReserved($subdomain);
        $available = $subdomains->isAvailable($subdomain);

        return response()->json([
            'subdomain' => $subdomain,
            'hostname' => $subdomains->hostname($subdomain),
            'available' => $available,
            'message' => match (true) {
                ! $valid => 'Use 3–40 letters, numbers, or single hyphens.',
                $reserved => 'This address is reserved by Ezyweb.',
                ! $available => 'This address is already in use.',
                default => 'This address is available.',
            },
        ]);
    }
}
