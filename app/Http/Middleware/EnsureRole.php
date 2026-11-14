<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        abort_unless($user && $user->role === $role && $user->account_status === 'active', 403);

        if ($user->must_change_password && ! $request->routeIs('account.password.*')) {
            return redirect()->route('account.password.edit')
                ->with('status', 'Ganti kata sandi sementara sebelum melanjutkan.');
        }

        return $next($request);
    }
}
