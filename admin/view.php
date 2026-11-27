<?php
require "../includes/require_admin.php";

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
$errors = [];
$message = "";

// ---- Handle the three possible actions (reply, change status, delete) ----
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST["action"];
    $id = (int)$_POST["id"];

    try {
        if ($action == "reply") {
            $message = trim($_POST["message"]);
            if ($message == "") {
                $errors[] = "Please write a reply before sending.";
            } elseif (strlen($message) < 5) {
                $errors[] = "Your reply is a bit too short. Please add a few more words.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO responses (complaint_id, admin_id, message) VALUES (:cid, :aid, :message)");
                $stmt->bindValue(":cid", $id, PDO::PARAM_INT);
                $stmt->bindValue(":aid", $_SESSION["user_id"], PDO::PARAM_INT);
                $stmt->bindValue(":message", $message);
                $stmt->execute();

                // A first reply means someone is now looking into it
                $stmt = $pdo->prepare("UPDATE complaints SET status = 'in_progress' WHERE id = :id AND status = 'pending'");
                $stmt->bindValue(":id", $id, PDO::PARAM_INT);
                $stmt->execute();

                setFlash("success", "Your reply has been sent.");
                header("Location: view.php?id=" . $id);
                exit;
            }
        } elseif ($action == "status") {
            $newStatus = $_POST["status"];
            if ($newStatus != "pending" && $newStatus != "in_progress" && $newStatus != "resolved") {
                $errors[] = "Please choose a valid status.";
            } else {
                $stmt = $pdo->prepare("UPDATE complaints
                                       SET status = :status,
                                           resolved_at = CASE WHEN :resolved_status = 'resolved' THEN COALESCE(resolved_at, NOW()) ELSE NULL END
                                       WHERE id = :id");
                $stmt->bindValue(":status", $newStatus);
                $stmt->bindValue(":resolved_status", $newStatus);
                $stmt->bindValue(":id", $id, PDO::PARAM_INT);
                $stmt->execute();

                setFlash("success", "The status has been updated.");
                header("Location: view.php?id=" . $id);
                exit;
            }
        } elseif ($action == "delete") {
            $stmt = $pdo->prepare("SELECT attachment_file FROM complaints WHERE id = :id");
            $stmt->bindValue(":id", $id, PDO::PARAM_INT);
            $stmt->execute();
            $attachment = $stmt->fetch();

            $stmt = $pdo->prepare("DELETE FROM complaints WHERE id = :id");
            $stmt->bindValue(":id", $id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                if ($attachment) {
                    deleteComplaintAttachment($attachment["attachment_file"]);
                }
                setFlash("success", "The complaint has been deleted.");
                header("Location: complaints.php");
                exit;
            }
            $errors[] = "That complaint could not be deleted.";
        }
    } catch (PDOException $e) {
        $errors[] = "Something went wrong on our side. Please try again.";
    }
}

// ---- Load the complaint and its replies ----
$complaint = false;
$responses = [];

try {
    $stmt = $pdo->prepare("SELECT c.*, u.full_name, u.email, cat.name AS category
                           FROM complaints c
                           JOIN users u ON u.id = c.user_id
                           JOIN categories cat ON cat.id = c.category_id
                           WHERE c.id = :id");
    $stmt->bindValue(":id", $id, PDO::PARAM_INT);
    $stmt->execute();
    $complaint = $stmt->fetch();

    if ($complaint) {
        $stmt = $pdo->prepare("SELECT r.message, r.created_at, u.full_name
                               FROM responses r
                               JOIN users u ON u.id = r.admin_id
                               WHERE r.complaint_id = :id
                               ORDER BY r.created_at ASC, r.id ASC");
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $responses = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $errors[] = "We could not load this complaint. Please try again.";
}

if (!$complaint && count($errors) == 0) {
    setFlash("error", "We could not find that complaint.");
    header("Location: complaints.php");
    exit;
}

$pageTitle = "Complaint details";
include "../includes/header.php";
?>

<div class="container">
    <?php showFlash(); ?>
    <?php showErrors($errors); ?>

    <?php if ($complaint) { ?>
        <div class="page-top">
            <div>
                <p class="muted small"><a href="complaints.php">Back to all complaints</a></p>
                <h1><?php echo e($complaint["title"]); ?></h1>
                <p class="muted"><?php echo e($complaint["reference_no"]); ?> &middot; sent <?php echo niceDate($complaint["created_at"]); ?></p>
            </div>
            <?php echo statusBadge($complaint["status"]); ?>
        </div>

        <div class="detail-grid">
            <div>
                <?php
                $progressSteps = ["pending" => "Received", "in_progress" => "In progress", "resolved" => "Resolved"];
                $currentStep = $complaint["status"] == "pending" ? 0 : ($complaint["status"] == "in_progress" ? 1 : 2);
                ?>
                <ol class="progress-tracker" aria-label="Complaint progress">
                    <?php $stepIndex = 0; foreach ($progressSteps as $stepLabel) { ?>
                        <li class="<?php echo $stepIndex < $currentStep ? "complete" : ($stepIndex == $currentStep ? "current" : ""); ?>" <?php if ($stepIndex == $currentStep) { ?>aria-current="step"<?php } ?>>
                            <span class="progress-marker"><?php echo $stepIndex < $currentStep ? iconSvg("check") : ($stepIndex + 1); ?></span>
                            <span><?php echo e($stepLabel); ?></span>
                        </li>
                    <?php $stepIndex++; } ?>
                </ol>

                <div class="card">
                    <h3>The complaint</h3>
                    <p class="body-text"><?php echo e($complaint["description"]); ?></p>
                    <?php if (!empty($complaint["attachment_name"])) { ?>
                        <p class="attachment-link">Attachment: <a href="<?php echo BASE_URL; ?>/attachment.php?id=<?php echo $complaint["id"]; ?>"><?php echo e($complaint["attachment_name"]); ?></a></p>
                    <?php } ?>
                </div>

                <div class="thread">
                    <h2>Conversation</h2>
                    <?php if (count($responses) == 0) { ?>
                        <div class="card"><p class="muted">No replies yet. Be the first to respond.</p></div>
                    <?php } else { ?>
                        <?php foreach ($responses as $r) { ?>
                            <div class="reply">
                                <span class="who"><?php echo e($r["full_name"]); ?></span>
                                <span class="when"> &middot; <?php echo niceDate($r["created_at"]); ?></span>
                                <p><?php echo e($r["message"]); ?></p>
                            </div>
                        <?php } ?>
                    <?php } ?>
                </div>

                <div class="card" style="margin-top: 20px;">
                    <h3>Write a reply</h3>
                    <form method="POST">
                        <?php csrfField(); ?>
                        <input type="hidden" name="action" value="reply">
                        <input type="hidden" name="id" value="<?php echo $complaint["id"]; ?>">
                        <div class="form-group">
                            <textarea name="message" placeholder="Thank the person, say what you will do, or ask for more details."><?php echo e($message); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-dark">Send reply</button>
                    </form>
                </div>
            </div>

            <div>
                <div class="card" style="margin-bottom: 20px;">
                    <h3>Details</h3>
                    <ul class="meta">
                        <li><span>From</span><span><?php echo $complaint["is_anonymous"] ? "Anonymous" : e($complaint["full_name"]); ?></span></li>
                        <?php if (!$complaint["is_anonymous"]) { ?>
                            <li><span>Email</span><span><?php echo e($complaint["email"]); ?></span></li>
                        <?php } ?>
                        <li><span>Category</span><span><?php echo e($complaint["category"]); ?></span></li>
                        <li><span>Priority</span><span class="priority-badge priority-<?php echo e($complaint["priority"]); ?>"><?php echo e(ucfirst($complaint["priority"])); ?></span></li>
                        <li><span>Last update</span><span><?php echo niceDate($complaint["updated_at"]); ?></span></li>
                    </ul>
                </div>

                <div class="card" style="margin-bottom: 20px;">
                    <h3>Change status</h3>
                    <form method="POST">
                        <?php csrfField(); ?>
                        <input type="hidden" name="action" value="status">
                        <input type="hidden" name="id" value="<?php echo $complaint["id"]; ?>">
                        <div class="form-group">
                            <select name="status">
                                <option value="pending" <?php if ($complaint["status"] == "pending") { echo "selected"; } ?>>Received</option>
                                <option value="in_progress" <?php if ($complaint["status"] == "in_progress") { echo "selected"; } ?>>In progress</option>
                                <option value="resolved" <?php if ($complaint["status"] == "resolved") { echo "selected"; } ?>>Resolved</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-dark btn-small">Update status</button>
                    </form>
                </div>

                <div class="card">
                    <h3>Remove complaint</h3>
                    <p class="hint" style="margin-bottom: 12px;">Deleting also removes all replies. This cannot be undone.</p>
                    <form method="POST" data-confirm="Delete this complaint and all its replies?">
                        <?php csrfField(); ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $complaint["id"]; ?>">
                        <button type="submit" class="btn btn-danger btn-small">Delete complaint</button>
                    </form>
                </div>
            </div>
        </div>
    <?php } ?>
</div>

<?php include "../includes/footer.php"; ?>
