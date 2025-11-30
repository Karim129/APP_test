<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        // Log API access attempt
        $this->logApiAccess($request, $user?->id, $permission);

        // Check if user is authenticated
        if (!$user) {
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required',
                    'timestamp' => now()->toIso8601String()
                ]
            ], 401);
        }

        // Check if user has the required permission
        if (!$user->hasPermission($permission)) {
            // Log unauthorized access attempt
            Log::warning('Unauthorized access attempt', [
                'user_id' => $user->id,
                'permission' => $permission,
                'endpoint' => $request->path(),
                'method' => $request->method()
            ]);

            return response()->json([
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'Insufficient permissions to access this resource',
                    'timestamp' => now()->toIso8601String()
                ]
            ], 403);
        }

        return $next($request);
    }

    /**
     * Log API access attempts.
     *
     * @param Request $request
     * @param int|null $userId
     * @param string $permission
     * @return void
     */
    protected function logApiAccess(Request $request, ?int $userId, string $permission): void
    {
        AuditLog::create([
            'type' => 'api_access',
            'user_id' => $userId,
            'operation' => 'access_attempt',
            'data' => json_encode([
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'permission' => $permission,
                'timestamp' => now()->toIso8601String()
            ]),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'severity' => 'info'
        ]);
    }
}
