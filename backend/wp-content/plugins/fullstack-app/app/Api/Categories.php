<?php

namespace FullStack\App\Api;

use FullStack\App\Api\ApiError;
use FullStack\App\Api\ApiResponse;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;


class Categories {

	/**
	 * Register REST API routes.
	 */
	public static function register(): void {

		// GET /categories
		register_rest_route(
			'fullstack/v1',
			'/categories',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ self::class, 'index' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'hide_empty' => [
						'default'           => true,
						'sanitize_callback' => function ( $value ) {
							return filter_var(
								$value,
								FILTER_VALIDATE_BOOLEAN,
								FILTER_NULL_ON_FAILURE
							) ?? true;
						},
					],
				],
			]
		);

		// GET /categories/{id}
		register_rest_route(
			'fullstack/v1',
			'/categories/(?P<id>\d+)',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ self::class, 'show' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'id' => [
						'required'          => true,
						'sanitize_callback' => 'absint',
						'validate_callback' => function ( $value ) {
							return (int) $value > 0;
						},
					],
				],
			]
		);
	}

	/**
	 * GET /categories
	 *
	 * Return all categories.
	 */
	public static function index(
		WP_REST_Request $request
	): WP_REST_Response {

		$hide_empty = (bool) $request->get_param( 'hide_empty' );

		$terms = get_categories(
			[
				'hide_empty' => $hide_empty,
				'orderby'    => 'name',
				'order'      => 'ASC',
			]
		);

		$categories = [];

		foreach ( $terms as $term ) {
			$categories[] = self::transform( $term );
		}

		return ApiResponse::success(
			$categories,
			[
				'total' => count( $categories ),
			]
		);
	}

	/**
	 * GET /categories/{id}
	 *
	 * Return a single category.
	 */
	public static function show(
		WP_REST_Request $request
	): WP_REST_Response|WP_Error {

		$id = (int) $request->get_param( 'id' );

		$term = get_category( $id );

		if ( ! $term || is_wp_error( $term ) ) {
			return ApiError::make(
				'fullstack_category_not_found',
				'Category not found.',
				404
			);
		}

		return ApiResponse::success(
			self::transform( $term )
		);
	}

	/**
	 * Transform a WordPress category into our API representation.
	 */
	private static function transform( $term ): array {

		return [
			'id'    => (int) $term->term_id,
			'name'  => $term->name,
			'slug'  => $term->slug,
			'count' => (int) $term->count,
		];
	}
}