<?php

namespace Modules\Stopit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Stopit\Models\Account;

class TenantSwitcherController extends Controller
{
    /**
     * Show the tenant switcher page.
     */
    public function index()
    {
        $user = Auth::user();

        if ( ! $user) {
            return redirect()->route('filament.admin.auth.login');
        }

        $accounts = $user->accounts()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('stopit::tenant-switcher', [
            'accounts'      => $accounts,
            'currentDomain' => request()->getHost(),
        ]);
    }

    /**
     * Switch to a specific tenant.
     */
    public function switch(Request $request, $accountId)
    {
        $user = Auth::user();

        if ( ! $user) {
            return redirect()->route('filament.admin.auth.login');
        }

        // Verify user has access to this account
        $account = $user->accounts()
            ->where('accounts.id', $accountId)
            ->where('is_active', true)
            ->first();

        if ( ! $account) {
            return redirect()
                ->back()
                ->with('error', 'You do not have access to this workspace.');
        }

        // Build the tenant URL
        if ($account->domain) {
            $protocol   = $request->secure() ? 'https' : 'http';
            $baseDomain = config('app.base_domain', 'stopit.dev');
            $tenantUrl  = "{$protocol}://{$account->domain}.{$baseDomain}/admin";

            return redirect()->away($tenantUrl);
        }

        // Fallback to main domain if no subdomain configured
        return redirect()
            ->route('filament.admin.pages.dashboard')
            ->with('error', 'This workspace does not have a domain configured.');
    }
}
