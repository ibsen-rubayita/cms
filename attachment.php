<?php
require "includes/require_login.php";

$id = isset($_GET["id"]) && is_string($_GET["id"])
    ? filter_var($_GET["id"], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])
    : false;
if ($id === false) {
    http_response_code(404);
    exit("Attachment not found.");
}

try {
    $stmt = $pdo->prepare("SELECT user_id, attachment_name, attachment_file
                           FROM complaints WHERE id = :id");
    $stmt->bindValue(":id", $id, PDO::PARAM_INT);
    $stmt->execute();
    $complaint = $stmt->fetch();
} catch (PDOException $e) {
    http_response_code(500);
    exit("We could not open this attachment. Please try again.");
}

if (!$complaint || empty($complaint["attachment_file"]) || empty($complaint["attachment_name"])) {
    http_response_code(404);
    exit("Attachment not found.");
}

if ($_SESSION["role"] != "admin" && (int)$complaint["user_id"] != (int)$_SESSION["user_id"]) {
    http_response_code(404);
    exit("Attachment not found.");
}

$storedFile = $complaint["attachment_file"];
if (!preg_match("/\A[a-f0-9]{32}\.(jpg|png|pdf)\z/D", $storedFile)) {
    http_response_code(404);
    exit("Attachment not found.");
}

$path = complaintUploadDirectory() . DIRECTORY_SEPARATOR . $storedFile;
if (!is_file($path) || !is_readable($path)) {
    http_response_code(404);
    exit("Attachment not found.");
}

$downloadName = basename(str_replace("\\", "/", $complaint["attachment_name"]));
$downloadName = str_replace(["\r", "\n", "\""], "", $downloadName);
header("Content-Type: application/octet-stream");
header("X-Content-Type-Options: nosniff");
header("Cache-Control: private, no-store");
header("Content-Length: " . filesize($path));
header("Content-Disposition: attachment; filename=\"" . addcslashes($downloadName, "\\") . "\"; filename*=UTF-8''" . rawurlencode($downloadName));
readfile($path);
exit;