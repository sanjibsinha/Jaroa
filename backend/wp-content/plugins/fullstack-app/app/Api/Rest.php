<?php

namespace FullStack\App\Api;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class Rest {

	/**
	 * Register API response handling.
	 */
	public static function register(): void {

		add_filter(
			'rest_post_dispatch',
			[ self::class, 'format_error_response' ],
			10,
			3
		);
	}

	/**
	 * Format errors for our API namespace.
	 */
	public static function format_error_response(
		$response,
		WP_REST_Server $server,
		WP_REST_Request $request
	) {

		$route = $request->get_route();

		// Only modify our own API.
		if ( ! str_starts_with( $route, '/fullstack/v1/' ) ) {
			return $response;
		}

		if ( ! is_wp_error( $response ) ) {
			return $response;
		}

		$status = 500;

		$error_data = $response->get_error_data();

		if ( is_array( $error_data ) && isset( $error_data['status'] ) ) {
			$status = (int) $error_data['status'];
		} elseif ( is_numeric( $error_data ) ) {
			$status = (int) $error_data;
		}

		return new WP_REST_Response(
			[
				'error' => [
					'code'    => $response->get_error_code(),
					'message' => $response->get_error_message(),
					'status'  => $status,
				],
			],
			$status
		);
	}
}