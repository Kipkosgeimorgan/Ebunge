
<?php
require_once __DIR__ . '/../config.php';

$user = current_user();
$flash = get_flash();
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= e($title ?? APP_NAME) ?></title>

    <link rel="stylesheet"
          href="<?= e(($asset_prefix ?? '') . 'assets/css/style.css') ?>?v=12">
</head>

<body>

<?php if ($user && empty($hide_user_topbar)): ?>

    <header class="topbar">

        <a class="brand"
           href="<?= e(($root_prefix ?? '') . 'landing.php') ?>">

            <img
                src="<?= e(($asset_prefix ?? '') . 'assets/images/logo.png') ?>"
                alt="Elgeyo Marakwet County Assembly Logo"
                class="brand-logo"
            >

            <span>E-Bunge</span>

        </a>

        <div class="top-actions">

            <span class="user-chip">
                <?= e($user['username']) ?>
                ·
                <?= e(role_name($user['role'])) ?>
            </span>

            <a class="btn btn-outline"
               href="<?= e(($root_prefix ?? '') . 'logout.php') ?>">
                Sign out
            </a>

        </div>

    </header>

<?php endif; ?>

<?php if ($flash): ?>

    <div class="flash <?= $flash['type'] === 'success' ? 'flash-success' : 'flash-error' ?>">
        <?= e($flash['message']) ?>
    </div>

<?php endif; ?>
