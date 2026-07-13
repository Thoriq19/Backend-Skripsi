<?php

namespace Tests\Feature;

use Tests\TestCase;

class GatewayRoutingTest extends TestCase
{
    /**
     * Test API Gateway health check endpoint structure.
     */
    public function test_gateway_health_check_endpoint()
    {
        $response = $this->getJson('/api/health');

        // Status can be 200 (if all services are up) or 503 (if any service is offline)
        $this->assertTrue(in_array($response->getStatusCode(), [200, 503]));

        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'gateway',
                'services',
                'timestamp',
            ]
        ]);
    }

    /**
     * Test gateway returns error if target service is completely invalid/unconfigured.
     */
    public function test_forward_request_fails_if_target_service_unconfigured()
    {
        // Mocking a request forwarding to a non-existent proxy route
        // ProxyController uses forwardRequest which checks service url mapping.
        // Let's call a non-existent route on the gateway
        $response = $this->getJson('/api/invalid-service-xyz');

        $response->assertStatus(404);
    }
}
