<?php

$showSiteFooter = false;

$pageTitle = $profile['name'];
$pageDescription = $profile['bio'];

$pageStylesheets = [
    '/assets/profile/css/profile.css',
];
?>

<section class="profile">

    <div class="profile-hero">

        <div class="profile-hero-content">

            <p class="profile-eyebrow">
                Jaroa Starter Template
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

        </div>

        <div class="profile-mark" aria-hidden="true">
            <span>J</span>
        </div>

    </div>


    <div class="profile-grid">

        <section class="profile-card profile-about">

            <p class="card-label">
                About
            </p>

            <h2>
                Ideas are worth exploring.
            </h2>

            <p>
                This profile is a demonstration of the Jaroa
                starter-template architecture. The presentation
                belongs to the frontend, while the application
                architecture remains independent underneath.
            </p>

            <p>
                Writing, software, design and artificial intelligence
                become different ways of asking the same question:
                what can we build when an idea is given enough room
                to breathe?
            </p>

        </section>


        <section class="profile-card profile-interests">

            <p class="card-label">
                Interests
            </p>

            <ul>
                <li>Writing</li>
                <li>Software Development</li>
                <li>Artificial Intelligence</li>
                <li>Design</li>
                <li>Philosophy</li>
                <li>Creative Technology</li>
            </ul>

        </section>

    </div>


    <section class="profile-statement">

        <p>
            “Build the application first.
            Let the architecture reveal itself.”
        </p>

    </section>


    <section class="profile-footer">

        <div>
            <p class="card-label">
                Built with
            </p>

            <h2>
                Jaroa
            </h2>
        </div>

        <a href="/">
            Explore the application →
        </a>

    </section>

</section>