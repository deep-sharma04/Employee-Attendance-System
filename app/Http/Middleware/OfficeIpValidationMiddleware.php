<?php

namespace App\Http\Middleware;

use App\Services\Attendance\IpValidationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OfficeIpValidationMiddleware
{
    public function __construct(
        protected IpValidationService $ipService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $clientIp = $request->ip();

        if (!$this->ipService->isIpAllowed($clientIp)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attendance actions are restricted to approved office networks. Your current IP (' . $clientIp . ') is unauthorized.',
                ], 403);
            }

            return response()->view('errors.unauthorized-ip', [
                'clientIp' => $clientIp,
            ], 403);
        }

        return $next($request);
    }
}
