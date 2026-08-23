<?php

define('WP_USE_THEMES', false);
require_once __DIR__ . '/dashboard/wp-load.php';

/*
 * Get the application slug from the clean URL.
 *
 * Example:
 * /applications/youtube-down-loader
 *
 * becomes:
 * application.php?slug=youtube-down-loader
 */
$slug = isset($_GET['slug']) ? sanitize_title(wp_unslash($_GET['slug'])) : '';

if (!$slug) {
    http_response_code(404);

    $page_title = 'Application Not Found';
    $page_description = 'The requested application could not be found.';
    $canonical_url = 'https://sanjibsinha.in/applications/';

    require __DIR__ . '/includes/header.php';
    ?>

    <main class="container">
        <div class="no-apps">
            <h2>Application Not Found</h2>
            <p>The application you are looking for does not exist.</p>
        </div>
    </main>

    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}


/*
 * Find the published application by slug.
 */
$query = new WP_Query([
    'post_type'      => 'app_repository',
    'post_status'    => 'publish',
    'name'           => $slug,
    'posts_per_page' => 1,
]);


/*
 * Application does not exist.
 */
if (!$query->have_posts()) {
    http_response_code(404);

    $page_title = 'Application Not Found';
    $page_description = 'The requested application could not be found.';
    $canonical_url = 'https://sanjibsinha.in/applications/' . rawurlencode($slug);

    require __DIR__ . '/includes/header.php';
    ?>

    <main class="container">
        <div class="no-apps">
            <h2>Application Not Found</h2>
            <p>The application you are looking for does not exist.</p>
        </div>
    </main>

    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}


/*
 * Prepare the WordPress application.
 */
$query->the_post();

$post_id = get_the_ID();

$title = get_the_title();

$version = get_post_meta(
    $post_id,
    'app_version',
    true
) ?: '1.0.0';

$os = get_post_meta(
    $post_id,
    'app_os',
    true
) ?: 'Linux / Ubuntu';

$file_id = get_post_meta(
    $post_id,
    'app_file_id',
    true
);

$file_url = $file_id
    ? wp_get_attachment_url($file_id)
    : '';

$excerpt = get_the_excerpt();

$page_title = $title;

$page_description = $excerpt ?: wp_trim_words(
    wp_strip_all_tags(get_the_content()),
    25,
    '...'
);

$canonical_url = 'https://sanjibsinha.in/applications/' . rawurlencode($slug);

require __DIR__ . '/includes/header.php';
?>

<main class="container">
    <article class="application-content">

        <header class="application-header">

            <div>
                <h2><?php echo esc_html($title); ?></h2>

                <div class="application-meta">
                    <span class="badge">
                        v<?php echo esc_html($version); ?>
                    </span>

                    <span class="os-tag">
                        <?php echo esc_html($os); ?>
                    </span>
                </div>
            </div>

        </header>


        <?php if ($excerpt): ?>
            <p class="application-description">
                <?php echo esc_html($excerpt); ?>
            </p>
        <?php endif; ?>


        <div class="application-body">
            <?php the_content(); ?>
        </div>


        <div class="application-actions">

            <?php if ($file_url): ?>

                <a
                    href="<?php echo esc_url($file_url); ?>"
                    class="btn-download"
                    download
                >
                    Download
                </a>

            <?php else: ?>

                <span class="btn-disabled">
                    No Download Available
                </span>

            <?php endif; ?>

        </div>

    </article>
</main>

<?php
wp_reset_postdata();
require __DIR__ . '/includes/footer.php';