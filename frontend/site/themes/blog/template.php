<?php

$showSiteHeader = false;
$showSiteFooter = false;

$pageTitle = $appName . ' · Blog';
$pageDescription = 'A quiet editorial space for stories, ideas and unfinished thoughts.';

$pageStylesheets = [
    '/themes/blog/assets/css/blog.css',
];
?>

<div class="blog-site">

    <header class="blog-header">

        <a class="blog-brand" href="/">
            <span class="blog-brand-mark">J</span>
            <span class="blog-brand-name">Jaroa</span>
            <span class="blog-brand-divider">/</span>
            <span class="blog-brand-section">Blog</span>
        </a>

        <nav class="blog-nav" aria-label="Blog navigation">
            <a href="#stories">Stories</a>
            <a href="#ideas">Ideas</a>
            <a href="#about">About</a>
        </nav>

        <a class="blog-header-home" href="/">
            Back to Jaroa <span>↗</span>
        </a>

    </header>


    <main>

        <section class="blog-hero">

            <div class="blog-hero-copy">

                <p class="blog-kicker">
                    Jaroa Blog · Vol. 01
                </p>

                <h1>
                    Thoughts deserve
                    <em>a place to grow.</em>
                </h1>

                <p class="blog-hero-introduction">
                    Stories, observations and unfinished ideas about
                    technology, creativity, work and the strange
                    spaces where they meet.
                </p>

            </div>

            <div class="blog-hero-art" aria-hidden="true">
                <div class="blog-orbit blog-orbit-one"></div>
                <div class="blog-orbit blog-orbit-two"></div>
                <div class="blog-orbit blog-orbit-three"></div>
                <div class="blog-orbit-dot blog-orbit-dot-one"></div>
                <div class="blog-orbit-dot blog-orbit-dot-two"></div>
            </div>

        </section>


        <section class="blog-featured" id="stories">

            <div class="blog-section-label">
                <span>01</span>
                Featured story
            </div>

            <article class="blog-featured-card">

                <div class="blog-featured-image" aria-hidden="true">
                    <div class="blog-featured-image-word">
                        SLOW
                    </div>
                </div>

                <div class="blog-featured-content">

                    <p class="blog-category">
                        Writing
                    </p>

                    <h2>
                        The Art of Slow Living
                    </h2>

                    <p class="blog-featured-excerpt">
                        Finding clarity in an increasingly complicated
                        world, and learning that attention is sometimes
                        the most valuable thing we can give an idea.
                    </p>

                    <div class="blog-article-meta">
                        <span>29 AUG 2026</span>
                        <span>6 min read</span>
                    </div>

                    <a class="blog-read-link" href="#">
                        Read story <span>↗</span>
                    </a>

                </div>

            </article>

        </section>


        <section class="blog-latest" id="ideas">

            <div class="blog-section-heading">

                <div class="blog-section-label">
                    <span>02</span>
                    From the notebook
                </div>

                <h2>
                    Recent thoughts.
                </h2>

                <p>
                    A small collection of ideas, observations
                    and questions still being worked through.
                </p>

            </div>


            <div class="blog-grid">

                <article class="blog-card">

                    <div class="blog-card-number">
                        01
                    </div>

                    <p class="blog-category">
                        Reflections
                    </p>

                    <h3>
                        Lessons from the Mountains
                    </h3>

                    <p class="blog-card-excerpt">
                        What quiet places can teach us about
                        attention, patience and seeing without rushing.
                    </p>

                    <div class="blog-article-meta">
                        <span>26 AUG 2026</span>
                        <span>7 min read</span>
                    </div>

                    <a class="blog-card-link" href="#">
                        Continue reading <span>↗</span>
                    </a>

                </article>


                <article class="blog-card blog-card-dark">

                    <div class="blog-card-number">
                        02
                    </div>

                    <p class="blog-category">
                        Productivity
                    </p>

                    <h3>
                        Focus in a Distracted World
                    </h3>

                    <p class="blog-card-excerpt">
                        Practical ways to protect attention when
                        everything around us is competing for it.
                    </p>

                    <div class="blog-article-meta">
                        <span>23 AUG 2026</span>
                        <span>5 min read</span>
                    </div>

                    <a class="blog-card-link" href="#">
                        Continue reading <span>↗</span>
                    </a>

                </article>


                <article class="blog-card">

                    <div class="blog-card-number">
                        03
                    </div>

                    <p class="blog-category">
                        Technology
                    </p>

                    <h3>
                        When Software Becomes a Language
                    </h3>

                    <p class="blog-card-excerpt">
                        Every system carries assumptions about
                        what belongs together and what should remain apart.
                    </p>

                    <div class="blog-article-meta">
                        <span>18 AUG 2026</span>
                        <span>8 min read</span>
                    </div>

                    <a class="blog-card-link" href="#">
                        Continue reading <span>↗</span>
                    </a>

                </article>

            </div>

        </section>


        <section class="blog-manifesto" id="about">

            <p class="blog-manifesto-mark">
                “
            </p>

            <p class="blog-manifesto-text">
                The best ideas are rarely born finished.
                They become interesting while we are still
                trying to understand what they mean.
            </p>

            <div class="blog-manifesto-rule"></div>

            <p class="blog-manifesto-caption">
                Jaroa Blog · Writing, technology and ideas
            </p>

        </section>

    </main>


    <footer class="blog-footer">

        <div>
            <strong>Jaroa / Blog</strong>
            <span>A place for ideas to grow.</span>
        </div>

        <a href="/">
            Return to Jaroa <span>↗</span>
        </a>

        <div class="blog-footer-bottom">
            <span>© 2026 Jaroa</span>
            <span>Editorial starter theme</span>
        </div>

    </footer>

</div>
