<?php

namespace App\Api;

use App\Exceptions\NotFoundException;
use RuntimeException;

class ApiClient
{
    private readonly string $baseUrl;

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Send a GET request to the API.
     */
    public function get(
        string $endpoint,
        array $query = [],
        array $headers = []
    ): array {
        return $this->request(
            'GET',
            $endpoint,
            $query,
            null,
            $headers
        );
    }

    /**
     * Send a POST request with a JSON body.
     */
    public function post(
        string $endpoint,
        array $data = [],
        array $headers = []
    ): array {
        return $this->request(
            'POST',
            $endpoint,
            [],
            $data,
            $headers
        );
    }

    /**
     * Build and execute an HTTP request.
     */

    public function put(
        string $endpoint,
        array $data = [],
        array $headers = []
    ): array {
        return $this->request(
            'PUT',
            $endpoint,
            $headers,
            $data
        );
    }

    public function delete(
        string $endpoint,
        array $headers = []
    ): array {
        return $this->request(
            'DELETE',
            $endpoint,
            $headers
        );
    }

    private function request(
        string $method,
        string $endpoint,
        array $query = [],
        ?array $json = null,
        array $headers = []
    ): array {
        $url = $this->buildUrl($endpoint, $query);

        $requestHeaders = [
            'Accept: application/json',
        ];

        foreach ($headers as $name => $value) {
            $requestHeaders[] = $name . ': ' . $value;
        }

        $options = [
            'http' => [
                'method' => strtoupper($method),
                'header' => $requestHeaders,
                'ignore_errors' => true,
            ],
        ];

        if ($json !== null) {
            $encoded = json_encode(
                $json,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );

            if (false === $encoded) {
                throw new RuntimeException(
                    'Unable to encode API request JSON.'
                );
            }

            $options['http']['header'][] =
                'Content-Type: application/json';

            $options['http']['content'] = $encoded;
        }

        $context = stream_context_create($options);

        $response = file_get_contents(
            $url,
            false,
            $context
        );

        if (false === $response) {
            throw new RuntimeException(
                "Unable to connect to API: {$url}"
            );
        }

        $statusCode = $this->getStatusCode(
            $http_response_header ?? []
        );

        $data = json_decode(
            $response,
            true
        );

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new RuntimeException(
                'API returned invalid JSON: ' .
                json_last_error_msg()
            );
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            $message =
                $data['error']['message']
                ?? $data['message']
                ?? 'API request failed.';

            if (404 === $statusCode) {
                throw new NotFoundException($message);
            }

            throw new RuntimeException(
                "API request failed ({$statusCode}): {$message}"
            );
        }

        return $data;
    }

    /**
     * Build the complete API URL.
     */
    private function buildUrl(
        string $endpoint,
        array $query = []
    ): string {
        $endpoint = '/' . ltrim(
            $endpoint,
            '/'
        );

        $url = $this->baseUrl . $endpoint;

        if ([] !== $query) {
            $url .= '?' . http_build_query($query);
        }

        return $url;
    }

    /**
     * Extract the HTTP status code from response headers.
     */
    private function getStatusCode(array $headers): int
    {
        foreach ($headers as $header) {
            if (
                preg_match(
                    '/^HTTP\/\S+\s+(\d{3})/',
                    $header,
                    $matches
                )
            ) {
                return (int) $matches[1];
            }
        }

        return 0;
    }
}
