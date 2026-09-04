<?php

$showSiteFooter = false;

$pageTitle = $profile['name'];
$pageDescription = $profile['bio'];

$pageStylesheets = [
    '/themes/profile/assets/css/profile.css',
];
?>

<section class="profile">

    <nav class="profile-nav" aria-label="Profile navigation">
        <a class="profile-nav-brand" href="/">
            <?= htmlspecialchars($profile['name']) ?>
        </a>

        <div class="profile-nav-links">
            <a href="#about">About</a>
            <a href="#work">Work</a>
            <a href="#writing">Writing</a>
            <a href="#contact">Contact</a>
        </div>
    </nav>


    <header class="profile-hero">

        <div class="profile-hero-content">
            <p class="profile-eyebrow">
                Independent writer &amp; creative technologist
            </p>

            <h1>
                <?= htmlspecialchars($profile['name']) ?>
            </h1>

            <p class="profile-role">
                <?= htmlspecialchars($profile['role']) ?>
            </p>

            <p class="profile-introduction">
                <?= htmlspecialchars($profile['bio']) ?>
            </p>

            <div class="profile-hero-meta">
                <span><?= htmlspecialchars($profile['location']) ?></span>
                <span><?= htmlspecialchars($profile['availability']) ?></span>
            </div>
        </div>

        <div class="profile-hero-image">
            <img
                src="<?= htmlspecialchars($profile['hero_image']) ?>"
                alt="Editorial portrait artwork"
            >
        </div>

    </header>


    <section class="profile-about" id="about">

        <div class="profile-section-label">
            <span>01</span>
            About
        </div>

        <div class="profile-about-content">

            <div>
                <h2>
                    A little about<br>
                    the work.
                </h2>
            </div>

            <div class="profile-about-copy">
                <p class="profile-lead">
                    I work across writing, software and creative technology,
                    usually somewhere between the obvious solution and the
                    interesting one.
                </p>

                <p>
                    My work begins with questions. How should an idea move
                    through a system? What makes a digital experience feel
                    human? And what happens when technology gives curiosity
                    somewhere new to go?
                </p>

                <div class="profile-facts">
                    <div>
                        <span>Based in</span>
                        <strong><?= htmlspecialchars($profile['location']) ?></strong>
                    </div>
                    <div>
                        <span>Focus</span>
                        <strong>Writing · Technology</strong>
                    </div>
                    <div>
                        <span>Currently</span>
                        <strong>Independent</strong>
                    </div>
                </div>
            </div>

        </div>

    </section>


    <section class="profile-work" id="work">

        <div class="profile-section-heading">
            <div class="profile-section-label">
                <span>02</span>
                Selected work
            </div>

            <h2>Things I've made.</h2>
        </div>

        <div class="profile-projects">

            <?php foreach ($profile['projects'] as $project): ?>

                <article class="profile-project">

                    <div class="profile-project-image">
                        <img
                            src="<?= htmlspecialchars($project['image']) ?>"
                            alt="<?= htmlspecialchars($project['title']) ?>"
                            loading="lazy"
                        >
                    </div>

                    <div class="profile-project-info">
                        <p class="profile-project-number">
                            <?= htmlspecialchars($project['number']) ?>
                        </p>

                        <p class="profile-project-category">
                            <?= htmlspecialchars($project['category']) ?>
                        </p>

                        <h3>
                            <?= htmlspecialchars($project['title']) ?>
                        </h3>

                        <p>
                            <?= htmlspecialchars($project['description']) ?>
                        </p>

                        <a href="#contact">
                            Discuss a similar project <span>↗</span>
                        </a>
                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    </section>


    <section class="profile-expertise">

        <div class="profile-section-label">
            <span>03</span>
            Expertise
        </div>

        <div class="profile-expertise-content">

            <h2>
                Different tools.<br>
                One curiosity.
            </h2>

            <div class="profile-expertise-list">

                <?php foreach ($profile['expertise'] as $item): ?>

                    <article class="profile-expertise-item">
                        <span><?= htmlspecialchars($item['number']) ?></span>

                        <div>
                            <h3><?= htmlspecialchars($item['title']) ?></h3>
                            <p><?= htmlspecialchars($item['text']) ?></p>
                        </div>
                    </article>

                <?php endforeach; ?>

            </div>

        </div>

    </section>


    <section class="profile-writing" id="writing">

        <div class="profile-section-heading">
            <div class="profile-section-label">
                <span>04</span>
                From the notebook
            </div>

            <h2>Recent thoughts.</h2>
        </div>

        <div class="profile-articles">

            <?php foreach ($profile['articles'] as $article): ?>

                <article class="profile-article">
                    <div class="profile-article-meta">
                        <span><?= htmlspecialchars($article['date']) ?></span>
                        <span><?= htmlspecialchars($article['type']) ?></span>
                    </div>

                    <h3>
                        <?= htmlspecialchars($article['title']) ?>
                    </h3>

                    <p>
                        <?= htmlspecialchars($article['excerpt']) ?>
                    </p>

                    <a href="#contact" aria-label="Read <?= htmlspecialchars($article['title']) ?>">
                        Read essay <span>↗</span>
                    </a>
                </article>

            <?php endforeach; ?>

        </div>

    </section>


    <section class="profile-statement">

        <p class="profile-statement-mark">“</p>

        <p>
            <?= htmlspecialchars($profile['statement']) ?>
        </p>

    </section>


    <section class="profile-contact" id="contact">

        <div class="profile-section-label">
            <span>05</span>
            Get in touch
        </div>

        <div class="profile-contact-content">
            <p class="profile-contact-kicker">
                Have an idea?
            </p>

            <h2>
                Let's make something
                <em>worth remembering.</em>
            </h2>

            <a class="profile-contact-link" href="mailto:<?= htmlspecialchars($profile['email']) ?>">
                <?= htmlspecialchars($profile['email']) ?>
                <span>↗</span>
            </a>
        </div>

    </section>


    <footer class="profile-footer">

        <div>
            <strong><?= htmlspecialchars($profile['name']) ?></strong>
            <span>Writing · Technology · Ideas</span>
        </div>

        <div class="profile-footer-links">
            <a href="#about">About</a>
            <a href="#work">Work</a>
            <a href="#writing">Writing</a>
            <a href="#contact">Contact</a>
        </div>

        <div class="profile-footer-bottom">
            <span>© 2026 <?= htmlspecialchars($profile['name']) ?></span>
            <span>Powered by Jaroa</span>
        </div>

    </footer>

</section>
