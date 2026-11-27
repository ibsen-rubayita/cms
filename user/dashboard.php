<?php
require "../includes/require_login.php";

// Admins have their own dashboard
if ($_SESSION["role"] == "admin") {
    header("Location: ../admin/dashboard.php");
    exit;
}

$counts = ["pending" => 0, "in_progress" => 0, "resolved" => 0];
$recent = [];

try {
    // Count my complaints by status
    $stmt = $pdo->prepare("SELECT status, COUNT(*) AS total FROM complaints WHERE user_id = :uid GROUP BY status");
    $stmt->bindValue(":uid", $_SESSION["user_id"]);
    $stmt->execute();
    foreach ($stmt->fetchAll() as $row) {
        $counts[$row["status"]] = $row["total"];
    }

    // My three latest complaints
    $stmt = $pdo->prepare("SELECT id, reference_no, title, status, created_at FROM complaints WHERE user_id = :uid ORDER BY created_at DESC, id DESC LIMIT 3");
    $stmt->bindValue(":uid", $_SESSION["user_id"]);
    $stmt->execute();
    $recent = $stmt->fetchAll();
} catch (PDOException $e) {
    $dbError = "We could not load your information right now. Please try again.";
}

$pageTitle = "My space";
include "../includes/header.php";
?>

<div class="container">
    <?php showFlash(); ?>
    <?php if (isset($dbError)) { showErrors([$dbError]); } ?>

    <div class="page-top">
        <div>
            <h1>Hello, <?php echo e($_SESSION["user_name"]); ?></h1>
            <p class="muted">This is your space. Share a concern or check how an earlier one is going.</p>
        </div>
        <a class="btn btn-dark" href="submit.php">Write a complaint</a>
    </div>

    <div class="grid-3 mb">
        <div class="stat"><div class="number"><?php echo $counts["pending"]; ?></div><div class="label">Received</div></div>
        <div class="stat"><div class="number"><?php echo $counts["in_progress"]; ?></div><div class="label">Being looked into</div></div>
        <div class="stat stat-dark"><div class="number"><?php echo $counts["resolved"]; ?></div><div class="label">Resolved</div></div>
    </div>

    <h2>Your latest complaints</h2>
    <?php if (count($recent) == 0) { ?>
        <div class="card empty">
            <h3>You have not sent any complaints yet</h3>
            <p class="muted" style="margin-bottom: 16px;">We are here whenever you need us.</p>
            <a class="btn btn-dark" href="submit.php">Write your first complaint</a>
        </div>
    <?php } else { ?>
        <div class="table-wrap">
            <table>
                <tr><th>Reference</th><th>Title</th><th>Status</th><th>Sent on</th><th></th></tr>
                <?php foreach ($recent as $c) { ?>
                    <tr>
                        <td data-label="Reference"><span class="ref"><?php echo e($c["reference_no"]); ?></span></td>
                        <td data-label="Title"><?php echo e($c["title"]); ?></td>
                        <td data-label="Status"><?php echo statusBadge($c["status"]); ?></td>
                        <td data-label="Sent on"><?php echo niceDate($c["created_at"]); ?></td>
                        <td data-label="Details"><a href="view.php?id=<?php echo $c["id"]; ?>">Open</a></td>
                    </tr>
                <?php } ?>
            </table>
        </div>
        <p style="margin-top: 14px;"><a href="my_complaints.php">See all my complaints</a></p>
    <?php } ?>
</div>

<?php include "../includes/footer.php"; ?>
