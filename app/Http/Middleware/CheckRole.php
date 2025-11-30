<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Log API access attempt
        $this->logApiAccess($request, $user?->id, $roles);

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

        // Check if user has any of the required roles
        $hasRole = false;
        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                $hasRole = true;
                break;
            }
        }

        if (!$hasRole) {
            // Log unauthorized access attempt
            Log::warning('Unauthorized access attempt - missing role', [
                'user_id' => $user->id,
                'required_roles' => $roles,
                'user_roles' => $user->roles->pluck('name')->toArray(),
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
     * @param array $roles
     * @return void
     */
    protected function logApiAccess(Request $request, ?int $userId, array $roles): void
    {
        AuditLog::create([
            'type' => 'api_access',
            'user_id' => $userId,
            'operation' => 'access_attempt',
            'data' => json_encode([
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'required_roles' => $roles,
                'timestamp' => now()->toIso8601String()
            ]),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'severity' => 'info'
        ]);
    }
}
