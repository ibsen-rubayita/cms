<?php
// Put this at the top of any page that needs a logged-in person.
require_once __DIR__ . "/init.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit;
}
