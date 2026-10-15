<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureIdempotentOfflineRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = trim((string) $request->header('X-Offline-Request-Id'));
        $userId = $request->user()?->getAuthIdentifier();

        if ($requestId === '' || ! $userId || ! in_array($request->method(), ['POST', 'PUT', 'PATCH'], true)) {
            return $next($request);
        }

        if (strlen($requestId) > 100) {
            return response()->json(['message' => 'ID permintaan offline tidak valid.'], 422);
        }

        $fingerprint = hash('sha256', implode('|', [
            $request->method(),
            '/'.$request->path(),
            json_encode($request->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $request->getContent(),
        ]));

        return Cache::lock("offline-request:{$userId}:{$requestId}", 30)->block(10, function () use ($request, $next, $requestId, $userId, $fingerprint) {
            $stored = DB::table('offline_requests')
                ->where('user_id', $userId)
                ->where('request_id', $requestId)
                ->first();

            if ($stored) {
                if (! hash_equals($stored->fingerprint, $fingerprint)) {
                    return response()->json(['message' => 'ID permintaan offline sudah digunakan untuk data yang berbeda.'], 409);
                }

                return response($stored->response_body, $stored->response_status)
                    ->header('Content-Type', $stored->response_content_type ?: 'application/json');
            }

            return DB::transaction(function () use ($request, $next, $requestId, $userId, $fingerprint) {
                $response = $next($request);

                DB::table('offline_requests')->insert([
                    'user_id' => $userId,
                    'request_id' => $requestId,
                    'fingerprint' => $fingerprint,
                    'response_status' => $response->getStatusCode(),
                    'response_body' => $response->getContent(),
                    'response_content_type' => $response->headers->get('Content-Type'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return $response;
            });
        });
    }
}
