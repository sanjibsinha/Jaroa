<?php

namespace FullStack\App\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_Query;

class Apps {

    /**
     * Register REST API routes for applications.
     */
    public static function register(): void {
        register_rest_route(
            'fullstack/v1',
            '/apps',
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ self::class, 'index' ],
                'permission_callback' => '__return_true',
            ]
        );
    }

    /**
     * GET /apps
     *
     * Return all published application repositories.
     */
    public static function index( WP_REST_Request $request ): WP_REST_Response {
        $query_args = [
            'post_type'      => 'app_repository',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        $query = new WP_Query( $query_args );
        $apps = [];

        foreach ( $query->posts as $post ) {
            $apps[] = self::transform( $post );
        }

        return ApiResponse::success( $apps, [ 'total' => count( $apps ) ] );
    }

    /**
     * Transform an application post into our API representation.
     */
    private static function transform( $post ): array {
        // Fetch custom metadata or fallbacks
        $version = get_post_meta( $post->ID, 'app_version', true ) ?: '1.0.0';
        $os      = get_post_meta( $post->ID, 'app_os', true ) ?: 'Linux / Ubuntu';
        $file_id = get_post_meta( $post->ID, 'app_file_id', true );
        $file_url = $file_id ? wp_get_attachment_url( $file_id ) : '';

        return [
            'id'          => $post->post_name,
            'title'       => get_the_title( $post ),
            'version'     => $version,
            'description' => get_the_excerpt( $post ) ?: $post->post_content,
            'os'          => $os,
            'file'        => $file_url,
            'date'        => get_post_time( 'M Y', true, $post ),
        ];
    }
}