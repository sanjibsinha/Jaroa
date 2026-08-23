<?php

define('WP_USE_THEMES', false);
require_once __DIR__ . '/dashboard/wp-load.php';

/*
 * Get the article slug from the clean URL.
 *
 * Example:
 * /articles/my-first-article
 *
 * becomes:
 * article.php?slug=my-first-article
 */
$slug = isset($_GET['slug']) ? sanitize_title(wp_unslash($_GET['slug'])) : '';

if (!$slug) {
    http_response_code(404);

    $page_title = 'Article Not Found';
    $page_description = 'The requested article could not be found.';
    $canonical_url = 'https://sanjibsinha.in/articles/';

    require __DIR__ . '/includes/header.php';
    ?>

    <main class="container">
        <div class="no-apps">
            <h2>Article Not Found</h2>
            <p>The article you are looking for does not exist.</p>
        </div>
    </main>

    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}


/*
 * Find the published WordPress article by slug.
 */
$query = new WP_Query([
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'name'           => $slug,
    'posts_per_page' => 1,
]);


/*
 * Article does not exist.
 */
if (!$query->have_posts()) {
    http_response_code(404);

    $page_title = 'Article Not Found';
    $page_description = 'The requested article could not be found.';
    $canonical_url = 'https://sanjibsinha.in/articles/' . rawurlencode($slug);

    require __DIR__ . '/includes/header.php';
    ?>

    <main class="container">
        <div class="no-apps">
            <h2>Article Not Found</h2>
            <p>The article you are looking for does not exist.</p>
        </div>
    </main>

    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}


/*
 * Prepare the WordPress post.
 */
$query->the_post();

$post_id = get_the_ID();
$title = get_the_title();
$excerpt = get_the_excerpt();
$published_date = get_the_date('F j, Y');

$page_title = $title;
$page_description = $excerpt ?: wp_trim_words(
    wp_strip_all_tags(get_the_content()),
    25,
    '...'
);

$canonical_url = 'https://sanjibsinha.in/articles/' . rawurlencode($slug);

require __DIR__ . '/includes/header.php';
?>

<main class="container">
    <article class="article-content">

        <header class="article-header">
            <h2><?php echo esc_html($title); ?></h2>

            <p class="article-meta">
                Published: <?php echo esc_html($published_date); ?>
            </p>
        </header>

        <div class="article-body">
            <?php the_content(); ?>
        </div>

    </article>
</main>

<?php
wp_reset_postdata();
require __DIR__ . '/includes/footer.php';