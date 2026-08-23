<?php

namespace FullStack\App\Api;

use WP_REST_Response;

class ApiResponse {

	/**
	 * Create a successful API response.
	 */
	public static function success(
		mixed $data,
		?array $meta = null,
		int $status = 200
	): WP_REST_Response {

		$response = [
			'data' => $data,
		];

		if ( null !== $meta ) {
			$response['meta'] = $meta;
		}

		return new WP_REST_Response(
			$response,
			$status
		);
	}
}