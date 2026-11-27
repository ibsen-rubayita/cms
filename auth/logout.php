<?php
require "../includes/init.php";

if ($_SERVER["REQUEST_METHOD"] != "POST") {
	header("Location: " . BASE_URL . "/auth/login.php");
	exit;
}

$_SESSION = [];
session_regenerate_id(true);
header("Location: " . BASE_URL . "/auth/login.php");
exit;
