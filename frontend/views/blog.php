<?php

$showSiteFooter = false;

$pageTitle = $appName . ' · Blog';

$pageStylesheets = [
    '/assets/blog/css/blog.css',
];
?>

<div class="blog-shell">

    <nav class="blog-nav">
        <div class="blog-brand">Jaroa Blog</div>

        <div class="blog-nav-links">
            <a href="#">Stories</a>
            <a href="#">Ideas</a>
            <a href="#">About</a>
        </div>
    </nav>

    <section class="blog-hero">
        <div class="blog-hero-copy">
            <div class="blog-kicker">Jaroa Blog Starter</div>

            <h1>
                Thoughts deserve a place to grow.
            </h1>

            <p>
                Stories, ideas and reflections on life, work
                and everything in between.
            </p>
        </div>

        <div class="blog-hero-art"></div>
    </section>

    <section class="blog-section">

        <div class="blog-section-head">
            <h2>Latest Articles</h2>
            <a href="#">View all articles →</a>
        </div>

        <div class="blog-grid">

            <article class="blog-card">
                <div class="blog-card-image"></div>

                <div class="blog-card-body">
                    <div class="blog-category">Writing</div>
                    <h3>The Art of Slow Living</h3>

                    <p>
                        Finding clarity in an increasingly
                        complicated world.
                    </p>

                    <div class="blog-meta">
                        August 29, 2026 · 6 min read
                    </div>
                </div>
            </article>

            <article class="blog-card">
                <div class="blog-card-image"></div>

                <div class="blog-card-body">
                    <div class="blog-category">Reflections</div>
                    <h3>Lessons from the Mountains</h3>

                    <p>
                        What quiet places can teach us about
                        attention and patience.
                    </p>

                    <div class="blog-meta">
                        August 26, 2026 · 7 min read
                    </div>
                </div>
            </article>

            <article class="blog-card">
                <div class="blog-card-image"></div>

                <div class="blog-card-body">
                    <div class="blog-category">Productivity</div>
                    <h3>Focus in a Distracted World</h3>

                    <p>
                        Practical ways to protect your attention
                        and get things done.
                    </p>

                    <div class="blog-meta">
                        August 23, 2026 · 5 min read
                    </div>
                </div>
            </article>

        </div>
    </section>

</div>
