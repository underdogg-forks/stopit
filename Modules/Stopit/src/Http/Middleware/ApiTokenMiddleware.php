<?php

namespace Modules\Stopit\Providers\src\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Stopit\Providers\Services\ApplicationService;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenMiddleware
{
    public function __construct(
        private ApplicationService $applicationService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ( ! $token) {
            return response()->json([
                'message' => 'Invalid or missing API token',
            ], 401);
        }

        $application = $this->applicationService->validateToken($token);

        if ( ! $application) {
            return response()->json([
                'message' => 'Invalid or missing API token',
            ], 401);
        }

        $request->attributes->set('application', $application);

        return $next($request);
    }
}
