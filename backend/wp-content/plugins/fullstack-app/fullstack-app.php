<?php
/**
 * Plugin Name: Full Stack App
 * Description: Backend application layer for our full-stack web app.
 * Version: 0.1.0
 * Author: Sanjib Deb Sinha
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$autoload = __DIR__ . '/vendor/autoload.php';

if ( ! file_exists( $autoload ) ) {
	return;
}

require_once $autoload;

FullStack\App\Bootstrap::boot();

/**
 * Redirect all public frontend requests to the login page,
 * leaving the Admin dashboard and REST API fully accessible.
 */


function register_app_repository_post_type() {
    register_post_type('app_repository', [
        'labels' => [
            'name' => 'Applications',
            'singular_name' => 'Application',
            'add_new' => 'Add New App',
            'add_new_item' => 'Add New Application',
            'edit_item' => 'Edit Application',
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_rest' => true,
        'supports' => ['title', 'editor', 'custom-fields', 'thumbnail'],
        'menu_icon' => 'dashicons-download',
    ]);
}
add_action('init', 'register_app_repository_post_type');

add_action('init', function() {
    register_post_type('app_repository', [
        'labels' => [
            'name' => 'Applications',
            'singular_name' => 'Application',
            'add_new' => 'Add New App',
            'add_new_item' => 'Add New Application',
            'edit_item' => 'Edit Application',
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_rest' => true,
        'supports' => ['title', 'editor', 'custom-fields', 'thumbnail'],
        'menu_icon' => 'dashicons-download',
    ]);
});

// Add Meta Box for App File Upload
function add_app_file_meta_box() {
    add_meta_box(
        'app_file_box',
        'Application Zip File',
        function( $post ) {
            $file_id = get_post_meta( $post->ID, 'app_file_id', true );
            $file_url = $file_id ? wp_get_attachment_url( $file_id ) : '';
            wp_nonce_field( 'save_app_file', 'app_file_nonce' );
            ?>
            <p>
                <input type="hidden" id="app_file_id" name="app_file_id" value="<?php echo esc_attr( $file_id ); ?>">
                <input type="text" id="app_file_url" value="<?php echo esc_url( $file_url ); ?>" style="width: 80%;" readonly>
                <button type="button" class="button" id="upload_app_button">Upload Zip</button>
            </p>
            <script>
            jQuery(document).ready(function($){
                $('#upload_app_button').click(function(e) {
                    e.preventDefault();
                    var custom_uploader = wp.media({
                        title: 'Select Application Zip',
                        button: { text: 'Use this file' },
                        multiple: false
                    }).on('select', function() {
                        var attachment = custom_uploader.state().get('selection').first().toJSON();
                        $('#app_file_id').val(attachment.id);
                        $('#app_file_url').val(attachment.url);
                    }).open();
                });
            });
            </script>
            <?php
        },
        'app_repository',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'add_app_file_meta_box' );

// Save Meta Box Data
function save_app_file_meta( $post_id ) {
    if ( ! isset( $_POST['app_file_nonce'] ) || ! wp_verify_nonce( $_POST['app_file_nonce'], 'save_app_file' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( isset( $_POST['app_file_id'] ) ) {
        update_post_meta( $post_id, 'app_file_id', sanitize_text_field( $_POST['app_file_id'] ) );
    }
}
add_action( 'save_post_app_repository', 'save_app_file_meta' );

function restrict_wordpress_frontend() {
    // 1. Bypass restriction completely if it's a REST API request
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || strpos( $request_uri, '/wp-json/' ) !== false ) {
        return;
    }

    // 2. Otherwise, redirect non-logged-in users trying to view the frontend site
    if ( ! is_user_logged_in() && ! is_admin() && ! in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ) ) ) {
        wp_redirect( wp_login_url() );
        exit;
    }
}
add_action( 'template_redirect', 'restrict_wordpress_frontend' );

/**
 * Allow Cross-Origin Requests (CORS) from DDEV Frontend
 */
add_action('rest_api_init', function() {
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    add_filter('rest_pre_serve_request', function($value) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Credentials: true');
        return $value;
    });
});