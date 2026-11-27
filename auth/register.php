<?php
require "../includes/init.php";

// Already logged in? Go home.
if (isset($_SESSION["user_id"])) {
    header("Location: ../index.php");
    exit;
}

$errors = [];
$name = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm = $_POST["confirm_password"];

    // Validation
    if ($name == "") {
        $errors[] = "Please tell us your name.";
    } elseif (strlen($name) > 100) {
        $errors[] = "Your name is too long (100 characters at most).";
    }

    if ($email == "") {
        $errors[] = "Please enter your email.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "That email does not look right. Please check it.";
    }

    if (strlen($password) < 6) {
        $errors[] = "Your password should have at least 6 characters.";
    }
    if ($password != $confirm) {
        $errors[] = "The two passwords do not match.";
    }

    if (count($errors) == 0) {
        try {
            // Is the email already taken?
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->bindValue(":email", $email);
            $stmt->execute();

            if ($stmt->fetch()) {
                $errors[] = "This email is already registered. Try logging in instead.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password) VALUES (:name, :email, :password)");
                $stmt->bindValue(":name", $name);
                $stmt->bindValue(":email", $email);
                $stmt->bindValue(":password", $hashed);
                $stmt->execute();

                setFlash("success", "Welcome, " . $name . "! Your account is ready. Please log in.");
                header("Location: login.php");
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = "Something went wrong on our side. Please try again.";
        }
    }
}

$pageTitle = "Create your account";
include "../includes/header.php";
?>

<div class="container">
    <div class="auth-split">
        <div class="auth-side">
            <?php echo iconSvg("voice"); ?>
            <p class="eyebrow"><?php echo e($site["university_short_name"]); ?> VOICE DESK</p>
            <h2>Let us get you started</h2>
            <p>Create an account so you can send a complaint, keep track of it and read our replies in one place.</p>
        </div>
        <div class="auth-form">
            <h2>Create your account</h2>
            <p class="muted" style="margin-bottom: 20px;">It only takes a minute.</p>

            <?php showErrors($errors); ?>

            <form method="POST">
                <?php csrfField(); ?>
                <div class="form-group">
                    <label for="full_name">Full name</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo e($name); ?>">
                </div>
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
                    <p class="hint">At least 6 characters.</p>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm password</label>
                    <input type="password" id="confirm_password" name="confirm_password">
                </div>
                <button type="submit" class="btn btn-dark"><?php echo iconSvg("plus"); ?>Register</button>
                <a href="login.php" class="btn btn-outline">I already have an account</a>
            </form>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
