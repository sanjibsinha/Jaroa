<?php

define('WP_USE_THEMES', false);
require_once __DIR__ . '/dashboard/wp-load.php';


/*
|--------------------------------------------------------------------------
| Get Page Slug
|--------------------------------------------------------------------------
*/

$slug = isset($_GET['slug'])
    ? sanitize_title(wp_unslash($_GET['slug']))
    : '';


$allowed_pages = [
    'about',
    'contact',
    'privacy-policy',
    'terms',
];


if (!in_array($slug, $allowed_pages, true)) {
    http_response_code(404);
    exit('Page not found.');
}


/*
|--------------------------------------------------------------------------
| Find the WordPress Page
|--------------------------------------------------------------------------
*/

$page_query = new WP_Query([
    'post_type'      => 'page',
    'post_status'    => 'publish',
    'name'           => $slug,
    'posts_per_page' => 1,
]);


if (!$page_query->have_posts()) {
    http_response_code(404);
    exit('Page not found.');
}


$page_query->the_post();


$page_title = get_the_title();

$page_description = wp_trim_words(
    wp_strip_all_tags(get_the_content()),
    30,
    '...'
);

$canonical_url = home_url('/' . $slug . '/');

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?php echo esc_html($page_title); ?> | Sanjib Sinha
    </title>

    <meta
        name="description"
        content="<?php echo esc_attr($page_description); ?>"
    >

    <meta
        name="robots"
        content="index, follow"
    >

    <link
        rel="canonical"
        href="<?php echo esc_url($canonical_url); ?>"
    >

    <link
        rel="stylesheet"
        href="assets/style.css"
    >

</head>


<body>

<header class="site-header">

    <div class="container header-flex">

        <div>

            <h1>
                Software Repository
            </h1>

            <p>
                Minimal, high-performance applications and notes.
            </p>

        </div>


        <button
            id="theme-toggle"
            class="theme-toggle"
            type="button"
        >
            🌙 Dark Mode
        </button>

    </div>


    <!-- Main Navigation -->

    <nav class="container main-nav">

        <a
            href="/?tab=apps"
            class="nav-link"
        >
            Applications
        </a>


        <a
            href="/?tab=posts"
            class="nav-link"
        >
            Articles
        </a>


        <a
            href="/about"
            class="nav-link <?php echo $slug === 'about' ? 'active' : ''; ?>"
        >
            About
        </a>


        <a
            href="/contact"
            class="nav-link <?php echo $slug === 'contact' ? 'active' : ''; ?>"
        >
            Contact
        </a>

    </nav>

</header>


<main>

    <article class="page-content">

        <header class="page-header">

            <h2>
                <?php echo esc_html($page_title); ?>
            </h2>

        </header>


        <div class="page-body">

            <?php the_content(); ?>

        </div>

    </article>

</main>


<footer class="site-footer">

    <div class="container footer-content">

        <p>
            © <?php echo esc_html(date('Y')); ?> Sanjib Sinha
        </p>


        <nav class="footer-nav">

            <a href="/privacy-policy">
                Privacy Policy
            </a>


            <span class="footer-separator">
                •
            </span>


            <a href="/terms">
                Terms
            </a>

        </nav>

    </div>

</footer>


<script src="assets/script.js"></script>


<?php

wp_reset_postdata();

?>

</body>

</html>