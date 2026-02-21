<?php

namespace App\Http\Middleware;

use App\Enums\RoleEnum;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->role !== RoleEnum::Admin) {
            return response()->json([
                'message' => 'Forbidden. Admin access required.',
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
