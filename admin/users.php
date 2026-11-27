<?php
require "../includes/require_admin.php";

$users = [];
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $targetId = isset($_POST["user_id"]) && is_string($_POST["user_id"])
        ? filter_var($_POST["user_id"], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])
        : false;
    $action = isset($_POST["action"]) && is_string($_POST["action"]) ? $_POST["action"] : "";

    if ($targetId === false) {
        $errors[] = "Please choose a valid account.";
    } elseif ((int)$targetId == (int)$_SESSION["user_id"]) {
        $errors[] = "You cannot change your own account from this page.";
    } elseif (!in_array($action, ["pause", "activate", "promote", "demote"], true)) {
        $errors[] = "Please choose a valid account action.";
    } else {
        try {
            if ($action == "pause" || $action == "activate") {
                $active = $action == "activate" ? 1 : 0;
                $stmt = $pdo->prepare("UPDATE users SET is_active = :active WHERE id = :id AND id <> :admin_id");
                $stmt->bindValue(":active", $active, PDO::PARAM_INT);
                $stmt->bindValue(":id", $targetId, PDO::PARAM_INT);
                $stmt->bindValue(":admin_id", $_SESSION["user_id"], PDO::PARAM_INT);
                $stmt->execute();
                $message = $active ? "The account has been activated." : "The account has been paused.";
            } else {
                $role = $action == "promote" ? "admin" : "user";
                $stmt = $pdo->prepare("UPDATE users SET role = :role WHERE id = :id AND id <> :admin_id");
                $stmt->bindValue(":role", $role);
                $stmt->bindValue(":id", $targetId, PDO::PARAM_INT);
                $stmt->bindValue(":admin_id", $_SESSION["user_id"], PDO::PARAM_INT);
                $stmt->execute();
                $message = $role == "admin" ? "The account has been promoted to admin." : "The account has been changed to complainant.";
            }

            if ($stmt->rowCount() > 0) {
                setFlash("success", $message);
                header("Location: users.php");
                exit;
            }
            $errors[] = "That account could not be changed. It may no longer exist or already has that setting.";
        } catch (PDOException $e) {
            $errors[] = "We could not update that account. Please try again.";
        }
    }
}

try {
    $stmt = $pdo->prepare("SELECT u.id, u.full_name, u.email, u.role, u.is_active, u.created_at, COUNT(c.id) AS total
                           FROM users u
                           LEFT JOIN complaints c ON c.user_id = u.id
                           GROUP BY u.id, u.full_name, u.email, u.role, u.is_active, u.created_at
                           ORDER BY u.created_at DESC, u.id DESC");
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $errors[] = "We could not load the users. Please try again.";
}

$pageTitle = "Users";
include "../includes/header.php";
?>

<div class="container">
    <?php showFlash(); ?>
    <?php showErrors($errors); ?>

    <div class="page-top">
        <div>
            <h1>Users</h1>
            <p class="muted"><?php echo count($users); ?> registered accounts</p>
        </div>
    </div>

    <div class="table-wrap">
        <table>
                <tr><th>Name</th><th>Email</th><th>Role</th><th>Account</th><th>Complaints</th><th>Joined</th><th>Actions</th></tr>
            <?php foreach ($users as $u) { ?>
                <tr>
                    <td data-label="Name"><?php echo e($u["full_name"]); ?></td>
                    <td data-label="Email"><?php echo e($u["email"]); ?></td>
                    <td data-label="Role"><?php echo $u["role"] == "admin" ? "Admin" : "Complainant"; ?></td>
                        <td data-label="Account"><?php echo (int)$u["is_active"] == 1 ? "Active" : "Paused"; ?></td>
                    <td data-label="Complaints"><?php echo $u["total"]; ?></td>
                    <td data-label="Joined"><?php echo niceDate($u["created_at"]); ?></td>
                        <td data-label="Actions">
                            <?php if ((int)$u["id"] == (int)$_SESSION["user_id"]) { ?>
                                <span class="muted small">Current account</span>
                            <?php } else { ?>
                                <div class="user-actions">
                                    <form method="POST">
                                        <?php csrfField(); ?>
                                        <input type="hidden" name="user_id" value="<?php echo $u["id"]; ?>">
                                        <input type="hidden" name="action" value="<?php echo (int)$u["is_active"] == 1 ? "pause" : "activate"; ?>">
                                        <button type="submit" class="btn btn-outline btn-small"><?php echo (int)$u["is_active"] == 1 ? "Pause" : "Activate"; ?></button>
                                    </form>
                                    <form method="POST" data-confirm="Change this account's role?">
                                        <?php csrfField(); ?>
                                        <input type="hidden" name="user_id" value="<?php echo $u["id"]; ?>">
                                        <input type="hidden" name="action" value="<?php echo $u["role"] == "admin" ? "demote" : "promote"; ?>">
                                        <button type="submit" class="btn btn-dark btn-small"><?php echo $u["role"] == "admin" ? "Demote" : "Promote"; ?></button>
                                    </form>
                                </div>
                            <?php } ?>
                        </td>
                </tr>
            <?php } ?>
        </table>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
