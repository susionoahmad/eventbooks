<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response;

class CheckTrialExpiration
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        if ($user && $user->tenant) {
            $tenant = $user->tenant;
            
            // Check if plan is trial and trial_ends_at is in the past
            if ($tenant->subscription_plan === 'trial' && $tenant->trial_ends_at) {
                if (Carbon::now()->gt(Carbon::parse($tenant->trial_ends_at))) {
                    return response()->json([
                        'message' => 'Masa trial 30 hari Anda telah berakhir. Silakan hubungi administrator untuk memperbarui langganan.',
                        'trial_expired' => true
                    ], 403);
                }
            }
        }

        return $next($request);
    }
}
