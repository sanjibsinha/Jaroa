<?php

$pageTitle = 'Jaroa · Access Denied';
?>

<section class="admin-message-page">

    <div class="admin-message-card">

        <p class="admin-kicker">
            ACCESS CONTROL
        </p>

        <h1>
            Permission required.
        </h1>

        <p>
            <?= htmlspecialchars(
                $message
                ?? 'You do not have access to this area.'
            ) ?>
        </p>

        <a
            class="admin-button admin-button-primary"
            href="/"
        >
            Return to Jaroa
            <span>↗</span>
        </a>

    </div>

</section>
