<?php

namespace App\Http\Middleware;

use App\Models\AdminActionLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogAdminActions
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldLog($request)) {
            return $next($request);
        }

        $snapshot = $this->buildSnapshot($request);

        try {
            $response = $next($request);
            $this->storeLog($snapshot, $response->getStatusCode());

            return $response;
        } catch (Throwable $throwable) {
            $this->storeLog($snapshot, 500);

            throw $throwable;
        }
    }

    private function shouldLog(Request $request): bool
    {
        $role = session('user_role');

        return session('user_id')
            && in_array($role, ['admin', 'super_admin'], true)
            && ! $request->is('telescope*');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSnapshot(Request $request): array
    {
        $route = $request->route();

        return [
            'user_id' => (int) session('user_id'),
            'role' => (string) session('user_role'),
            'action' => $route?->getName() ?: $route?->getActionName() ?: $request->method().' '.$request->path(),
            'method' => $request->method(),
            'route_name' => $route?->getName(),
            'path' => $request->path(),
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_data' => $this->sanitizeRequestData($request),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sanitizeRequestData(Request $request): array
    {
        $data = $request->except([
            '_token',
            '_method',
            'password',
            'password_confirmation',
            'current_password',
        ]);

        foreach ($data as $key => $value) {
            if ($value instanceof UploadedFile) {
                $data[$key] = [
                    'original_name' => $value->getClientOriginalName(),
                    'mime_type' => $value->getClientMimeType(),
                    'size' => $value->getSize(),
                ];
            }
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $snapshot
     */
    private function storeLog(array $snapshot, int $statusCode): void
    {
        AdminActionLog::create([
            'user_id' => $snapshot['user_id'],
            'role' => $snapshot['role'],
            'action' => $snapshot['action'],
            'method' => $snapshot['method'],
            'route_name' => $snapshot['route_name'],
            'path' => $snapshot['path'],
            'url' => $snapshot['url'],
            'ip_address' => $snapshot['ip_address'],
            'user_agent' => $snapshot['user_agent'],
            'request_data' => $snapshot['request_data'],
            'response_status' => $statusCode,
        ]);
    }
}