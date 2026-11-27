<?php
require "includes/init.php";

$errors = [];
$stories = [];
$showSampleNews = false;
$pageSize = 9;
$pageNumber = 1;

if (isset($_GET["page"])) {
    $requestedPage = is_string($_GET["page"])
        ? filter_var($_GET["page"], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])
        : false;
    if ($requestedPage === false) {
        $errors[] = "Please choose a valid news page.";
    } else {
        $pageNumber = $requestedPage;
    }
}

try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM announcements WHERE is_published = :published");
    $countStmt->bindValue(":published", 1, PDO::PARAM_INT);
    $countStmt->execute();
    $totalStories = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalStories / $pageSize));

    if ($pageNumber > $totalPages) {
        $pageNumber = $totalPages;
    }

    $offset = ($pageNumber - 1) * $pageSize;
    $stmt = $pdo->prepare("SELECT a.title, a.tag, a.body, a.created_at, u.full_name AS author_name
                           FROM announcements a
                           JOIN users u ON u.id = a.author_id
                           WHERE a.is_published = :published
                           ORDER BY a.created_at DESC, a.id DESC
                           LIMIT :limit OFFSET :offset");
    $stmt->bindValue(":published", 1, PDO::PARAM_INT);
    $stmt->bindValue(":limit", $pageSize, PDO::PARAM_INT);
    $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
    $stmt->execute();
    $stories = $stmt->fetchAll();

    if ($totalStories == 0) {
        $stories = sampleAnnouncements();
        $showSampleNews = true;
    }
} catch (PDOException $e) {
    $stories = sampleAnnouncements();
    $showSampleNews = true;
    $totalStories = count($stories);
    $totalPages = 1;
    $pageNumber = 1;
    $errors[] = "Live announcements are not available yet. Sample stories are shown.";
}

$pageTitle = "Latest news";
include "includes/header.php";
?>

<div class="container">
    <div class="page-top">
        <div>
            <p class="eyebrow">Horizon Valley University</p>
            <h1>News &amp; notices</h1>
            <p>Updates from across the university community.</p>
        </div>
    </div>

    <?php showErrors($errors); ?>
    <?php if ($showSampleNews && count($errors) == 0) { ?>
        <p class="sample-note">Sample stories are shown until HVU publishes announcements.</p>
    <?php } ?>

    <?php if (count($stories) == 0) { ?>
        <div class="card empty">
            <h2>No announcements yet</h2>
            <p class="muted">Please check back for updates from the university.</p>
        </div>
    <?php } else { ?>
        <div class="news-grid">
            <?php foreach ($stories as $story) { ?>
                <article class="card news-card">
                    <div class="news-meta">
                        <span class="badge"><?php echo e($story["tag"] != "" ? $story["tag"] : "University news"); ?></span>
                        <time datetime="<?php echo e(date("Y-m-d", strtotime($story["created_at"]))); ?>"><?php echo e(niceDate($story["created_at"])); ?></time>
                    </div>
                    <h2><?php echo e($story["title"]); ?></h2>
                    <p><?php echo nl2br(e($story["body"])); ?></p>
                    <p class="news-byline">Posted by <?php echo e($story["author_name"]); ?></p>
                </article>
            <?php } ?>
        </div>
    <?php } ?>

    <?php if (!$showSampleNews && $totalPages > 1) { ?>
        <nav class="pagination" aria-label="News pages">
            <?php if ($pageNumber > 1) { ?>
                <a href="news.php?page=<?php echo $pageNumber - 1; ?>" aria-label="Previous page">Previous</a>
            <?php } else { ?>
                <span class="disabled" aria-disabled="true">Previous</span>
            <?php } ?>
            <?php for ($page = 1; $page <= $totalPages; $page++) { ?>
                <?php if ($page == $pageNumber) { ?>
                    <span class="current" aria-current="page"><?php echo $page; ?></span>
                <?php } else { ?>
                    <a href="news.php?page=<?php echo $page; ?>" aria-label="Page <?php echo $page; ?>"><?php echo $page; ?></a>
                <?php } ?>
            <?php } ?>
            <?php if ($pageNumber < $totalPages) { ?>
                <a href="news.php?page=<?php echo $pageNumber + 1; ?>" aria-label="Next page">Next</a>
            <?php } else { ?>
                <span class="disabled" aria-disabled="true">Next</span>
            <?php } ?>
        </nav>
    <?php } ?>
</div>

<?php include "includes/footer.php"; ?>