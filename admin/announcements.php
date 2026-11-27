<?php
require "../includes/require_admin.php";

$errors = [];
$announcementId = 0;
$announcement = false;
$title = "";
$tag = "";
$body = "";
$isPublished = 0;

if (isset($_GET["id"])) {
    $requestedId = is_string($_GET["id"])
        ? filter_var($_GET["id"], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])
        : false;
    if ($requestedId === false) {
        $errors[] = "Please choose a valid announcement.";
    } else {
        $announcementId = $requestedId;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = isset($_POST["action"]) && is_string($_POST["action"]) ? $_POST["action"] : "";
    $postedId = isset($_POST["id"]) && is_string($_POST["id"])
        ? filter_var($_POST["id"], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])
        : false;

    if ($action == "delete") {
        if ($postedId === false) {
            $errors[] = "Please choose a valid announcement to delete.";
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = :id");
                $stmt->bindValue(":id", $postedId, PDO::PARAM_INT);
                $stmt->execute();
                if ($stmt->rowCount() > 0) {
                    setFlash("success", "The announcement has been deleted.");
                } else {
                    setFlash("error", "That announcement could not be found.");
                }
                header("Location: announcements.php");
                exit;
            } catch (PDOException $e) {
                $errors[] = "We could not delete this announcement. Please try again.";
            }
        }
    } elseif ($action == "save") {
        $announcementId = $postedId === false ? 0 : $postedId;
        $title = isset($_POST["title"]) && is_string($_POST["title"]) ? trim($_POST["title"]) : "";
        $tag = isset($_POST["tag"]) && is_string($_POST["tag"]) ? trim($_POST["tag"]) : "";
        $body = isset($_POST["body"]) && is_string($_POST["body"]) ? trim($_POST["body"]) : "";
        $isPublished = isset($_POST["is_published"]) ? 1 : 0;

        if ($announcementId == 0 && isset($_POST["id"]) && $_POST["id"] != "") {
            $errors[] = "Please choose a valid announcement.";
        }
        if ($title == "") {
            $errors[] = "Please enter an announcement title.";
        } elseif (strlen($title) > 150) {
            $errors[] = "The title is too long (150 characters at most).";
        }
        if (strlen($tag) > 50) {
            $errors[] = "The tag is too long (50 characters at most).";
        }
        if ($body == "") {
            $errors[] = "Please enter the announcement text.";
        } elseif (strlen($body) > 20000) {
            $errors[] = "The announcement text is too long (20000 characters at most).";
        }

        if (count($errors) == 0) {
            try {
                if ($announcementId > 0) {
                    $stmt = $pdo->prepare("UPDATE announcements
                                           SET title = :title, tag = :tag, body = :body, is_published = :published
                                           WHERE id = :id");
                    $stmt->bindValue(":id", $announcementId, PDO::PARAM_INT);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO announcements (title, tag, body, is_published, author_id)
                                           VALUES (:title, :tag, :body, :published, :author_id)");
                    $stmt->bindValue(":author_id", $_SESSION["user_id"], PDO::PARAM_INT);
                }
                $stmt->bindValue(":title", $title);
                $stmt->bindValue(":tag", $tag == "" ? null : $tag, $tag == "" ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $stmt->bindValue(":body", $body);
                $stmt->bindValue(":published", $isPublished, PDO::PARAM_INT);
                $stmt->execute();

                setFlash("success", $announcementId > 0 ? "The announcement has been updated." : "The announcement has been created.");
                header("Location: announcements.php");
                exit;
            } catch (PDOException $e) {
                $errors[] = "We could not save this announcement. Please try again.";
            }
        }
    } else {
        $errors[] = "Please choose a valid announcement action.";
    }
}

if ($announcementId > 0 && $_SERVER["REQUEST_METHOD"] != "POST") {
    try {
        $stmt = $pdo->prepare("SELECT id, title, tag, body, is_published FROM announcements WHERE id = :id");
        $stmt->bindValue(":id", $announcementId, PDO::PARAM_INT);
        $stmt->execute();
        $announcement = $stmt->fetch();
        if (!$announcement) {
            $errors[] = "That announcement could not be found.";
            $announcementId = 0;
        } else {
            $title = $announcement["title"];
            $tag = $announcement["tag"] === null ? "" : $announcement["tag"];
            $body = $announcement["body"];
            $isPublished = (int)$announcement["is_published"];
        }
    } catch (PDOException $e) {
        $errors[] = "We could not load this announcement. Please try again.";
    }
}

$announcements = [];
try {
    $stmt = $pdo->prepare("SELECT a.id, a.title, a.tag, a.body, a.is_published, a.created_at, u.full_name AS author_name
                           FROM announcements a
                           JOIN users u ON u.id = a.author_id
                           ORDER BY a.created_at DESC, a.id DESC");
    $stmt->execute();
    $announcements = $stmt->fetchAll();
} catch (PDOException $e) {
    $errors[] = "We could not load announcements. Please try again.";
}

$pageTitle = "Announcements";
include "../includes/header.php";
?>

<div class="container">
    <?php showFlash(); ?>
    <?php showErrors($errors); ?>

    <div class="page-top">
        <div>
            <h1>Announcements</h1>
            <p class="muted">Create and manage university news and notices.</p>
        </div>
    </div>

    <div class="detail-grid">
        <section class="card">
            <p class="eyebrow"><?php echo $announcementId > 0 ? "Edit notice" : "New notice"; ?></p>
            <h2><?php echo $announcementId > 0 ? "Update announcement" : "Create announcement"; ?></h2>
            <form method="POST">
                <?php csrfField(); ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?php echo $announcementId > 0 ? $announcementId : ""; ?>">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" maxlength="150" value="<?php echo e($title); ?>" required>
                </div>
                <div class="form-group">
                    <label for="tag">Tag (optional)</label>
                    <input type="text" id="tag" name="tag" maxlength="50" value="<?php echo e($tag); ?>">
                </div>
                <div class="form-group">
                    <label for="body">Announcement</label>
                    <textarea id="body" name="body" maxlength="20000" required><?php echo e($body); ?></textarea>
                </div>
                <div class="form-group check-row">
                    <input type="checkbox" id="is_published" name="is_published" value="1" <?php if ($isPublished) { echo "checked"; } ?>>
                    <div>
                        <label for="is_published">Publish this announcement</label>
                        <p class="hint">Published announcements appear on the public site.</p>
                    </div>
                </div>
                <button type="submit" class="btn btn-dark">Save announcement</button>
                <?php if ($announcementId > 0) { ?><a class="btn btn-outline" href="announcements.php">Cancel</a><?php } ?>
            </form>
        </section>

        <section>
            <h2>All announcements</h2>
            <?php if (count($announcements) == 0) { ?>
                <div class="card empty"><p class="muted">No announcements have been created.</p></div>
            <?php } else { ?>
                <div class="announcement-list">
                    <?php foreach ($announcements as $item) { ?>
                        <article class="card announcement-admin-card">
                            <div class="announcement-admin-top">
                                <span class="badge"><?php echo (int)$item["is_published"] == 1 ? "Published" : "Draft"; ?></span>
                                <time><?php echo niceDate($item["created_at"]); ?></time>
                            </div>
                            <h3><?php echo e($item["title"]); ?></h3>
                            <?php if (!empty($item["tag"])) { ?><p class="muted small"><?php echo e($item["tag"]); ?></p><?php } ?>
                            <p class="announcement-admin-body"><?php echo e($item["body"]); ?></p>
                            <p class="hint">By <?php echo e($item["author_name"]); ?></p>
                            <div class="btn-row">
                                <a class="btn btn-outline btn-small" href="announcements.php?id=<?php echo $item["id"]; ?>">Edit</a>
                                <form method="POST" data-confirm="Delete this announcement?">
                                    <?php csrfField(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $item["id"]; ?>">
                                    <button type="submit" class="btn btn-danger btn-small">Delete</button>
                                </form>
                            </div>
                        </article>
                    <?php } ?>
                </div>
            <?php } ?>
        </section>
    </div>
</div>

<?php include "../includes/footer.php"; ?>