<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SetTenantConnection
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->runningUnitTests()) {
            return $next($request);
        }

        $user = $request->user('sanctum') ?? $request->user();

        if ($user === null) {
            return $next($request);
        }

        $tenantId = $request->hasSession() ? $request->session()->get('tenant_id') : null;

        if ($tenantId === null) {
            $tenantId = DB::connection('landlord')
                ->table('tenant_users')
                ->where('user_id', $user->getAuthIdentifier())
                ->value('tenant_id');

            if ($tenantId === null) {
                if ($request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tiene un tenant asignado. Contacte al administrador.',
                        'error_code' => 'TENANT_INVALID',
                    ], 403);
                }
                Auth::logout();
                if ($request->hasSession()) {
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                }

                return redirect()->route('login')->withErrors([
                    'tenant' => 'No tiene un tenant asignado. Contacte al administrador.',
                ]);
            }

            if ($request->hasSession()) {
                $request->session()->put('tenant_id', $tenantId);
            }
        }

        $tenant = DB::connection('landlord')
            ->table('tenants')
            ->where('id', $tenantId)
            ->where('status', '1')
            ->first();

        if (! $tenant || empty($tenant->db_database)) {
            if ($request->hasSession()) {
                $request->session()->forget('tenant_id');
            }

            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant no configurado o inactivo. Contacte al administrador.',
                    'error_code' => 'TENANT_INVALID',
                ], 403);
            }

            return redirect()->route('login')->withErrors([
                'tenant' => 'Tenant no configurado o inactivo. Contacte al administrador.',
            ]);
        }

        Config::set('database.connections.tenant.database', $tenant->db_database);
        Config::set('database.connections.tenant.host', $tenant->db_host ?: config('database.connections.landlord.host'));
        Config::set('database.connections.tenant.username', $tenant->db_username ?: config('database.connections.landlord.username'));
        Config::set('database.connections.tenant.password', $tenant->db_password ?? config('database.connections.landlord.password'));

        DB::purge('tenant');
        DB::reconnect('tenant');

        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }
}
