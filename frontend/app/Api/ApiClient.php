<?php

namespace App\Api;

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
    public function get(string $endpoint, array $query = []): array
    {
        $url = $this->buildUrl($endpoint, $query);

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'Accept: application/json',
                ],
                'ignore_errors' => true,
            ],
        ]);

        $response = file_get_contents($url, false, $context);

        if (false === $response) {
            throw new RuntimeException(
                "Unable to connect to API: {$url}"
            );
        }

        $statusCode = $this->getStatusCode($http_response_header ?? []);

        $data = json_decode($response, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new RuntimeException(
                'API returned invalid JSON: ' . json_last_error_msg()
            );
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            $message = $data['message'] ?? 'API request failed.';

            throw new RuntimeException(
                "API request failed ({$statusCode}): {$message}"
            );
        }

        return $data;
    }

    /**
     * Build the complete API URL.
     */
    private function buildUrl(string $endpoint, array $query = []): string
    {
        $endpoint = '/' . ltrim($endpoint, '/');

        $url = $this->baseUrl . $endpoint;

        if ([] !== $query) {
            $url .= '?' . http_build_query($query);
        }

        return $url;
    }

    /**
     * Extract the HTTP status code from the response headers.
     */
    private function getStatusCode(array $headers): int
    {
        foreach ($headers as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $header, $matches)) {
                return (int) $matches[1];
            }
        }

        return 0;
    }
}