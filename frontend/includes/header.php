<?php
$page_title = $page_title ?? 'Sanjib Sinha';
$page_description = $page_description ?? 'Minimal, high-performance applications and notes by Sanjib Sinha.';
$canonical_url = $canonical_url ?? 'https://sanjibsinha.in/';
$current_page = basename($_SERVER['PHP_SELF']);
$current_tab = $_GET['tab'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> | Sanjib Sinha</title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>">
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-flex">
            <div>
                <h1>Software Repository</h1>
                <p>Minimal, high-performance applications and notes by Sanjib Sinha.</p>
            </div>

            <button id="theme-toggle" class="theme-toggle" type="button" aria-label="Toggle color theme">🌙 Dark Mode</button>
        </div>

        <nav class="container main-nav" aria-label="Main navigation">
            <a href="/?tab=apps" class="nav-link <?php echo ($current_page === 'index.php' && $current_tab !== 'posts') ? 'active' : ''; ?>">Applications</a>
            <a href="/?tab=posts" class="nav-link <?php echo ($current_page === 'index.php' && $current_tab === 'posts') ? 'active' : ''; ?>">Articles</a>
            <a href="/about.php" class="nav-link <?php echo $current_page === 'about.php' ? 'active' : ''; ?>">About</a>
            <a href="/contact.php" class="nav-link <?php echo $current_page === 'contact.php' ? 'active' : ''; ?>">Contact</a>
        </nav>
    </header>
