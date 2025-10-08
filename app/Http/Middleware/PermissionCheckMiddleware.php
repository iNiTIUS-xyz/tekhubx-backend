<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use App\Utils\ServerErrorMask;
use App\Helpers\ApiResponseHelper;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PermissionCheckMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permissionSlugs): Response
    {
        // $user = Auth::user();

        // if (!$user || !$user->role_id) {
        //     return response()->json([
        //         'errors' => ['User does not have a valid role.'],
        //         'message' => 'Forbidden',
        //     ], 403);
        // }

        // $permissions = $user->permissions->permissions ?? [];

        // $permissions = is_string($permissions) ? json_decode($permissions, true) : $permissions;

        // $requiredPermissions = explode(',', $permissionSlugs);

        // foreach ($requiredPermissions as $requiredPermission) {
        //     if (in_array($requiredPermission, $permissions)) {
        //         return $next($request);
        //     }
        // }

        // return response()->json([
        //     'errors' => ['Permission denied.'],
        //     'message' => 'Forbidden',
        // ], 403);
        // $permissions = $user->permissions->permissions ?? [];
        // $permissions = is_string($permissions) ? json_decode($permissions, true) : $permissions;

        // This now allows access to all users regardless of permissions
        return $next($request);
    }
}
