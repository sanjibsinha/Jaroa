<?php

$pageTitle = '404 · Page Not Found';

$pageDescription =
    'The page you requested could not be found.';

$pageStylesheets = [
    '/assets/css/jaroa-404.css',
];

$showSiteFooter = false;
?>

<section class="error-page">

    <div class="error-card">

        <p class="error-code">
            404
        </p>

        <p class="error-label">
            Jaroa · Page Not Found
        </p>

        <h1>
            This page wandered off somewhere.
        </h1>

        <p class="error-message">
            The address you requested does not exist
            in this Jaroa application. The rest of the
            application is still right where we left it.
        </p>

        <a
            class="error-action"
            href="/"
        >
            ← Back to Jaroa
        </a>

        <div
            class="error-mark"
            aria-hidden="true"
        >
            J
        </div>

        <div class="error-footer">
            Keep exploring
        </div>

    </div>

</section>
