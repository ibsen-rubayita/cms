<?php
require "../includes/require_admin.php";

$errors = [];
$name = "";
$description = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST["action"];

    if ($action == "add") {
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
                $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (:name, :description)");
                $stmt->bindValue(":name", $name);
                $stmt->bindValue(":description", $description);
                $stmt->execute();

                setFlash("success", "The category \"" . $name . "\" has been added.");
                header("Location: categories.php");
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $errors[] = "A category with that name already exists.";
                } else {
                    $errors[] = "Something went wrong on our side. Please try again.";
                }
            }
        }
    } elseif ($action == "delete") {
        $id = (int)$_POST["id"];
        try {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :id");
            $stmt->bindValue(":id", $id, PDO::PARAM_INT);
            $stmt->execute();

            setFlash("success", "The category has been deleted.");
            header("Location: categories.php");
            exit;
        } catch (PDOException $e) {
            // The database refuses to delete a category that complaints still use
            if ($e->getCode() == 23000) {
                $errors[] = "This category cannot be deleted because complaints are still using it.";
            } else {
                $errors[] = "Something went wrong on our side. Please try again.";
            }
        }
    }
}

$categories = [];
try {
    $stmt = $pdo->prepare("SELECT cat.id, cat.name, cat.description, COUNT(c.id) AS total
                           FROM categories cat
                           LEFT JOIN complaints c ON c.category_id = cat.id
                           GROUP BY cat.id, cat.name, cat.description
                           ORDER BY cat.name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $errors[] = "We could not load the categories. Please try again.";
}

$pageTitle = "Categories";
include "../includes/header.php";
?>

<div class="container">
    <?php showFlash(); ?>
    <?php showErrors($errors); ?>

    <div class="page-top">
        <div>
            <h1>Categories</h1>
            <p class="muted">The topics people choose from when they write a complaint.</p>
        </div>
    </div>

    <div class="detail-grid">
        <div class="table-wrap">
            <table>
                <tr><th>Name</th><th>Complaints</th><th></th></tr>
                <?php foreach ($categories as $cat) { ?>
                    <tr>
                        <td data-label="Category">
                            <strong><?php echo e($cat["name"]); ?></strong><br>
                            <span class="muted small"><?php echo e($cat["description"]); ?></span>
                        </td>
                        <td data-label="Complaints"><?php echo $cat["total"]; ?></td>
                        <td data-label="Actions">
                            <a class="btn btn-outline btn-small" href="category_edit.php?id=<?php echo $cat["id"]; ?>">Edit</a>
                            <form method="POST" class="inline-form" data-confirm="Delete this category?">
                                <?php csrfField(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $cat["id"]; ?>">
                                <button type="submit" class="btn btn-danger btn-small">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        </div>

        <div class="card">
            <h3>Add a category</h3>
            <form method="POST">
                <?php csrfField(); ?>
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" maxlength="100" value="<?php echo e($name); ?>">
                </div>
                <div class="form-group">
                    <label for="description">Short description</label>
                    <input type="text" id="description" name="description" maxlength="255" value="<?php echo e($description); ?>">
                </div>
                <button type="submit" class="btn btn-dark">Add category</button>
            </form>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
