<?php
// Database settings. When you deploy online, change these four lines
// to the details your hosting provider gives you.
$db_host = "localhost";
$db_name = "cms";
$db_user = "root";
$db_pass = "";

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Sorry, we could not connect to the database right now. Please try again later.");
}

// BASE_URL is the folder the project lives in (for example /cms on XAMPP, or empty on a live host).
// It is worked out automatically so links work in both places.
$doc_root = str_replace("\\", "/", realpath($_SERVER["DOCUMENT_ROOT"]));
$project_root = str_replace("\\", "/", realpath(__DIR__ . "/.."));
if (strpos($project_root, $doc_root) === 0) {
    define("BASE_URL", substr($project_root, strlen($doc_root)));
} else {
    define("BASE_URL", "/cms");
}
