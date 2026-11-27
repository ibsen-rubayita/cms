<?php
require "../includes/require_admin.php";

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
$errors = [];
$category = false;

try {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = :id");
    $stmt->bindValue(":id", $id, PDO::PARAM_INT);
    $stmt->execute();
    $category = $stmt->fetch();
} catch (PDOException $e) {
    $errors[] = "We could not load this category. Please try again.";
}

if (!$category && count($errors) == 0) {
    setFlash("error", "We could not find that category.");
    header("Location: categories.php");
    exit;
}

if ($category) {
    $name = $category["name"];
    $description = $category["description"];
}

if ($category && $_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $description = trim($_POST["description"]);

    if ($name == "") {
        $errors[] = "Please enter a category name.";
    } elseif (strlen($name) > 100) {
        $errors[] = "The name is too long (100 characters at most).";
    }
    if (strlen($description) > 255) {
        $errors[] = "The description is too long (255 characters at most).";
    }

    if (count($errors) == 0) {
        try {
            $stmt = $pdo->prepare("UPDATE categories SET name = :name, description = :description WHERE id = :id");
            $stmt->bindValue(":name", $name);
            $stmt->bindValue(":description", $description);
            $stmt->bindValue(":id", $id, PDO::PARAM_INT);
            $stmt->execute();

            setFlash("success", "The category has been updated.");
            header("Location: categories.php");
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errors[] = "Another category already uses that name.";
            } else {
                $errors[] = "Something went wrong on our side. Please try again.";
            }
        }
    }
}

$pageTitle = "Edit category";
include "../includes/header.php";
?>

<div class="container" style="max-width: 640px;">
    <div class="page-top">
        <div>
            <p class="eyebrow">Categories</p>
            <h1>Edit category</h1>
            <p class="muted">Changes apply to all complaints already using this category.</p>
        </div>
    </div>

    <?php showErrors($errors); ?>

    <?php if ($category) { ?>
    <div class="card">
        <form method="POST">
            <?php csrfField(); ?>
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" maxlength="100" value="<?php echo e($name); ?>">
            </div>
            <div class="form-group">
                <label for="description">Short description</label>
                <input type="text" id="description" name="description" maxlength="255" value="<?php echo e($description); ?>">
            </div>
            <button type="submit" class="btn btn-dark">Save changes</button>
            <a href="categories.php" class="btn btn-outline">Cancel</a>
        </form>
    </div>
    <?php } ?>
</div>

<?php include "../includes/footer.php"; ?>
