<?php

namespace Modules\Stopit\Providers\src\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class EnforceTenantAccess
{
    /**
     * Ensure the authenticated user has access to the current tenant.
     *
     * @param Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = Session::get('tenant_id');

        // If we're on a tenant subdomain
        if ($tenantId) {
            $user = Auth::user();

            // User must be authenticated
            if ( ! $user) {
                return redirect()->route('filament.admin.auth.login');
            }

            // Check if user has access to this tenant
            $hasAccess = $user->accounts()
                ->where('accounts.id', $tenantId)
                ->exists();

            if ( ! $hasAccess) {
                // User doesn't have access to this tenant
                Auth::logout();
                Session::flush();

                return redirect()
                    ->route('filament.admin.auth.login')
                    ->with('error', 'You do not have access to this workspace.');
            }
        }

        return $next($request);
    }
}
