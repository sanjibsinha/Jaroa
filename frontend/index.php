<?php
define('WP_USE_THEMES', false);
require_once __DIR__ . '/dashboard/wp-load.php';

// Pagination setup
$paged = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$tab = $_GET['tab'] ?? 'apps'; // 'apps' or 'posts'
$per_page = 6;

if ($tab === 'posts') {
    $query_args = [
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $paged,
    ];
} else {
    $query_args = [
        'post_type'      => 'app_repository',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $paged,
    ];
}

$query = new WP_Query($query_args);
$total_pages = $query->max_num_pages;
?>
<?php
$page_title = $tab === 'posts' ? 'Technical Articles & Insights' : 'Software Repository - Open Source Tools';
$page_description = 'A minimal, ultra-fast repository for software applications, open-source Linux tools, and technical articles.';
$canonical_url = 'https://sanjibsinha.in/' . ($tab !== 'apps' ? '?tab=' . rawurlencode($tab) : '');
require __DIR__ . '/includes/header.php';
?>

    <main class="container">
        <div class="app-grid">
            <?php if (!$query->have_posts()): ?>
                <div class="no-apps"><p>No items found in this section.</p></div>
            <?php else: ?>
                <?php while ($query->have_posts()): $query->the_post(); ?>
                    <?php if ($tab === 'posts'): ?>
                        <article class="app-card">
                            <div class="app-header">
                                <h2><?php the_title(); ?></h2>
                            </div>
                            <p class="app-desc"><?php echo get_the_excerpt(); ?></p>
                            <div class="app-footer">
                                <small><?php echo get_the_date('M Y'); ?></small>
                                <a href="/articles/<?php echo esc_attr(get_post_field('post_name', get_the_ID())); ?>" class="btn-download">
                                    Read Article
                                </a>
                            </div>
                        </article>
                    <?php else: ?>
                        <?php
                            $version = get_post_meta(get_the_ID(), 'app_version', true) ?: '1.0.0';
                            $os = get_post_meta(get_the_ID(), 'app_os', true) ?: 'Linux / Ubuntu';
                            $file_id = get_post_meta(get_the_ID(), 'app_file_id', true);
                            $file_url = $file_id ? wp_get_attachment_url($file_id) : '';
                        ?>
                        <article class="app-card">
                            <div class="app-header">
                                <h2><?php the_title(); ?> <span class="badge">v<?php echo htmlspecialchars($version); ?></span></h2>
                                <span class="os-tag"><?php echo htmlspecialchars($os); ?></span>
                            </div>
                            <p class="app-desc"><?php echo get_the_excerpt(); ?></p>
                            <div class="app-footer">
    <small>Updated: <?php echo esc_html(get_the_date('M Y')); ?></small>

    <div class="app-actions">
        <a
            href="/applications/<?php echo esc_attr(get_post_field('post_name', get_the_ID())); ?>"
            class="btn-read"
        >
            Read
        </a>

        <?php if ($file_url): ?>

            <a
                href="<?php echo esc_url($file_url); ?>"
                class="btn-download"
                download
            >
                Download
            </a>

        <?php else: ?>

            <span class="btn-disabled">No File</span>

        <?php endif; ?>
    </div>
</div>
                        </article>
                    <?php endif; ?>
                <?php endwhile; wp_reset_postdata(); ?>
            <?php endif; ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <nav class="pagination" aria-label="Pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?tab=<?php echo urlencode($tab); ?>&page=<?php echo $i; ?>" class="page-num <?php echo $i === $paged ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
    </main>

<?php require __DIR__ . '/includes/footer.php'; ?>
