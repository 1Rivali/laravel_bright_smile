<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UserType
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|array  $types
     */
    public function handle(Request $request, Closure $next, ...$types)
    {
        $user_type = Auth::guard('sanctum')->user()->user_type;

        if (in_array($user_type, $types)) {
            return $next($request);
        }

        return response()->json(["message" => "Unauthorized"], 401);
    }
}
