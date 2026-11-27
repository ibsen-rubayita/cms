<?php
require "../includes/require_login.php";

$complaints = [];
$errors = [];
$search = isset($_GET["search"]) && is_string($_GET["search"]) ? trim($_GET["search"]) : "";
$pageNumber = 1;
$pageSize = 10;
$totalPages = 1;
$totalComplaints = 0;

if (isset($_GET["page"])) {
    $requestedPage = is_string($_GET["page"])
        ? filter_var($_GET["page"], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])
        : false;
    if ($requestedPage === false) {
        $errors[] = "Please choose a valid complaints page.";
    } else {
        $pageNumber = $requestedPage;
    }
}
if (strlen($search) > 100) {
    $errors[] = "Search must be 100 characters or fewer.";
    $search = substr($search, 0, 100);
}

try {
    $fromSql = "FROM complaints c
                JOIN categories cat ON cat.id = c.category_id
                WHERE c.user_id = :uid";
    if ($search != "") {
        $fromSql .= " AND (c.title LIKE :search_title OR c.reference_no LIKE :search_reference)";
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) " . $fromSql);
    $stmt->bindValue(":uid", $_SESSION["user_id"], PDO::PARAM_INT);
    if ($search != "") {
        $stmt->bindValue(":search_title", "%" . $search . "%");
        $stmt->bindValue(":search_reference", "%" . $search . "%");
    }
    $stmt->execute();
    $totalComplaints = (int)$stmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalComplaints / $pageSize));
    if ($pageNumber > $totalPages) {
        $pageNumber = $totalPages;
    }

    $offset = ($pageNumber - 1) * $pageSize;
    $stmt = $pdo->prepare("SELECT c.id, c.reference_no, c.title, c.status, c.priority, c.created_at, cat.name AS category
                           " . $fromSql . "
                           ORDER BY c.created_at DESC, c.id DESC
                           LIMIT :limit OFFSET :offset");
    $stmt->bindValue(":uid", $_SESSION["user_id"], PDO::PARAM_INT);
    if ($search != "") {
        $stmt->bindValue(":search_title", "%" . $search . "%");
        $stmt->bindValue(":search_reference", "%" . $search . "%");
    }
    $stmt->bindValue(":limit", $pageSize, PDO::PARAM_INT);
    $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
    $stmt->execute();
    $complaints = $stmt->fetchAll();
} catch (PDOException $e) {
    $errors[] = "We could not load your complaints right now. Please try again.";
}

$pageTitle = "My complaints";
include "../includes/header.php";
?>

<div class="container">
    <?php showFlash(); ?>
    <?php showErrors($errors); ?>

    <div class="page-top">
        <div>
            <h1>My complaints</h1>
            <p class="muted"><?php echo $totalComplaints; ?> complaints. Everything you have shared with us, and where it stands.</p>
        </div>
        <a class="btn btn-dark" href="submit.php">Write a complaint</a>
    </div>

    <form method="GET" class="filters complaint-search">
        <div class="form-group">
            <label for="search">Search complaints</label>
            <input type="search" id="search" name="search" value="<?php echo e($search); ?>" placeholder="Title or reference number">
        </div>
        <div>
            <button type="submit" class="btn btn-dark">Search</button>
            <a class="btn btn-outline" href="my_complaints.php">Clear</a>
        </div>
    </form>

    <?php if (count($complaints) == 0) { ?>
        <div class="card empty">
            <h3><?php echo $search == "" ? "You have not sent any complaints yet" : "No complaints match your search"; ?></h3>
            <p class="muted" style="margin-bottom: 16px;">We are here whenever you need us.</p>
            <?php if ($search == "") { ?><a class="btn btn-dark" href="submit.php">Write your first complaint</a><?php } ?>
        </div>
    <?php } else { ?>
        <div class="table-wrap">
            <table>
                <tr><th>Reference</th><th>Title</th><th>Priority</th><th>Category</th><th>Status</th><th>Sent on</th><th></th></tr>
                <?php foreach ($complaints as $c) { ?>
                    <tr>
                        <td data-label="Reference"><span class="ref"><?php echo e($c["reference_no"]); ?></span></td>
                        <td data-label="Title"><?php echo e($c["title"]); ?></td>
                        <td data-label="Priority"><span class="priority-badge priority-<?php echo e($c["priority"]); ?>"><?php echo e(ucfirst($c["priority"])); ?></span></td>
                        <td data-label="Category"><?php echo e($c["category"]); ?></td>
                        <td data-label="Status"><?php echo statusBadge($c["status"]); ?></td>
                        <td data-label="Sent on"><?php echo niceDate($c["created_at"]); ?></td>
                        <td data-label="Details"><a href="view.php?id=<?php echo $c["id"]; ?>">Open</a></td>
                    </tr>
                <?php } ?>
            </table>
        </div>
        <?php if ($totalPages > 1) { ?>
            <nav class="pagination" aria-label="Complaint pages">
                <?php if ($pageNumber > 1) { ?><a href="?page=<?php echo $pageNumber - 1; ?>&amp;search=<?php echo urlencode($search); ?>" aria-label="Previous page">Previous</a><?php } ?>
                <?php $startPage = max(1, $pageNumber - 2); $endPage = min($totalPages, $pageNumber + 2); for ($page = $startPage; $page <= $endPage; $page++) { ?>
                    <?php if ($page == $pageNumber) { ?><span class="current" aria-current="page"><?php echo $page; ?></span><?php } else { ?>
                        <a href="?page=<?php echo $page; ?>&amp;search=<?php echo urlencode($search); ?>" aria-label="Page <?php echo $page; ?>"><?php echo $page; ?></a>
                    <?php } ?>
                <?php } ?>
                <?php if ($pageNumber < $totalPages) { ?><a href="?page=<?php echo $pageNumber + 1; ?>&amp;search=<?php echo urlencode($search); ?>" aria-label="Next page">Next</a><?php } ?>
            </nav>
        <?php } ?>
    <?php } ?>
</div>

<?php include "../includes/footer.php"; ?>
