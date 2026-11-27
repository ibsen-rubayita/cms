<?php
// Put this at the top of any page that only the admin may open.
require_once __DIR__ . "/init.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit;
}
if ($_SESSION["role"] != "admin") {
    header("Location: " . BASE_URL . "/user/dashboard.php");
    exit;
}
