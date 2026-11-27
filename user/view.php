<?php
require "../includes/require_login.php";

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
$complaint = false;
$responses = [];
$errors = [];

try {
    // Only the owner can open their own complaint
    $stmt = $pdo->prepare("SELECT c.*, cat.name AS category
                           FROM complaints c
                           JOIN categories cat ON cat.id = c.category_id
                           WHERE c.id = :id AND c.user_id = :uid");
    $stmt->bindValue(":id", $id, PDO::PARAM_INT);
    $stmt->bindValue(":uid", $_SESSION["user_id"], PDO::PARAM_INT);
    $stmt->execute();
    $complaint = $stmt->fetch();

    if ($complaint) {
        $stmt = $pdo->prepare("SELECT message, created_at FROM responses WHERE complaint_id = :id ORDER BY created_at ASC, id ASC");
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        $responses = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $errors[] = "We could not load this complaint right now. Please try again.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $complaint && isset($_POST["action"]) && $_POST["action"] == "rate") {
    $ratingValue = isset($_POST["rating"]) && is_string($_POST["rating"])
        ? filter_var($_POST["rating"], FILTER_VALIDATE_INT)
        : false;
    $feedbackComment = isset($_POST["feedback_comment"]) && is_string($_POST["feedback_comment"])
        ? trim($_POST["feedback_comment"])
        : "";

    if ($complaint["status"] != "resolved") {
        $errors[] = "You can leave feedback after this complaint is resolved.";
    } elseif ($complaint["rating"] !== null) {
        $errors[] = "You have already sent feedback for this complaint.";
    }
    if ($ratingValue === false || $ratingValue < 1 || $ratingValue > 5) {
        $errors[] = "Please choose a rating from 1 to 5 stars.";
    }
    if (strlen($feedbackComment) > 1000) {
        $errors[] = "Your comment is too long (1000 characters at most).";
    }

    if (count($errors) == 0) {
        try {
            $stmt = $pdo->prepare("UPDATE complaints
                                   SET rating = :rating, feedback_comment = :comment
                                   WHERE id = :id AND user_id = :uid AND status = 'resolved' AND rating IS NULL");
            $stmt->bindValue(":rating", $ratingValue, PDO::PARAM_INT);
            $stmt->bindValue(":comment", $feedbackComment == "" ? null : $feedbackComment, $feedbackComment == "" ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(":id", $id, PDO::PARAM_INT);
            $stmt->bindValue(":uid", $_SESSION["user_id"], PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() == 1) {
                setFlash("success", "Thank you for sharing your feedback.");
            } else {
                setFlash("error", "This complaint changed or feedback has already been saved. Please review it again.");
            }
            header("Location: view.php?id=" . $id);
            exit;
        } catch (PDOException $e) {
            $errors[] = "We could not save your feedback. Please try again.";
        }
    }
}

if (!$complaint && count($errors) == 0) {
    setFlash("error", "We could not find that complaint.");
    header("Location: my_complaints.php");
    exit;
}

$pageTitle = "Complaint";
include "../includes/header.php";
?>

<div class="container">
    <?php showFlash(); ?>
    <?php showErrors($errors); ?>

    <?php if ($complaint) { ?>
        <div class="page-top">
            <div>
                <p class="muted small"><a href="my_complaints.php">Back to my complaints</a></p>
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
                    <?php $stepIndex = 0; foreach ($progressSteps as $statusKey => $stepLabel) { ?>
                        <li class="<?php echo $stepIndex < $currentStep ? "complete" : ($stepIndex == $currentStep ? "current" : ""); ?>" <?php if ($stepIndex == $currentStep) { ?>aria-current="step"<?php } ?>>
                            <span class="progress-marker"><?php echo $stepIndex < $currentStep ? iconSvg("check") : ($stepIndex + 1); ?></span>
                            <span><?php echo e($stepLabel); ?></span>
                        </li>
                    <?php $stepIndex++; } ?>
                </ol>

                <div class="status-note">
                    <strong><?php echo statusLabel($complaint["status"]); ?>.</strong>
                    <?php echo statusMessage($complaint["status"]); ?>
                </div>

                <div class="card">
                    <h3>What you told us</h3>
                    <p class="body-text"><?php echo e($complaint["description"]); ?></p>
                    <?php if (!empty($complaint["attachment_name"])) { ?>
                        <p class="attachment-link">Attachment: <a href="<?php echo BASE_URL; ?>/attachment.php?id=<?php echo $complaint["id"]; ?>"><?php echo e($complaint["attachment_name"]); ?></a></p>
                    <?php } ?>
                </div>

                <div class="thread">
                    <h2>Replies from our team</h2>
                    <?php if (count($responses) == 0) { ?>
                        <div class="card">
                            <p class="muted">No replies yet. We will write here as soon as there is news.</p>
                        </div>
                    <?php } else { ?>
                        <?php foreach ($responses as $r) { ?>
                            <div class="reply">
                                <span class="who">Support team</span>
                                <span class="when"> &middot; <?php echo niceDate($r["created_at"]); ?></span>
                                <p><?php echo e($r["message"]); ?></p>
                            </div>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>

            <div class="card">
                <h3>Details</h3>
                <ul class="meta">
                    <li><span>Reference</span><span><?php echo e($complaint["reference_no"]); ?></span></li>
                    <li><span>Category</span><span><?php echo e($complaint["category"]); ?></span></li>
                    <li><span>Sent as</span><span><?php echo $complaint["is_anonymous"] ? "Anonymous" : "With my name"; ?></span></li>
                    <li><span>Priority</span><span class="priority-badge priority-<?php echo e($complaint["priority"]); ?>"><?php echo e(ucfirst($complaint["priority"])); ?></span></li>
                    <li><span>Last update</span><span><?php echo niceDate($complaint["updated_at"]); ?></span></li>
                </ul>

                <?php if ($complaint["status"] == "pending") { ?>
                    <div class="btn-row" style="margin-top: 18px;">
                        <a class="btn btn-outline btn-small" href="edit.php?id=<?php echo $complaint["id"]; ?>">Edit</a>
                        <form method="POST" action="delete.php" class="inline-form" data-confirm="Delete this complaint? This cannot be undone.">
                            <?php csrfField(); ?>
                            <input type="hidden" name="id" value="<?php echo $complaint["id"]; ?>">
                            <button type="submit" class="btn btn-danger btn-small">Delete</button>
                        </form>
                    </div>
                    <p class="hint">You can change or delete a complaint until someone starts working on it.</p>
                <?php } else { ?>
                    <p class="hint" style="margin-top: 14px;">This complaint can no longer be edited because our team has started working on it.</p>
                <?php } ?>
            </div>
        </div>

        <?php if ($complaint["status"] == "resolved") { ?>
            <section class="card feedback-panel">
                <h2>Your feedback</h2>
                <?php if ($complaint["rating"] === null) { ?>
                    <p class="muted">How did we handle your concern? You can send feedback once.</p>
                    <form method="POST">
                        <?php csrfField(); ?>
                        <input type="hidden" name="action" value="rate">
                        <fieldset class="rating-fieldset">
                            <legend>Rate your experience</legend>
                            <div class="rating-options">
                                <?php for ($ratingOption = 1; $ratingOption <= 5; $ratingOption++) { ?>
                                    <label class="rating-choice" aria-label="<?php echo $ratingOption; ?> star<?php echo $ratingOption == 1 ? "" : "s"; ?>">
                                        <input type="radio" name="rating" value="<?php echo $ratingOption; ?>" aria-label="<?php echo $ratingOption; ?> star<?php echo $ratingOption == 1 ? "" : "s"; ?>" required>
                                        <span><?php echo iconSvg("star"); ?></span>
                                    </label>
                                <?php } ?>
                            </div>
                        </fieldset>
                        <div class="form-group">
                            <label for="feedback_comment">Comment (optional)</label>
                            <textarea id="feedback_comment" name="feedback_comment" maxlength="1000"></textarea>
                        </div>
                        <button type="submit" class="btn btn-dark">Send feedback</button>
                    </form>
                <?php } else { ?>
                    <p class="rating-display" aria-label="Your rating: <?php echo (int)$complaint["rating"]; ?> out of 5 stars">
                        <?php for ($ratingOption = 1; $ratingOption <= 5; $ratingOption++) { ?>
                            <span class="<?php echo $ratingOption <= (int)$complaint["rating"] ? "filled" : ""; ?>"><?php echo iconSvg("star"); ?></span>
                        <?php } ?>
                    </p>
                    <?php if (!empty($complaint["feedback_comment"])) { ?>
                        <p class="body-text"><?php echo e($complaint["feedback_comment"]); ?></p>
                    <?php } ?>
                    <p class="hint">Your feedback has been recorded.</p>
                <?php } ?>
            </section>
        <?php } ?>
    <?php } ?>
</div>

<?php include "../includes/footer.php"; ?>
