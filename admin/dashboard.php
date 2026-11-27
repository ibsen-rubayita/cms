<?php
require "../includes/require_admin.php";

$counts = ["pending" => 0, "in_progress" => 0, "resolved" => 0];
$priorityCounts = ["low" => 0, "medium" => 0, "high" => 0, "urgent" => 0];
$totalUsers = 0;
$totalCategories = 0;
$averageRating = 0;
$ratedComplaints = 0;
$recent = [];
$errors = [];

try {
    $stmt = $pdo->prepare("SELECT status, COUNT(*) AS total FROM complaints GROUP BY status");
    $stmt->execute();
    foreach ($stmt->fetchAll() as $row) {
        $counts[$row["status"]] = (int)$row["total"];
    }

    $stmt = $pdo->prepare("SELECT priority, COUNT(*) AS total FROM complaints GROUP BY priority");
    $stmt->execute();
    foreach ($stmt->fetchAll() as $row) {
        $priorityCounts[$row["priority"]] = (int)$row["total"];
    }

    $stmt = $pdo->prepare("SELECT COUNT(rating) AS rated_total, COALESCE(AVG(rating), 0) AS average_rating FROM complaints");
    $stmt->execute();
    $ratingStats = $stmt->fetch();
    $ratedComplaints = (int)$ratingStats["rated_total"];
    $averageRating = (float)$ratingStats["average_rating"];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = :role");
    $stmt->bindValue(":role", "user");
    $stmt->execute();
    $totalUsers = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories");
    $stmt->execute();
    $totalCategories = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT c.id, c.reference_no, c.title, c.status, c.created_at, c.is_anonymous, u.full_name, cat.name AS category
                           FROM complaints c
                           JOIN users u ON u.id = c.user_id
                           JOIN categories cat ON cat.id = c.category_id
                           ORDER BY c.created_at DESC, c.id DESC LIMIT 5");
    $stmt->execute();
    $recent = $stmt->fetchAll();
} catch (PDOException $e) {
    $errors[] = "We could not load the dashboard figures. Please try again.";
}

$total = $counts["pending"] + $counts["in_progress"] + $counts["resolved"];
$priorityTotal = array_sum($priorityCounts);

$pageTitle = "Admin dashboard";
include "../includes/header.php";
?>

<div class="container">
    <?php showFlash(); ?>
    <?php showErrors($errors); ?>

    <div class="page-top">
        <div>
            <h1>Dashboard</h1>
            <p class="muted">Welcome, <?php echo e($_SESSION["user_name"]); ?>. Here is how things stand today.</p>
        </div>
        <a class="btn btn-dark" href="complaints.php?status=pending">Review waiting complaints</a>
    </div>

    <div class="grid-4 mb">
        <div class="stat"><div class="number"><?php echo $total; ?></div><div class="label">All complaints</div></div>
        <div class="stat"><div class="number"><?php echo $counts["pending"]; ?></div><div class="label">Waiting (received)</div></div>
        <div class="stat"><div class="number"><?php echo $counts["in_progress"]; ?></div><div class="label">In progress</div></div>
        <div class="stat stat-dark"><div class="number"><?php echo $counts["resolved"]; ?></div><div class="label">Resolved</div></div>
    </div>

    <div class="grid-3 mb">
        <div class="stat"><div class="number"><?php echo $totalUsers; ?></div><div class="label">Registered complainants</div></div>
        <div class="stat"><div class="number"><?php echo $totalCategories; ?></div><div class="label">Categories</div></div>
        <div class="stat"><div class="number"><?php echo number_format($averageRating, 1); ?><span class="stat-suffix"> / 5</span></div><div class="label">Average rating from <?php echo $ratedComplaints; ?> reviews</div></div>
    </div>

    <div class="admin-chart-grid mb">
        <section class="card chart-panel">
            <p class="eyebrow">Workload</p>
            <h2>Complaints by status</h2>
            <div class="bar-chart" role="img" aria-label="Complaints by status">
                <?php foreach (["pending" => "Received", "in_progress" => "In progress", "resolved" => "Resolved"] as $statusKey => $statusText) { ?>
                    <?php $barWidth = $total > 0 ? round($counts[$statusKey] / $total * 100, 1) : 0; ?>
                    <div class="bar-row">
                        <span class="bar-label"><?php echo e($statusText); ?></span>
                        <div class="bar-track"><span class="bar-fill bar-status-<?php echo e($statusKey); ?>" style="width: <?php echo $barWidth; ?>%;"></span></div>
                        <strong><?php echo $counts[$statusKey]; ?></strong>
                    </div>
                <?php } ?>
            </div>
        </section>
        <section class="card chart-panel">
            <p class="eyebrow">Urgency</p>
            <h2>Complaints by priority</h2>
            <div class="bar-chart" role="img" aria-label="Complaints by priority">
                <?php foreach (["low" => "Low", "medium" => "Medium", "high" => "High", "urgent" => "Urgent"] as $priorityKey => $priorityText) { ?>
                    <?php $barWidth = $priorityTotal > 0 ? round($priorityCounts[$priorityKey] / $priorityTotal * 100, 1) : 0; ?>
                    <div class="bar-row">
                        <span class="bar-label"><?php echo e($priorityText); ?></span>
                        <div class="bar-track"><span class="bar-fill bar-priority-<?php echo e($priorityKey); ?>" style="width: <?php echo $barWidth; ?>%;"></span></div>
                        <strong><?php echo $priorityCounts[$priorityKey]; ?></strong>
                    </div>
                <?php } ?>
            </div>
        </section>
    </div>

    <h2>Latest complaints</h2>
    <?php if (count($recent) == 0) { ?>
        <div class="card empty">
            <h3>No complaints yet</h3>
            <p class="muted">When someone shares a concern it will appear here.</p>
        </div>
    <?php } else { ?>
        <div class="table-wrap">
            <table>
                <tr><th>Reference</th><th>Title</th><th>From</th><th>Category</th><th>Status</th><th></th></tr>
                <?php foreach ($recent as $c) { ?>
                    <tr>
                        <td data-label="Reference"><span class="ref"><?php echo e($c["reference_no"]); ?></span></td>
                        <td data-label="Title"><?php echo e($c["title"]); ?></td>
                        <td data-label="From"><?php echo $c["is_anonymous"] ? "Anonymous" : e($c["full_name"]); ?></td>
                        <td data-label="Category"><?php echo e($c["category"]); ?></td>
                        <td data-label="Status"><?php echo statusBadge($c["status"]); ?></td>
                        <td data-label="Details"><a href="view.php?id=<?php echo $c["id"]; ?>">Open</a></td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    <?php } ?>
</div>

<?php include "../includes/footer.php"; ?>
