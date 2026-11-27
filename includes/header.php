<?php
require_once __DIR__ . "/init.php";
if (!isset($pageTitle)) {
    $pageTitle = $site["app_name"];
}
$current = basename($_SERVER["PHP_SELF"]);
$loggedIn = isset($_SESSION["user_id"]);
$isAdmin = $loggedIn && $_SESSION["role"] == "admin";

// Mark the menu link of the page we are on.
function navClass($files, $current) {
    return in_array($current, $files) ? "active" : "";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($pageTitle); ?> | <?php echo e($site["university_short_name"] . " " . $site["app_name"]); ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="container nav-inner">
        <a class="brand" href="<?php echo BASE_URL; ?>/index.php">
            <span class="brand-emblem"><?php echo iconSvg("voice"); ?></span>
            <span>
                <span class="brand-name"><?php echo e($site["app_name"]); ?></span>
                <span class="brand-meta"><?php echo e($site["university_short_name"]); ?> | <?php echo e($site["university_name"]); ?></span>
            </span>
        </a>

        <button type="button" class="nav-toggle" id="navToggle" aria-label="Toggle navigation" aria-controls="navLinks" aria-expanded="false">
            <?php echo iconSvg("menu"); ?><span>Menu</span>
        </button>

        <nav class="nav-links" id="navLinks">
            <?php if ($isAdmin) { ?>
                <a class="<?php echo navClass(["dashboard.php"], $current); ?>" href="<?php echo BASE_URL; ?>/admin/dashboard.php"><?php echo iconSvg("home"); ?>Dashboard</a>
                <a class="<?php echo navClass(["complaints.php", "view.php"], $current); ?>" href="<?php echo BASE_URL; ?>/admin/complaints.php"><?php echo iconSvg("inbox"); ?>Complaints</a>
                <a class="<?php echo navClass(["categories.php", "category_edit.php"], $current); ?>" href="<?php echo BASE_URL; ?>/admin/categories.php"><?php echo iconSvg("reference"); ?>Categories</a>
                <a class="<?php echo navClass(["users.php"], $current); ?>" href="<?php echo BASE_URL; ?>/admin/users.php"><?php echo iconSvg("voice"); ?>Users</a>
                <a class="<?php echo navClass(["announcements.php"], $current); ?>" href="<?php echo BASE_URL; ?>/admin/announcements.php">Announcements</a>
                <a class="<?php echo navClass(["news.php"], $current); ?>" href="<?php echo BASE_URL; ?>/news.php">News</a>
                <a class="<?php echo navClass(["profile.php"], $current); ?>" href="<?php echo BASE_URL; ?>/account/profile.php">Profile</a>
                <form method="POST" action="<?php echo BASE_URL; ?>/auth/logout.php" class="nav-logout-form">
                    <?php csrfField(); ?>
                    <button type="submit" class="btn btn-light btn-small"><?php echo iconSvg("logout"); ?>Logout</button>
                </form>
            <?php } elseif ($loggedIn) { ?>
                <a class="<?php echo navClass(["dashboard.php"], $current); ?>" href="<?php echo BASE_URL; ?>/user/dashboard.php"><?php echo iconSvg("home"); ?>Home</a>
                <a class="<?php echo navClass(["my_complaints.php", "view.php", "edit.php"], $current); ?>" href="<?php echo BASE_URL; ?>/user/my_complaints.php"><?php echo iconSvg("inbox"); ?>My complaints</a>
                <a class="<?php echo navClass(["submit.php"], $current); ?>" href="<?php echo BASE_URL; ?>/user/submit.php"><?php echo iconSvg("plus"); ?>New complaint</a>
                <a class="<?php echo navClass(["news.php"], $current); ?>" href="<?php echo BASE_URL; ?>/news.php">News</a>
                <a class="<?php echo navClass(["profile.php"], $current); ?>" href="<?php echo BASE_URL; ?>/account/profile.php">Profile</a>
                <form method="POST" action="<?php echo BASE_URL; ?>/auth/logout.php" class="nav-logout-form">
                    <?php csrfField(); ?>
                    <button type="submit" class="btn btn-light btn-small"><?php echo iconSvg("logout"); ?>Logout</button>
                </form>
            <?php } else { ?>
                <a href="<?php echo BASE_URL; ?>/index.php#how">How it works</a>
                <a class="<?php echo navClass(["news.php"], $current); ?>" href="<?php echo BASE_URL; ?>/news.php">News</a>
                <a href="<?php echo BASE_URL; ?>/auth/login.php"><?php echo iconSvg("login"); ?>Log in</a>
                <a class="btn btn-light btn-small" href="<?php echo BASE_URL; ?>/auth/register.php"><?php echo iconSvg("plus"); ?>Get started</a>
            <?php } ?>
        </nav>
    </div>
</header>

<main class="<?php echo $current == "index.php" ? "page page-home" : "page"; ?>">
