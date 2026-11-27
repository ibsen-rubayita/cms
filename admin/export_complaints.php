<?php
require "../includes/require_admin.php";

$search = isset($_GET["search"]) && is_string($_GET["search"]) ? trim($_GET["search"]) : "";
$status = isset($_GET["status"]) && is_string($_GET["status"]) ? $_GET["status"] : "";
$category = isset($_GET["category"]) && is_string($_GET["category"]) ? $_GET["category"] : "";
$priority = isset($_GET["priority"]) && is_string($_GET["priority"]) ? $_GET["priority"] : "";

if (strlen($search) > 100
    || ($status != "" && !in_array($status, ["pending", "in_progress", "resolved"], true))
    || ($category != "" && filter_var($category, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]) === false)
    || ($priority != "" && !in_array($priority, ["low", "medium", "high", "urgent"], true))) {
    http_response_code(400);
    exit("Please check the complaint export filters.");
}

$fromSql = "FROM complaints c
            JOIN users u ON u.id = c.user_id
            JOIN categories cat ON cat.id = c.category_id
            WHERE 1 = 1";
$parameters = [];

if ($search != "") {
    $fromSql .= " AND (c.title LIKE :search_title OR c.reference_no LIKE :search_reference)";
    $parameters[":search_title"] = ["%" . $search . "%", PDO::PARAM_STR];
    $parameters[":search_reference"] = ["%" . $search . "%", PDO::PARAM_STR];
}
if ($status != "") {
    $fromSql .= " AND c.status = :status";
    $parameters[":status"] = [$status, PDO::PARAM_STR];
}
if ($category != "") {
    $fromSql .= " AND c.category_id = :category";
    $parameters[":category"] = [(int)$category, PDO::PARAM_INT];
}
if ($priority != "") {
    $fromSql .= " AND c.priority = :priority";
    $parameters[":priority"] = [$priority, PDO::PARAM_STR];
}

try {
    $stmt = $pdo->prepare("SELECT c.reference_no, c.title, c.description,
                                  CASE WHEN c.is_anonymous = 1 THEN 'Anonymous' ELSE u.full_name END AS submitter,
                                  cat.name AS category, c.priority, c.status, c.created_at, c.resolved_at,
                                  c.rating, c.feedback_comment, c.attachment_name
                           " . $fromSql . "
                           ORDER BY c.created_at DESC, c.id DESC");
    foreach ($parameters as $name => $parameter) {
        $stmt->bindValue($name, $parameter[0], $parameter[1]);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_NUM);
} catch (PDOException $e) {
    http_response_code(500);
    exit("We could not export the complaints. Please try again.");
}

header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=hvu-complaints-" . date("Y-m-d") . ".csv");
header("X-Content-Type-Options: nosniff");

$output = fopen("php://output", "w");
fputcsv($output, ["Reference", "Title", "Description", "Submitter", "Category", "Priority", "Status", "Created", "Resolved", "Rating", "Feedback", "Attachment"]);

foreach ($rows as $row) {
    foreach ($row as $index => $value) {
        $value = $value === null ? "" : (string)$value;
        if (preg_match("/\A[\t\r ]*[=+@-]/", $value)) {
            $value = "'" . $value;
        }
        $row[$index] = $value;
    }
    fputcsv($output, $row);
}

fclose($output);
exit;