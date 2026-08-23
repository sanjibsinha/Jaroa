<?php

namespace FullStack\App\Api;

class Status {

	public static function register(): void {

		register_rest_route(
			'fullstack/v1',
			'/status',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ self::class, 'handle' ],
				'permission_callback' => '__return_true',
			]
		);
	}

	public static function handle(): array {

		return [
			'status'      => 'ok',
			'application' => 'Full Stack App',
			'version'     => '0.1.0',
		];
	}
}