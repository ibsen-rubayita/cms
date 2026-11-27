<?php
require "../includes/require_login.php";

$errors = [];
$profile = false;

try {
    $stmt = $pdo->prepare("SELECT full_name, email, phone, bio, role, created_at, last_login
                           FROM users WHERE id = :id");
    $stmt->bindValue(":id", $_SESSION["user_id"], PDO::PARAM_INT);
    $stmt->execute();
    $profile = $stmt->fetch();
} catch (PDOException $e) {
    $errors[] = "We could not load your profile. Please try again.";
}

$fullName = $profile ? $profile["full_name"] : "";
$email = $profile ? $profile["email"] : "";
$phone = $profile && $profile["phone"] !== null ? $profile["phone"] : "";
$bio = $profile && $profile["bio"] !== null ? $profile["bio"] : "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && $profile) {
    $action = isset($_POST["action"]) && is_string($_POST["action"]) ? $_POST["action"] : "";

    if ($action == "details") {
        $fullName = isset($_POST["full_name"]) && is_string($_POST["full_name"]) ? trim($_POST["full_name"]) : "";
        $email = isset($_POST["email"]) && is_string($_POST["email"]) ? strtolower(trim($_POST["email"])) : "";
        $phone = isset($_POST["phone"]) && is_string($_POST["phone"]) ? trim($_POST["phone"]) : "";
        $bio = isset($_POST["bio"]) && is_string($_POST["bio"]) ? trim($_POST["bio"]) : "";

        if ($fullName == "") {
            $errors[] = "Please enter your name.";
        } elseif (strlen($fullName) > 100) {
            $errors[] = "Your name is too long (100 characters at most).";
        }
        if ($email == "") {
            $errors[] = "Please enter your email address.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
            $errors[] = "Please enter a valid email address (150 characters at most).";
        }
        if (strlen($phone) > 30) {
            $errors[] = "Your phone number is too long (30 characters at most).";
        }
        if (strlen($bio) > 300) {
            $errors[] = "Your bio is too long (300 characters at most).";
        }

        if (count($errors) == 0) {
            try {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id <> :id");
                $stmt->bindValue(":email", $email);
                $stmt->bindValue(":id", $_SESSION["user_id"], PDO::PARAM_INT);
                $stmt->execute();

                if ($stmt->fetch()) {
                    $errors[] = "That email address is already in use.";
                } else {
                    $stmt = $pdo->prepare("UPDATE users
                                           SET full_name = :full_name, email = :email, phone = :phone, bio = :bio
                                           WHERE id = :id");
                    $stmt->bindValue(":full_name", $fullName);
                    $stmt->bindValue(":email", $email);
                    $stmt->bindValue(":phone", $phone == "" ? null : $phone, $phone == "" ? PDO::PARAM_NULL : PDO::PARAM_STR);
                    $stmt->bindValue(":bio", $bio == "" ? null : $bio, $bio == "" ? PDO::PARAM_NULL : PDO::PARAM_STR);
                    $stmt->bindValue(":id", $_SESSION["user_id"], PDO::PARAM_INT);
                    $stmt->execute();

                    $_SESSION["user_name"] = $fullName;
                    setFlash("success", "Your profile has been updated.");
                    header("Location: profile.php");
                    exit;
                }
            } catch (PDOException $e) {
                $errors[] = "We could not save your profile. Please try again.";
            }
        }
    } elseif ($action == "password") {
        $currentPassword = isset($_POST["current_password"]) && is_string($_POST["current_password"]) ? $_POST["current_password"] : "";
        $newPassword = isset($_POST["new_password"]) && is_string($_POST["new_password"]) ? $_POST["new_password"] : "";
        $confirmPassword = isset($_POST["confirm_password"]) && is_string($_POST["confirm_password"]) ? $_POST["confirm_password"] : "";

        if ($currentPassword == "") {
            $errors[] = "Please enter your current password.";
        }
        if (strlen($newPassword) < 8) {
            $errors[] = "Your new password should have at least 8 characters.";
        }
        if ($newPassword != $confirmPassword) {
            $errors[] = "The new passwords do not match.";
        }

        if (count($errors) == 0) {
            try {
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
                $stmt->bindValue(":id", $_SESSION["user_id"], PDO::PARAM_INT);
                $stmt->execute();
                $passwordHash = $stmt->fetchColumn();

                if (!$passwordHash || !password_verify($currentPassword, $passwordHash)) {
                    $errors[] = "Your current password is not correct.";
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
                    $stmt->bindValue(":password", password_hash($newPassword, PASSWORD_DEFAULT));
                    $stmt->bindValue(":id", $_SESSION["user_id"], PDO::PARAM_INT);
                    $stmt->execute();

                    setFlash("success", "Your password has been changed.");
                    header("Location: profile.php");
                    exit;
                }
            } catch (PDOException $e) {
                $errors[] = "We could not change your password. Please try again.";
            }
        }
    } else {
        $errors[] = "Please choose a valid profile action.";
    }
}

$initials = "?";
if ($profile) {
    $nameParts = preg_split("/\s+/", trim($fullName));
    $initials = strtoupper(substr($nameParts[0], 0, 1));
    if (isset($nameParts[1])) {
        $initials .= strtoupper(substr($nameParts[1], 0, 1));
    }
}

$pageTitle = "My profile";
include "../includes/header.php";
?>

<div class="container">
    <?php showFlash(); ?>
    <?php showErrors($errors); ?>

    <?php if ($profile) { ?>
        <section class="profile-banner">
            <div class="profile-avatar" aria-label="Avatar initials <?php echo e($initials); ?>"><?php echo e($initials); ?></div>
            <div class="profile-heading">
                <p class="eyebrow">Account profile</p>
                <h1><?php echo e($profile["full_name"]); ?></h1>
                <p><?php echo e($profile["email"]); ?></p>
            </div>
        </section>

        <div class="profile-summary">
            <div class="stat"><div class="label">Role</div><div class="profile-value"><?php echo e(ucfirst($profile["role"])); ?></div></div>
            <div class="stat"><div class="label">Member since</div><div class="profile-value"><?php echo e(niceDate($profile["created_at"])); ?></div></div>
            <div class="stat"><div class="label">Last login</div><div class="profile-value"><?php echo $profile["last_login"] ? e(niceDate($profile["last_login"])) : "Not recorded yet"; ?></div></div>
        </div>

        <div class="profile-forms">
            <section class="card">
                <p class="eyebrow">Your information</p>
                <h2>Personal details</h2>
                <form method="POST">
                    <?php csrfField(); ?>
                    <input type="hidden" name="action" value="details">
                    <div class="form-group">
                        <label for="full_name">Full name</label>
                        <input type="text" id="full_name" name="full_name" maxlength="100" value="<?php echo e($fullName); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" maxlength="150" value="<?php echo e($email); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone" maxlength="30" value="<?php echo e($phone); ?>">
                    </div>
                    <div class="form-group">
                        <label for="bio">Bio</label>
                        <textarea id="bio" name="bio" maxlength="300"><?php echo e($bio); ?></textarea>
                        <p class="hint">Up to 300 characters.</p>
                    </div>
                    <button type="submit" class="btn btn-dark"><?php echo iconSvg("check"); ?>Save details</button>
                </form>
            </section>

            <section class="card">
                <p class="eyebrow">Keep your account secure</p>
                <h2>Change password</h2>
                <p class="muted profile-password-note">Your password is never displayed.</p>
                <form method="POST" autocomplete="off">
                    <?php csrfField(); ?>
                    <input type="hidden" name="action" value="password">
                    <div class="form-group">
                        <label for="current_password">Current password</label>
                        <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New password</label>
                        <input type="password" id="new_password" name="new_password" autocomplete="new-password" required>
                        <p class="hint">At least 8 characters.</p>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm new password</label>
                        <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" required>
                    </div>
                    <button type="submit" class="btn btn-dark"><?php echo iconSvg("check"); ?>Change password</button>
                </form>
            </section>
        </div>
    <?php } ?>
</div>

<?php include "../includes/footer.php"; ?>