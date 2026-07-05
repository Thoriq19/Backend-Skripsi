<?php

namespace Shared;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;

/**
 * MicroserviceClient - HTTP Client for inter-service communication
 *
 * Provides a standardized way for services to communicate with each other
 * using HTTP requests with automatic retry logic and error handling.
 */
class MicroserviceClient
{
    protected Client $client;
    protected string $baseUrl;
    protected int $timeout;
    protected int $retries;

    /**
     * Create a new MicroserviceClient instance.
     *
     * @param string $baseUrl Base URL of the target service
     * @param int $timeout Request timeout in seconds
     * @param int $retries Number of retry attempts
     */
    public function __construct(string $baseUrl, int $timeout = 10, int $retries = 2)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
        $this->retries = $retries;

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => $this->timeout,
            'headers'  => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Make a GET request.
     *
     * @param string $uri
     * @param array $query
     * @param string|null $token JWT token to forward
     * @return array
     */
    public function get(string $uri, array $query = [], ?string $token = null): array
    {
        return $this->request('GET', $uri, [
            'query' => $query,
        ], $token);
    }

    /**
     * Make a POST request.
     *
     * @param string $uri
     * @param array $data
     * @param string|null $token JWT token to forward
     * @return array
     */
    public function post(string $uri, array $data = [], ?string $token = null): array
    {
        return $this->request('POST', $uri, [
            'json' => $data,
        ], $token);
    }

    /**
     * Make a PUT request.
     *
     * @param string $uri
     * @param array $data
     * @param string|null $token JWT token to forward
     * @return array
     */
    public function put(string $uri, array $data = [], ?string $token = null): array
    {
        return $this->request('PUT', $uri, [
            'json' => $data,
        ], $token);
    }

    /**
     * Make a DELETE request.
     *
     * @param string $uri
     * @param string|null $token JWT token to forward
     * @return array
     */
    public function delete(string $uri, ?string $token = null): array
    {
        return $this->request('DELETE', $uri, [], $token);
    }

    /**
     * Execute HTTP request with retry logic.
     *
     * @param string $method
     * @param string $uri
     * @param array $options
     * @param string|null $token
     * @return array
     */
    protected function request(string $method, string $uri, array $options = [], ?string $token = null): array
    {
        // Forward JWT token if provided
        if ($token) {
            $options['headers']['Authorization'] = 'Bearer ' . $token;
        }

        $attempts = 0;
        $lastException = null;

        while ($attempts <= $this->retries) {
            try {
                $response = $this->client->request($method, $uri, $options);

                $body = json_decode($response->getBody()->getContents(), true);

                return [
                    'success'     => true,
                    'status_code' => $response->getStatusCode(),
                    'data'        => $body,
                ];
            } catch (ConnectException $e) {
                $lastException = $e;
                $attempts++;

                if ($attempts <= $this->retries) {
                    // Exponential backoff: 100ms, 200ms, 400ms...
                    usleep(100000 * pow(2, $attempts - 1));
                }
            } catch (RequestException $e) {
                $response = $e->getResponse();
                $body = $response
                    ? json_decode($response->getBody()->getContents(), true)
                    : null;

                return [
                    'success'     => false,
                    'status_code' => $response ? $response->getStatusCode() : 500,
                    'data'        => $body,
                    'message'     => $e->getMessage(),
                ];
            }
        }

        return [
            'success'     => false,
            'status_code' => 503,
            'data'        => null,
            'message'     => 'Service unavailable after ' . $this->retries . ' retries: ' . ($lastException ? $lastException->getMessage() : 'Unknown error'),
        ];
    }

    /**
     * Extract JWT token from the current request.
     *
     * @param \Illuminate\Http\Request|null $request
     * @return string|null
     */
    public static function extractToken(?\Illuminate\Http\Request $request = null): ?string
    {
        $request = $request ?? request();
        $header = $request->header('Authorization', '');

        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        return null;
    }
}
