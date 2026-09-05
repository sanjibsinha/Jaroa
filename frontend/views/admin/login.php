<?php

$pageTitle = 'Jaroa · Admin Login';

$error = $error ?? null;
$email = $email ?? '';
?>

<section class="admin-login-page">

    <div class="admin-login-panel">

        <div class="admin-login-brand">
            <span class="admin-logo">J</span>

            <div>
                <strong>Jaroa</strong>
                <span>Admin Console</span>
            </div>
        </div>

        <div class="admin-login-intro">

            <p class="admin-kicker">
                ADMINISTRATION
            </p>

            <h1>
                Welcome back.
            </h1>

            <p>
                Sign in to manage your Jaroa application,
                content and starter templates.
            </p>

        </div>

        <?php if ($error !== null): ?>

            <div class="admin-alert admin-alert-error">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>

        <form
            class="admin-form"
            method="post"
            action="/admin/login"
        >

            <label>
                <span>Email</span>

                <input
                    type="email"
                    name="email"
                    value="<?= htmlspecialchars($email) ?>"
                    autocomplete="email"
                    required
                    autofocus
                >
            </label>

            <label>
                <span>Password</span>

                <input
                    type="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >
            </label>

            <button
                class="admin-button admin-button-primary"
                type="submit"
            >
                Enter Jaroa
                <span>↗</span>
            </button>

        </form>

        <a
            class="admin-back-link"
            href="/"
        >
            ← Return to application
        </a>

    </div>

    <div class="admin-login-art">
        <div class="admin-orbit admin-orbit-a"></div>
        <div class="admin-orbit admin-orbit-b"></div>
        <div class="admin-orbit admin-orbit-c"></div>

        <div class="admin-orbit-core">
            J
        </div>

        <span class="admin-coordinate">
            JAROA / 01
        </span>
    </div>

</section>
