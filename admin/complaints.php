<?php
require "../includes/require_admin.php";

$search = isset($_GET["search"]) && is_string($_GET["search"]) ? trim($_GET["search"]) : "";
$status = isset($_GET["status"]) && is_string($_GET["status"]) ? $_GET["status"] : "";
$category = isset($_GET["category"]) && is_string($_GET["category"]) ? $_GET["category"] : "";
$priority = isset($_GET["priority"]) && is_string($_GET["priority"]) ? $_GET["priority"] : "";
$sort = isset($_GET["sort"]) && is_string($_GET["sort"]) ? $_GET["sort"] : "newest";
$sortOptions = [
    "newest" => "c.created_at DESC, c.id DESC",
    "oldest" => "c.created_at ASC, c.id ASC",
    "title" => "c.title ASC, c.id DESC",
    "priority" => "FIELD(c.priority, 'urgent', 'high', 'medium', 'low'), c.created_at DESC"
];
$pageNumber = 1;
$pageSize = 10;
$totalPages = 1;
$totalComplaints = 0;
$pageUrls = [];

$complaints = [];
$categories = [];
$errors = [];

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
if ($status != "" && !in_array($status, ["pending", "in_progress", "resolved"], true)) {
    $errors[] = "Please choose a valid status filter.";
    $status = "";
}
if ($category != "" && filter_var($category, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]) === false) {
    $errors[] = "Please choose a valid category filter.";
    $category = "";
}
if ($priority != "" && !in_array($priority, ["low", "medium", "high", "urgent"], true)) {
    $errors[] = "Please choose a valid priority filter.";
    $priority = "";
}
if (!isset($sortOptions[$sort])) {
    $errors[] = "Please choose a valid sort order.";
    $sort = "newest";
}

$exportParameters = [];
foreach (["search" => $search, "status" => $status, "category" => $category, "priority" => $priority] as $key => $value) {
    if ($value != "") {
        $exportParameters[$key] = $value;
    }
}
$exportUrl = "export_complaints.php" . (count($exportParameters) > 0 ? "?" . http_build_query($exportParameters) : "");

try {
    $stmt = $pdo->prepare("SELECT id, name FROM categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll();

    $fromSql = "FROM complaints c
                JOIN users u ON u.id = c.user_id
                JOIN categories cat ON cat.id = c.category_id
                WHERE 1 = 1";
    $parameters = [];

    if ($search != "") {
        $fromSql .= " AND (c.title LIKE :search_title OR c.reference_no LIKE :search_reference)";
        $parameters[":search_title"] = ["%" . $search . "%", PDO::PARAM_STR];
        $parameters[":search_reference"] = ["%" . $search . "%", PDO::PARAM_STR];
    }
    if ($status != "") {
        $fromSql .= " AND c.status = :status";
        $parameters[":status"] = [$status, PDO::PARAM_STR];
    }
    if ($category != "") {
        $fromSql .= " AND c.category_id = :category";
        $parameters[":category"] = [(int)$category, PDO::PARAM_INT];
    }
    if ($priority != "") {
        $fromSql .= " AND c.priority = :priority";
        $parameters[":priority"] = [$priority, PDO::PARAM_STR];
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) " . $fromSql);
    foreach ($parameters as $name => $parameter) {
        $stmt->bindValue($name, $parameter[0], $parameter[1]);
    }
    $stmt->execute();
    $totalComplaints = (int)$stmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalComplaints / $pageSize));
    if ($pageNumber > $totalPages) {
        $pageNumber = $totalPages;
    }

    $offset = ($pageNumber - 1) * $pageSize;
    $stmt = $pdo->prepare("SELECT c.id, c.reference_no, c.title, c.status, c.priority, c.created_at, c.is_anonymous, u.full_name, cat.name AS category
                           " . $fromSql . "
                           ORDER BY " . $sortOptions[$sort] . "
                           LIMIT :limit OFFSET :offset");
    foreach ($parameters as $name => $parameter) {
        $stmt->bindValue($name, $parameter[0], $parameter[1]);
    }
    $stmt->bindValue(":limit", $pageSize, PDO::PARAM_INT);
    $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
    $stmt->execute();
    $complaints = $stmt->fetchAll();

    $startPage = max(1, $pageNumber - 2);
    $endPage = min($totalPages, $pageNumber + 2);
    for ($page = $startPage; $page <= $endPage; $page++) {
        $query = ["page" => $page];
        if ($search != "") { $query["search"] = $search; }
        if ($status != "") { $query["status"] = $status; }
        if ($category != "") { $query["category"] = $category; }
        if ($priority != "") { $query["priority"] = $priority; }
        $query["sort"] = $sort;
        $pageUrls[$page] = "complaints.php?" . http_build_query($query);
    }
} catch (PDOException $e) {
    $errors[] = "We could not load the complaints. Please try again.";
}

$pageTitle = "All complaints";
include "../includes/header.php";
?>

<div class="container">
    <?php showFlash(); ?>
    <?php showErrors($errors); ?>

    <div class="page-top">
        <div>
            <h1>All complaints</h1>
            <p class="muted"><?php echo $totalComplaints; ?> found</p>
        </div>
        <a class="btn btn-outline" href="<?php echo e($exportUrl); ?>">Export CSV</a>
    </div>

    <form method="GET" class="filters">
        <div class="form-group">
            <label for="search">Search</label>
            <input type="text" id="search" name="search" placeholder="Title or reference, e.g. CMP-0012" value="<?php echo e($search); ?>">
        </div>
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Any status</option>
                <option value="pending" <?php if ($status == "pending") { echo "selected"; } ?>>Received</option>
                <option value="in_progress" <?php if ($status == "in_progress") { echo "selected"; } ?>>In progress</option>
                <option value="resolved" <?php if ($status == "resolved") { echo "selected"; } ?>>Resolved</option>
            </select>
        </div>
        <div class="form-group">
            <label for="category">Category</label>
            <select id="category" name="category">
                <option value="">Any category</option>
                <?php foreach ($categories as $cat) { ?>
                    <option value="<?php echo $cat["id"]; ?>" <?php if ($category == $cat["id"]) { echo "selected"; } ?>><?php echo e($cat["name"]); ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="form-group">
            <label for="priority">Priority</label>
            <select id="priority" name="priority">
                <option value="">Any priority</option>
                <?php foreach (["low" => "Low", "medium" => "Medium", "high" => "High", "urgent" => "Urgent"] as $priorityValue => $priorityLabel) { ?>
                    <option value="<?php echo e($priorityValue); ?>" <?php if ($priority == $priorityValue) { echo "selected"; } ?>><?php echo e($priorityLabel); ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="form-group">
            <label for="sort">Sort by</label>
            <select id="sort" name="sort">
                <option value="newest" <?php if ($sort == "newest") { echo "selected"; } ?>>Newest</option>
                <option value="oldest" <?php if ($sort == "oldest") { echo "selected"; } ?>>Oldest</option>
                <option value="title" <?php if ($sort == "title") { echo "selected"; } ?>>Title</option>
                <option value="priority" <?php if ($sort == "priority") { echo "selected"; } ?>>Highest priority</option>
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-dark">Filter</button>
            <a href="complaints.php" class="btn btn-outline">Clear</a>
        </div>
    </form>

    <?php if (count($complaints) == 0) { ?>
        <div class="card empty">
            <h3>Nothing matches</h3>
            <p class="muted">Try a different search or clear the filters.</p>
        </div>
    <?php } else { ?>
        <div class="table-wrap">
            <table>
                <tr><th>Reference</th><th>Title</th><th>From</th><th>Category</th><th>Priority</th><th>Status</th><th>Sent on</th><th></th></tr>
                <?php foreach ($complaints as $c) { ?>
                    <tr>
                        <td data-label="Reference"><span class="ref"><?php echo e($c["reference_no"]); ?></span></td>
                        <td data-label="Title"><?php echo e($c["title"]); ?></td>
                        <td data-label="From"><?php echo $c["is_anonymous"] ? "Anonymous" : e($c["full_name"]); ?></td>
                        <td data-label="Category"><?php echo e($c["category"]); ?></td>
                        <td data-label="Priority"><span class="priority-badge priority-<?php echo e($c["priority"]); ?>"><?php echo e(ucfirst($c["priority"])); ?></span></td>
                        <td data-label="Status"><?php echo statusBadge($c["status"]); ?></td>
                        <td data-label="Sent on"><?php echo niceDate($c["created_at"]); ?></td>
                        <td data-label="Details"><a href="view.php?id=<?php echo $c["id"]; ?>">Open</a></td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    <?php } ?>
    <?php if ($totalPages > 1) { ?>
        <nav class="pagination" aria-label="Complaint pages">
            <?php if ($pageNumber > 1) { ?><a href="<?php echo e($pageUrls[$pageNumber - 1]); ?>" aria-label="Previous page">Previous</a><?php } ?>
            <?php for ($page = 1; $page <= $totalPages; $page++) { ?>
                <?php if ($page == $pageNumber) { ?><span class="current" aria-current="page"><?php echo $page; ?></span><?php } else { ?>
                    <a href="<?php echo e($pageUrls[$page]); ?>" aria-label="Page <?php echo $page; ?>"><?php echo $page; ?></a>
                <?php } ?>
            <?php } ?>
            <?php if ($pageNumber < $totalPages) { ?><a href="<?php echo e($pageUrls[$pageNumber + 1]); ?>" aria-label="Next page">Next</a><?php } ?>
        </nav>
    <?php } ?>
</div>

<?php include "../includes/footer.php"; ?>
