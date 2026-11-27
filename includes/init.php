<?php
// Loaded at the top of every page: session, database connection and helpers.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$sessionExpired = false;
$now = time();
if (isset($_SESSION["user_id"])) {
    $lastActivity = isset($_SESSION["last_activity"]) ? (int)$_SESSION["last_activity"] : $now;
    if ($now - $lastActivity > 1800) {
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION["flash_type"] = "info";
        $_SESSION["flash_message"] = "Your session ended after 30 minutes of inactivity. Please log in again.";
        $sessionExpired = true;
    } else {
        $_SESSION["last_activity"] = $now;
    }
}

require_once __DIR__ . "/../config/connection.php";
$site = require __DIR__ . "/../config/site.php";
require_once __DIR__ . "/functions.php";

$accountPaused = false;
if (isset($_SESSION["user_id"])) {
    try {
        $stmt = $pdo->prepare("SELECT is_active, role FROM users WHERE id = :id");
        $stmt->bindValue(":id", $_SESSION["user_id"], PDO::PARAM_INT);
        $stmt->execute();
        $account = $stmt->fetch();

        if (!$account || (int)$account["is_active"] != 1) {
            $_SESSION = [];
            session_regenerate_id(true);
            $_SESSION["flash_type"] = "error";
            $_SESSION["flash_message"] = "This account is paused. Please contact an administrator.";
            $accountPaused = true;
        } else {
            $_SESSION["role"] = $account["role"];
        }
    } catch (PDOException $e) {
        http_response_code(500);
        exit("We could not verify your account. Please try again.");
    }
}

if (($sessionExpired || $accountPaused) && $_SERVER["REQUEST_METHOD"] == "POST") {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit;
}

csrfToken();
if ($_SERVER["REQUEST_METHOD"] == "POST" && !hasValidCsrfToken()) {
    http_response_code(403);
    exit("This form could not be verified. Reload the page and try again.");
}
