<?php
require "../includes/require_login.php";

// Deleting is only allowed from a form (POST), never from a plain link.
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: my_complaints.php");
    exit;
}

$id = isset($_POST["id"]) ? (int)$_POST["id"] : 0;

try {
    $stmt = $pdo->prepare("SELECT attachment_file FROM complaints
                           WHERE id = :id AND user_id = :uid AND status = 'pending'");
    $stmt->bindValue(":id", $id, PDO::PARAM_INT);
    $stmt->bindValue(":uid", $_SESSION["user_id"], PDO::PARAM_INT);
    $stmt->execute();
    $complaint = $stmt->fetch();

    $stmt = $pdo->prepare("DELETE FROM complaints WHERE id = :id AND user_id = :uid AND status = 'pending'");
    $stmt->bindValue(":id", $id, PDO::PARAM_INT);
    $stmt->bindValue(":uid", $_SESSION["user_id"], PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        if ($complaint) {
            deleteComplaintAttachment($complaint["attachment_file"]);
        }
        setFlash("success", "Your complaint has been deleted.");
    } else {
        setFlash("error", "That complaint could not be deleted. It may already be in progress.");
    }
} catch (PDOException $e) {
    setFlash("error", "Something went wrong on our side. Please try again.");
}

header("Location: my_complaints.php");
exit;
