<?php
require "../includes/init.php";

if (isset($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit;
}

$errors = [];
$email = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = isset($_POST["email"]) && is_string($_POST["email"]) ? strtolower(trim($_POST["email"])) : "";
    $password = isset($_POST["password"]) && is_string($_POST["password"]) ? $_POST["password"] : "";

    if ($email == "" || $password == "") {
        $errors[] = "Please enter your email and password.";
    } elseif (strlen($email) > 150) {
        $errors[] = "Please enter a valid email address.";
    } else {
        try {
            $ipAddress = isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : "";
            $cleanupStmt = $pdo->prepare("DELETE FROM login_attempts
                                          WHERE email = :email
                                          AND attempted_at < DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
            $cleanupStmt->bindValue(":email", $email);
            $cleanupStmt->execute();

            $attemptStmt = $pdo->prepare("SELECT COUNT(*) AS attempt_count,
                                                 COALESCE(TIMESTAMPDIFF(SECOND, MIN(recent.attempted_at), MAX(recent.attempted_at)), 0) AS attempt_span,
                                                 COALESCE(TIMESTAMPDIFF(SECOND, MAX(recent.attempted_at), NOW()), 0) AS seconds_since_last
                                          FROM (
                                              SELECT attempted_at
                                              FROM login_attempts
                                              WHERE email = :email
                                              ORDER BY attempted_at DESC
                                              LIMIT 5
                                          ) AS recent");
            $attemptStmt->bindValue(":email", $email);
            $attemptStmt->execute();
            $attemptState = $attemptStmt->fetch();
            $attemptCount = (int)$attemptState["attempt_count"];
            $attemptSpan = (int)$attemptState["attempt_span"];
            $secondsSinceLast = (int)$attemptState["seconds_since_last"];

            if ($attemptCount >= 5 && $secondsSinceLast < 900) {
                $errors[] = "Too many failed sign-in attempts. Please try again in 15 minutes.";
            } else {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
                $stmt->bindValue(":email", $email);
                $stmt->execute();
                $user = $stmt->fetch();

                if ($user && (!isset($user["is_active"]) || (int)$user["is_active"] == 1) && password_verify($password, $user["password"])) {
                    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
                    $stmt->bindValue(":id", $user["id"], PDO::PARAM_INT);
                    $stmt->execute();

                    $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE email = :email");
                    $stmt->bindValue(":email", $email);
                    $stmt->execute();

                    session_regenerate_id(true);
                    unset($_SESSION["csrf_token"]);
                    csrfToken();
                    $_SESSION["user_id"] = $user["id"];
                    $_SESSION["user_name"] = $user["full_name"];
                    $_SESSION["role"] = $user["role"];
                    $_SESSION["last_activity"] = time();

                    if ($user["role"] == "admin") {
                        header("Location: ../admin/dashboard.php");
                    } else {
                        header("Location: ../user/dashboard.php");
                    }
                    exit;
                } else {
                    $stmt = $pdo->prepare("INSERT INTO login_attempts (email, ip_address) VALUES (:email, :ip)");
                    $stmt->bindValue(":email", $email);
                    $stmt->bindValue(":ip", $ipAddress);
                    $stmt->execute();

                    if ($attemptCount >= 4 && $attemptSpan + $secondsSinceLast < 900) {
                        $errors[] = "Too many failed sign-in attempts. Please try again in 15 minutes.";
                    } else {
                        $errors[] = "The email or password is not correct. Please try again.";
                    }
                }
            }
        } catch (PDOException $e) {
            $errors[] = "Something went wrong on our side. Please try again.";
        }
    }
}

$pageTitle = "Log in";
include "../includes/header.php";
?>

<div class="container">
    <div class="auth-split">
        <div class="auth-side">
            <?php echo iconSvg("voice"); ?>
            <p class="eyebrow"><?php echo e($site["university_short_name"]); ?> VOICE DESK</p>
            <h2>Welcome back</h2>
            <p>Log in to share a concern or to see how one you already sent is getting on.</p>
        </div>
        <div class="auth-form">
            <h2>Log in</h2>
            <p class="muted" style="margin-bottom: 20px;">We are glad to see you.</p>

            <?php showFlash(); ?>
            <?php showErrors($errors); ?>

            <form method="POST">
                <?php csrfField(); ?>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="text" id="email" name="email" value="<?php echo e($email); ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="pw-wrap">
                        <input type="password" id="password" name="password">
                        <button type="button" data-toggle-password="password">Show</button>
                    </div>
                </div>
                <button type="submit" class="btn btn-dark"><?php echo iconSvg("login"); ?>Log in</button>
                <a href="register.php" class="btn btn-outline">Create an account</a>
            </form>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
