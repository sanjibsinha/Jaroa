<?php

namespace FullStack\App\Api;

use WP_Error;

class ApiError {

	/**
	 * Create a standardized API error.
	 */
	public static function make(
		string $code,
		string $message,
		int $status
	): WP_Error {

		return new WP_Error(
			$code,
			$message,
			[
				'status' => $status,
			]
		);
	}
}