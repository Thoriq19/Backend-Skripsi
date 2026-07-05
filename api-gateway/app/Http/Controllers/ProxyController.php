<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ProxyController - API Gateway Request Proxy
 *
 * Routes incoming requests to the appropriate microservice.
 * Handles JWT validation, request forwarding, and response aggregation.
 */
class ProxyController extends Controller
{
    /**
     * Service URL mapping.
     */
    private array $services;

    public function __construct()
    {
        $this->services = [
            'auth'         => env('AUTH_SERVICE_URL', 'http://localhost:8001'),
            'property'     => env('PROPERTY_SERVICE_URL', 'http://localhost:8002'),
            'payment'      => env('PAYMENT_SERVICE_URL', 'http://localhost:8003'),
            'complaint'    => env('COMPLAINT_SERVICE_URL', 'http://localhost:8005'),
            'notification' => env('NOTIFICATION_SERVICE_URL', 'http://localhost:8007'),
        ];
    }

    // ─── Auth Service Proxies ──────────────────────────────────────

    /**
     * Proxy request to Auth Service.
     * ANY /api/auth/{path}
     */
    public function proxyAuth(Request $request, string $path = ''): JsonResponse
    {
        return $this->forwardRequest($request, 'auth', '/api/auth/' . $path);
    }

    /**
     * Proxy request to Auth Service (users).
     * ANY /api/users/{path}
     */
    public function proxyUsers(Request $request, string $path = ''): JsonResponse
    {
        return $this->forwardRequest($request, 'auth', '/api/users/' . $path);
    }

    // ─── Property Service Proxies ──────────────────────────────────

    /**
     * Proxy request to Property Service (kos).
     * ANY /api/kos/{path}
     */
    public function proxyKos(Request $request, string $path = ''): JsonResponse
    {
        return $this->forwardRequest($request, 'property', '/api/kos/' . $path);
    }

    /**
     * Proxy request to Property Service (kamar).
     * ANY /api/kamar/{path}
     */
    public function proxyKamar(Request $request, string $path = ''): JsonResponse
    {
        return $this->forwardRequest($request, 'property', '/api/kamar/' . $path);
    }

    /**
     * Proxy request to Property Service (aset).
     * ANY /api/aset/{path}
     */
    public function proxyAset(Request $request, string $path = ''): JsonResponse
    {
        return $this->forwardRequest($request, 'property', '/api/aset/' . $path);
    }

    /**
     * Proxy request to Property Service (maintenance).
     * ANY /api/maintenance/{path}
     */
    public function proxyMaintenance(Request $request, string $path = ''): JsonResponse
    {
        return $this->forwardRequest($request, 'property', '/api/maintenance/' . $path);
    }

    // ─── Payment Service Proxies ───────────────────────────────────

    /**
     * Proxy request to Payment Service (sewa).
     * ANY /api/sewa/{path}
     */
    public function proxySewa(Request $request, string $path = ''): JsonResponse
    {
        return $this->forwardRequest($request, 'payment', '/api/sewa/' . $path);
    }

    /**
     * Proxy request to Payment Service (tagihan).
     * ANY /api/tagihan/{path}
     */
    public function proxyTagihan(Request $request, string $path = ''): JsonResponse
    {
        return $this->forwardRequest($request, 'payment', '/api/tagihan/' . $path);
    }

    /**
     * Proxy request to Payment Service (pembayaran).
     * ANY /api/pembayaran/{path}
     */
    public function proxyPembayaran(Request $request, string $path = ''): JsonResponse
    {
        return $this->forwardRequest($request, 'payment', '/api/pembayaran/' . $path);
    }

    // ─── Complaint Service Proxies ─────────────────────────────────

    /**
     * Proxy request to Complaint Service (laporan).
     * ANY /api/laporan/{path}
     */
    public function proxyLaporan(Request $request, string $path = ''): JsonResponse
    {
        return $this->forwardRequest($request, 'complaint', '/api/laporan/' . $path);
    }

    // ─── Notification Service Proxies ──────────────────────────────

    /**
     * Proxy request to Notification Service (notifikasi).
     * ANY /api/notifikasi/{path}
     */
    public function proxyNotifikasi(Request $request, string $path = ''): JsonResponse
    {
        return $this->forwardRequest($request, 'notification', '/api/notifikasi/' . $path);
    }

    // ─── Health Check ──────────────────────────────────────────────

    /**
     * Health check endpoint.
     * GET /api/health
     */
    public function health(): JsonResponse
    {
        $statuses = [];

        foreach ($this->services as $name => $url) {
            try {
                $client = new Client(['timeout' => 3]);
                $response = $client->get($url . '/up');
                $statuses[$name] = [
                    'status'      => 'healthy',
                    'status_code' => $response->getStatusCode(),
                    'url'         => $url,
                ];
            } catch (\Exception $e) {
                $statuses[$name] = [
                    'status'  => 'unhealthy',
                    'message' => $e->getMessage(),
                    'url'     => $url,
                ];
            }
        }

        $allHealthy = collect($statuses)->every(fn($s) => $s['status'] === 'healthy');

        return response()->json([
            'success'  => true,
            'message'  => $allHealthy ? 'All services are healthy' : 'Some services are unhealthy',
            'data'     => [
                'gateway'  => 'healthy',
                'services' => $statuses,
                'timestamp' => now()->toISOString(),
            ],
            'errors' => null,
        ], $allHealthy ? 200 : 503);
    }

    // ─── Request Forwarder ─────────────────────────────────────────

    /**
     * Forward the request to the target service.
     */
    private function forwardRequest(Request $request, string $service, string $path): JsonResponse
    {
        $baseUrl = $this->services[$service] ?? null;

        if (!$baseUrl) {
            return response()->json([
                'success' => false,
                'message' => "Service '{$service}' not configured",
                'data'    => null,
                'errors'  => null,
            ], 503);
        }

        try {
            $client = new Client([
                'base_uri' => $baseUrl,
                'timeout'  => 30,
            ]);

            // Build request options
            $options = [
                'headers' => [
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'query' => $request->query(),
            ];

            // Forward Authorization header
            if ($request->hasHeader('Authorization')) {
                $options['headers']['Authorization'] = $request->header('Authorization');
            }

            // Forward request body for POST/PUT/PATCH
            if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
                $options['json'] = $request->all();
            }

            $response = $client->request($request->method(), $path, $options);

            $body = json_decode($response->getBody()->getContents(), true);

            return response()->json($body, $response->getStatusCode());

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $body = json_decode($response->getBody()->getContents(), true);

            return response()->json(
                $body ?? [
                    'success' => false,
                    'message' => 'Service returned an error',
                    'data'    => null,
                    'errors'  => null,
                ],
                $response->getStatusCode()
            );
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            $response = $e->getResponse();

            return response()->json([
                'success' => false,
                'message' => "Service '{$service}' internal error",
                'data'    => null,
                'errors'  => null,
            ], $response->getStatusCode());
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            return response()->json([
                'success' => false,
                'message' => "Service '{$service}' is unavailable",
                'data'    => null,
                'errors'  => $e->getMessage(),
            ], 503);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gateway error: ' . $e->getMessage(),
                'data'    => null,
                'errors'  => null,
            ], 500);
        }
    }
}
