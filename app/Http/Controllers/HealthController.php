<?php

namespace App\Http\Controllers;

use App\Health\HealthChecks;
use Illuminate\Http\JsonResponse;
use Throwable;

/**
 * Monitoring endpoints (Phase 10 §26). Liveness says the process answers; readiness says the
 * dependencies answer. Neither exposes configuration, versions, hosts or error details.
 */
class HealthController extends Controller
{
    public function live(): JsonResponse
    {
        return $this->respond(['status' => 'ok'], 200);
    }

    public function ready(HealthChecks $checks): JsonResponse
    {
        $checks = [
            'database' => $this->check(fn () => $checks->database()),
            'cache' => $this->check(fn () => $checks->cache()),
            'queue' => $this->check(fn () => $checks->queue()),
        ];

        $healthy = ! in_array('fail', $checks, true);

        return $this->respond(['status' => $healthy ? 'ok' : 'degraded', 'checks' => $checks], $healthy ? 200 : 503);
    }

    protected function check(\Closure $probe): string
    {
        try {
            $probe();

            return 'ok';
        } catch (Throwable $exception) {
            report($exception);

            return 'fail';
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function respond(array $payload, int $status): JsonResponse
    {
        return response()->json($payload, $status)->withHeaders(['Cache-Control' => 'no-store, private']);
    }
}
