<?php
require "../includes/require_login.php";

$errors = [];
$title = "";
$description = "";
$category_id = "";
$priority = "low";
$anonymous = 0;

try {
    $stmt = $pdo->prepare("SELECT id, name FROM categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
    $errors[] = "We could not load the categories. Please try again.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = isset($_POST["title"]) && is_string($_POST["title"]) ? trim($_POST["title"]) : "";
    $description = isset($_POST["description"]) && is_string($_POST["description"]) ? trim($_POST["description"]) : "";
    $category_id = isset($_POST["category_id"]) && is_string($_POST["category_id"]) ? $_POST["category_id"] : "";
    $priority = isset($_POST["priority"]) && is_string($_POST["priority"]) ? $_POST["priority"] : "";
    $anonymous = isset($_POST["is_anonymous"]) ? 1 : 0;

    // Validation
    if ($category_id == "") {
        $errors[] = "Please choose a category.";
    }
    if ($title == "") {
        $errors[] = "Please give your complaint a short title.";
    } elseif (strlen($title) > 150) {
        $errors[] = "The title is too long (150 characters at most).";
    }
    if ($description == "") {
        $errors[] = "Please tell us what happened.";
    } elseif (strlen($description) < 10) {
        $errors[] = "Please add a little more detail (at least 10 characters).";
    }
    if (!in_array($priority, ["low", "medium", "high", "urgent"], true)) {
        $errors[] = "Please choose a valid priority.";
    }

    $attachment = ["error" => "", "original_name" => null, "stored_file" => null];
    if (count($errors) == 0) {
        try {
            // Make sure the category really exists
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE id = :id");
            $stmt->bindValue(":id", $category_id, PDO::PARAM_INT);
            $stmt->execute();

            if (!$stmt->fetch()) {
                $errors[] = "That category is not available. Please choose another.";
            } else {
                $attachment = saveComplaintAttachment(isset($_FILES["attachment"]) ? $_FILES["attachment"] : null);
                if ($attachment["error"] != "") {
                    $errors[] = $attachment["error"];
                } else {
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare("INSERT INTO complaints
                                           (user_id, category_id, title, description, is_anonymous, priority, attachment_name, attachment_file)
                                           VALUES (:uid, :cid, :title, :description, :anon, :priority, :attachment_name, :attachment_file)");
                    $stmt->bindValue(":uid", $_SESSION["user_id"], PDO::PARAM_INT);
                    $stmt->bindValue(":cid", $category_id, PDO::PARAM_INT);
                    $stmt->bindValue(":title", $title);
                    $stmt->bindValue(":description", $description);
                    $stmt->bindValue(":anon", $anonymous, PDO::PARAM_INT);
                    $stmt->bindValue(":priority", $priority);
                    $stmt->bindValue(":attachment_name", $attachment["original_name"], $attachment["original_name"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                    $stmt->bindValue(":attachment_file", $attachment["stored_file"], $attachment["stored_file"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                    $stmt->execute();

                    // Create the reference number from the new id
                    $newId = $pdo->lastInsertId();
                    $reference = makeReference($newId);

                    $stmt = $pdo->prepare("UPDATE complaints SET reference_no = :ref WHERE id = :id");
                    $stmt->bindValue(":ref", $reference);
                    $stmt->bindValue(":id", $newId, PDO::PARAM_INT);
                    $stmt->execute();
                    $pdo->commit();

                    setFlash("success", "Thank you, we have received your complaint. Your reference is " . $reference . ".");
                    header("Location: view.php?id=" . $newId);
                    exit;
                }
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            deleteComplaintAttachment($attachment["stored_file"]);
            $errors[] = "Something went wrong on our side. Please try again.";
        }
    }
}

$pageTitle = "New complaint";
include "../includes/header.php";
?>

<div class="container" style="max-width: 760px;">
    <div class="page-top">
        <div>
            <p class="eyebrow">New complaint</p>
            <h1>Tell us what happened</h1>
            <p class="muted">Take your time and explain it in your own words.</p>
        </div>
    </div>

    <?php showErrors($errors); ?>

    <div class="card">
        <form method="POST" enctype="multipart/form-data">
            <?php csrfField(); ?>
            <div class="form-group">
                <label for="category_id">What is it about?</label>
                <select id="category_id" name="category_id">
                    <option value="">Choose a category</option>
                    <?php foreach ($categories as $cat) { ?>
                        <option value="<?php echo $cat["id"]; ?>" <?php if ($category_id == $cat["id"]) { echo "selected"; } ?>><?php echo e($cat["name"]); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group">
                <label for="title">Short title</label>
                <input type="text" id="title" name="title" maxlength="150" value="<?php echo e($title); ?>">
            </div>
            <div class="form-group">
                <label for="description">What happened?</label>
                <textarea id="description" name="description" data-counter="descCount"><?php echo e($description); ?></textarea>
                <p class="hint" id="descCount">0 characters</p>
            </div>
            <div class="form-group">
                <label for="priority">Priority</label>
                <select id="priority" name="priority">
                    <option value="low" <?php if ($priority == "low") { echo "selected"; } ?>>Low</option>
                    <option value="medium" <?php if ($priority == "medium") { echo "selected"; } ?>>Medium</option>
                    <option value="high" <?php if ($priority == "high") { echo "selected"; } ?>>High</option>
                    <option value="urgent" <?php if ($priority == "urgent") { echo "selected"; } ?>>Urgent</option>
                </select>
            </div>
            <div class="form-group">
                <label for="attachment">Attachment (optional)</label>
                <input type="file" id="attachment" name="attachment" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf">
                <p class="hint">JPG, PNG or PDF. Maximum 2 MB.</p>
            </div>
            <div class="form-group">
                <div class="check-row">
                    <input type="checkbox" id="is_anonymous" name="is_anonymous" value="1" <?php if ($anonymous) { echo "checked"; } ?>>
                    <div>
                        <label for="is_anonymous">Send this anonymously</label>
                        <p class="hint">The staff will see your concern but not your name. You can still follow it from your account.</p>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-dark">Send complaint</button>
            <a href="my_complaints.php" class="btn btn-outline">Cancel</a>
        </form>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
