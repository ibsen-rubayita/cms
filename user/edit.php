<?php
require "../includes/require_login.php";

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
$errors = [];
$complaint = false;
$priority = "low";

try {
    // Only my own complaint, and only while it is still pending
    $stmt = $pdo->prepare("SELECT * FROM complaints WHERE id = :id AND user_id = :uid AND status = 'pending'");
    $stmt->bindValue(":id", $id, PDO::PARAM_INT);
    $stmt->bindValue(":uid", $_SESSION["user_id"], PDO::PARAM_INT);
    $stmt->execute();
    $complaint = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT id, name FROM categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
    $errors[] = "We could not load this complaint right now. Please try again.";
}

if (!$complaint && count($errors) == 0) {
    setFlash("error", "That complaint cannot be edited. It may not exist, or it is already being handled.");
    header("Location: my_complaints.php");
    exit;
}

if ($complaint) {
    $title = $complaint["title"];
    $description = $complaint["description"];
    $category_id = $complaint["category_id"];
    $anonymous = $complaint["is_anonymous"];
    $priority = $complaint["priority"];
}

if ($complaint && $_SERVER["REQUEST_METHOD"] == "POST") {
    $title = isset($_POST["title"]) && is_string($_POST["title"]) ? trim($_POST["title"]) : "";
    $description = isset($_POST["description"]) && is_string($_POST["description"]) ? trim($_POST["description"]) : "";
    $category_id = isset($_POST["category_id"]) && is_string($_POST["category_id"]) ? $_POST["category_id"] : "";
    $priority = isset($_POST["priority"]) && is_string($_POST["priority"]) ? $_POST["priority"] : "";
    $anonymous = isset($_POST["is_anonymous"]) ? 1 : 0;

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
            $attachment = saveComplaintAttachment(isset($_FILES["attachment"]) ? $_FILES["attachment"] : null);
            if ($attachment["error"] != "") {
                $errors[] = $attachment["error"];
            } else {
                $stmt = $pdo->prepare("UPDATE complaints
                                   SET category_id = :cid, title = :title, description = :description, is_anonymous = :anon,
                                       priority = :priority,
                                       attachment_name = COALESCE(:attachment_name, attachment_name),
                                       attachment_file = COALESCE(:attachment_file, attachment_file)
                                   WHERE id = :id AND user_id = :uid AND status = 'pending'");
                $stmt->bindValue(":cid", $category_id, PDO::PARAM_INT);
                $stmt->bindValue(":title", $title);
                $stmt->bindValue(":description", $description);
                $stmt->bindValue(":anon", $anonymous, PDO::PARAM_INT);
                $stmt->bindValue(":priority", $priority);
                $stmt->bindValue(":attachment_name", $attachment["original_name"], $attachment["original_name"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $stmt->bindValue(":attachment_file", $attachment["stored_file"], $attachment["stored_file"] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $stmt->bindValue(":id", $id, PDO::PARAM_INT);
                $stmt->bindValue(":uid", $_SESSION["user_id"], PDO::PARAM_INT);
                $stmt->execute();

                if ($attachment["stored_file"] !== null && $stmt->rowCount() == 0) {
                    deleteComplaintAttachment($attachment["stored_file"]);
                    $errors[] = "This complaint is no longer available to edit.";
                } else {
                    if ($attachment["stored_file"] !== null) {
                        deleteComplaintAttachment($complaint["attachment_file"]);
                    }
                    setFlash("success", "Your complaint has been updated.");
                    header("Location: view.php?id=" . $id);
                    exit;
                }
            }
        } catch (PDOException $e) {
            deleteComplaintAttachment($attachment["stored_file"]);
            $errors[] = "We could not save your changes. Please check the category and try again.";
        }
    }
}

$pageTitle = "Edit complaint";
include "../includes/header.php";
?>

<div class="container" style="max-width: 760px;">
    <div class="page-top">
        <div>
            <p class="eyebrow">My complaints</p>
            <h1>Edit your complaint</h1>
            <p class="muted">You can change the details until our team starts working on it.</p>
        </div>
    </div>

    <?php showErrors($errors); ?>

    <?php if ($complaint) { ?>
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
            <?php if (!empty($complaint["attachment_name"])) { ?>
                <p class="hint">Current attachment: <a href="<?php echo BASE_URL; ?>/attachment.php?id=<?php echo $complaint["id"]; ?>"><?php echo e($complaint["attachment_name"]); ?></a></p>
            <?php } ?>
            <div class="form-group">
                <label for="attachment">Replace attachment (optional)</label>
                <input type="file" id="attachment" name="attachment" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf">
                <p class="hint">JPG, PNG or PDF. Maximum 2 MB. Leave empty to keep the current file.</p>
            </div>
            <div class="form-group">
                <div class="check-row">
                    <input type="checkbox" id="is_anonymous" name="is_anonymous" value="1" <?php if ($anonymous) { echo "checked"; } ?>>
                    <div>
                        <label for="is_anonymous">Send this anonymously</label>
                        <p class="hint">The staff will see your concern but not your name.</p>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-dark">Save changes</button>
            <a href="view.php?id=<?php echo $id; ?>" class="btn btn-outline">Cancel</a>
        </form>
    </div>
    <?php } ?>
</div>

<?php include "../includes/footer.php"; ?>
