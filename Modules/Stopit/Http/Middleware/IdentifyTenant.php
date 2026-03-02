<?php

namespace Modules\Stopit\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Modules\Stopit\Models\Account;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    /**
     * Handle an incoming request and identify the tenant from subdomain.
     *
     * @param Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host   = $request->getHost();
        $tenant = null;

        // Extract subdomain from host
        $parts = explode('.', $host);

        // If we have a subdomain (more than 2 parts, e.g., gitman.stopit.dev)
        if (count($parts) >= 3) {
            $subdomain = $parts[0];

            // Find tenant by domain
            $tenant = Account::where('domain', $subdomain)
                ->where('is_active', true)
                ->first();

            if ($tenant) {
                // Store tenant in session
                Session::put('tenant_id', $tenant->id);
                Session::put('tenant_domain', $tenant->domain);

                // Share tenant with views
                view()->share('currentTenant', $tenant);

                // Set tenant in request
                $request->attributes->set('tenant', $tenant);
            } else {
                // Invalid subdomain - show 404 or redirect
                return response()->view('errors.404-tenant', [], 404);
            }
        } else {
            // Main domain (stopit.dev) - clear tenant
            Session::forget(['tenant_id', 'tenant_domain']);
        }

        return $next($request);
    }
}
