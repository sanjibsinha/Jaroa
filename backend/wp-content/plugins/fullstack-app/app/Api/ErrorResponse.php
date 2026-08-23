<?php

namespace FullStack\App\Api;

use WP_Error;

class ErrorResponse {

	/**
	 * Convert WP_Error into our API error format.
	 */
	public static function format( WP_Error $error ): array {

		$data = $error->get_error_data();

		return [
			'error' => [
				'code'    => $error->get_error_code(),
				'message' => $error->get_error_message(),
				'status'  => isset( $data['status'] )
					? (int) $data['status']
					: 500,
			],
		];
	}
}