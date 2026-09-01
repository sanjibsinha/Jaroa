<?php

$pageTitle = '404 - Page Not Found';
$pageDescription = 'The page you requested could not be found.';
?>

<section>

    <h1>404</h1>

    <h2>Page Not Found</h2>

    <p>
        <?= htmlspecialchars($pageDescription) ?>
    </p>

    <p>
        <a href="/">
            Back to <?= htmlspecialchars($appName) ?>
        </a>
    </p>

</section>