<?php

namespace FullStack\App\Api;

use FullStack\App\Api\ApiError;
use FullStack\App\Api\ApiResponse;
use WP_Error;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;

class Posts {

	/**
	 * Register REST API routes.
	 */
	public static function register(): void {

		// GET /posts
		register_rest_route(
			'fullstack/v1',
			'/posts',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ self::class, 'index' ],
				'permission_callback' => '__return_true',
				'args'                => self::collection_args(),
			]
		);

		// GET /posts/{id}
		register_rest_route(
			'fullstack/v1',
			'/posts/(?P<id>\d+)',
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

		// GET /posts/slug/{slug}
		register_rest_route(
			'fullstack/v1',
			'/posts/slug/(?P<slug>[a-z0-9-]+)',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ self::class, 'showBySlug' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'slug' => [
						'required'          => true,
						'sanitize_callback' => 'sanitize_title',
					],
				],
			]
		);
	}

	/**
	 * Define parameters accepted by the collection endpoint.
	 */
	private static function collection_args(): array {

		return [
			'page' => [
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => function ( $value ) {
					return (int) $value >= 1;
				},
			],

			'per_page' => [
				'default'           => 10,
				'sanitize_callback' => 'absint',
				'validate_callback' => function ( $value ) {
					return (int) $value >= 1 && (int) $value <= 50;
				},
			],

			'search' => [
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			],

			'category' => [
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => function ( $value ) {

					if ( '' === $value ) {
						return true;
					}

					if ( ctype_digit( (string) $value ) ) {
						return (int) $value > 0;
					}

					return (bool) preg_match(
						'/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
						$value
					);
				},
			],

			'orderby' => [
				'default'           => 'date',
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => function ( $value ) {

					return in_array(
						$value,
						[
							'date',
							'title',
							'ID',
							'modified',
						],
						true
					);
				},
			],

			'order' => [
				'default'           => 'desc',
				'sanitize_callback' => function ( $value ) {
					return strtoupper( $value );
				},
				'validate_callback' => function ( $value ) {

					return in_array(
						strtoupper( $value ),
						[
							'ASC',
							'DESC',
						],
						true
					);
				},
			],
		];
	}

	/**
	 * GET /posts
	 *
	 * Return a paginated collection of published posts.
	 */
	public static function index( WP_REST_Request $request ): WP_REST_Response {

		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = min(
			50,
			max(
				1,
				(int) $request->get_param( 'per_page' )
			)
		);

		$search   = $request->get_param( 'search' );
		$category = $request->get_param( 'category' );
		$orderby  = $request->get_param( 'orderby' );
		$order    = strtoupper( $request->get_param( 'order' ) );

		$query_args = [
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => $orderby,
			'order'          => $order,
		];

		if ( ! empty( $search ) ) {
			$query_args['s'] = $search;
		}

		if ( ! empty( $category ) ) {

			if ( ctype_digit( (string) $category ) ) {
				$query_args['cat'] = (int) $category;
			} else {
				$query_args['category_name'] = $category;
			}
		}

		$query = new WP_Query( $query_args );

		$posts = [];

		foreach ( $query->posts as $post ) {
			$posts[] = self::transform( $post );
		}

		return ApiResponse::success(
			$posts,
			[
				'page'        => $page,
				'per_page'    => $per_page,
				'total'       => (int) $query->found_posts,
				'total_pages' => (int) $query->max_num_pages,
			]
		);
	}

	/**
	 * GET /posts/{id}
	 *
	 * Return a single published post.
	 */
	public static function show(
		WP_REST_Request $request
	): WP_REST_Response|WP_Error {

		$id = (int) $request->get_param( 'id' );

		$post = get_post( $id );

		if (
			! $post ||
			'post' !== $post->post_type ||
			'publish' !== $post->post_status
		) {
			return ApiError::make(
				'fullstack_post_not_found',
				'Post not found.',
				404
			);
		}

		return ApiResponse::success(
			self::transform( $post )
		);
	}

	/**
	 * GET /posts/slug/{slug}
	 *
	 * Return a single published post by slug.
	 */
	public static function showBySlug(
		WP_REST_Request $request
	): WP_REST_Response|WP_Error {

		$slug = sanitize_title(
			$request->get_param( 'slug' )
		);

		$query = new WP_Query(
			[
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'name'           => $slug,
				'posts_per_page' => 1,
			]
		);

		$post = $query->posts[0] ?? null;

		if ( ! $post ) {
			return ApiError::make(
				'fullstack_post_not_found',
				'Post not found.',
				404
			);
		}

		return ApiResponse::success(
			self::transform( $post )
		);
	}

	/**
	 * Transform a WordPress post into our API representation.
	 */
	private static function transform( $post ): array {

		$categories = get_the_category( $post->ID );

		$category_data = [];

		foreach ( $categories as $category ) {
			$category_data[] = [
				'id'   => (int) $category->term_id,
				'name' => $category->name,
				'slug' => $category->slug,
			];
		}

		$featured_image_id = get_post_thumbnail_id( $post->ID );

		$featured_image = null;

		if ( $featured_image_id ) {
			$featured_image = [
				'id'  => (int) $featured_image_id,
				'url' => get_the_post_thumbnail_url(
					$post->ID,
					'full'
				),
				'alt' => get_post_meta(
					$featured_image_id,
					'_wp_attachment_image_alt',
					true
				),
			];
		}

		return [
			'id'             => (int) $post->ID,
			'title'          => get_the_title( $post ),
			'slug'           => $post->post_name,
			'excerpt'        => get_the_excerpt( $post ),
			'content'        => apply_filters(
				'the_content',
				$post->post_content
			),
			'date'           => get_post_time(
				DATE_ATOM,
				true,
				$post
			),
			'author'         => [
				'id'   => (int) $post->post_author,
				'name' => get_the_author_meta(
					'display_name',
					$post->post_author
				),
			],
			'categories'     => $category_data,
			'featured_image' => $featured_image,
			'link'           => get_permalink( $post ),
		];
	}
}