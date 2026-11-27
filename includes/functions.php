<?php
// Small helper functions used on many pages.

// Make text safe to print on a page.
function e($text) {
    return htmlspecialchars($text, ENT_QUOTES, "UTF-8");
}

// Save a message to show after the next redirect.
function setFlash($type, $message) {
    $_SESSION["flash_type"] = $type;
    $_SESSION["flash_message"] = $message;
}

// Show the saved message once, then forget it.
function showFlash() {
    if (isset($_SESSION["flash_message"])) {
        $type = isset($_SESSION["flash_type"]) ? $_SESSION["flash_type"] : "info";
        $icon = $type == "success" ? "check" : ($type == "error" ? "alert" : "info");
        echo '<div class="alert alert-' . e($type) . '" role="status">';
        echo iconSvg($icon);
        echo '<span>' . e($_SESSION["flash_message"]) . '</span>';
        echo '<button type="button" class="alert-close" aria-label="Close message">' . iconSvg("close") . '</button>';
        echo '</div>';
        unset($_SESSION["flash_type"]);
        unset($_SESSION["flash_message"]);
    }
}

// Show a list of validation errors.
function showErrors($errors) {
    foreach ($errors as $error) {
        echo '<div class="alert alert-error" role="alert">' . iconSvg("alert") . '<span>' . e($error) . '</span></div>';
    }
}

// Friendly words for each status.
function statusLabel($status) {
    if ($status == "pending") {
        return "Received";
    } elseif ($status == "in_progress") {
        return "In progress";
    }
    return "Resolved";
}

// Longer, warmer sentence for the complainant.
function statusMessage($status) {
    if ($status == "pending") {
        return "We have received your complaint and it is waiting to be picked up.";
    } elseif ($status == "in_progress") {
        return "Someone is looking into this right now.";
    }
    return "This has been resolved. Thank you for your patience.";
}

// Print a coloured status badge.
function statusBadge($status) {
    return '<span class="badge badge-' . $status . '">' . statusLabel($status) . '</span>';
}

// Turn a database date into something readable.
function niceDate($date) {
    return date("d M Y, H:i", strtotime($date));
}

// Create the reference number, for example CMP-0012.
function makeReference($id) {
    return "CMP-" . str_pad($id, 4, "0", STR_PAD_LEFT);
}

function csrfToken() {
    if (!isset($_SESSION["csrf_token"]) || !is_string($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf_token"];
}

function csrfField() {
    echo '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function hasValidCsrfToken() {
    if (!isset($_POST["csrf_token"]) || !is_string($_POST["csrf_token"])) {
        return false;
    }
    if (!isset($_SESSION["csrf_token"]) || !is_string($_SESSION["csrf_token"])) {
        return false;
    }
    return hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"]);
}

function complaintUploadDirectory() {
    return dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . "cms_private_uploads";
}

function saveComplaintAttachment($upload) {
    $result = ["error" => "", "original_name" => null, "stored_file" => null];

    if ($upload === null) {
        return $result;
    }
    if (!is_array($upload) || !isset($upload["error"]) || !is_int($upload["error"])) {
        $result["error"] = "The selected attachment is not valid.";
        return $result;
    }
    if ($upload["error"] == UPLOAD_ERR_NO_FILE) {
        return $result;
    }
    if ($upload["error"] != UPLOAD_ERR_OK) {
        $result["error"] = "The attachment could not be uploaded. Please try again.";
        return $result;
    }
    if (!isset($upload["name"], $upload["size"], $upload["tmp_name"])
        || !is_string($upload["name"])
        || !is_string($upload["tmp_name"])
        || !is_numeric($upload["size"])) {
        $result["error"] = "The selected attachment is not valid.";
        return $result;
    }
    if ((int)$upload["size"] < 1 || (int)$upload["size"] > 2097152) {
        $result["error"] = "Choose an attachment smaller than 2 MB.";
        return $result;
    }
    if (!isset($upload["tmp_name"]) || !is_uploaded_file($upload["tmp_name"])) {
        $result["error"] = "The selected attachment is not valid.";
        return $result;
    }

    $originalName = basename(str_replace("\\", "/", $upload["name"]));
    $originalName = preg_replace("/[\x00-\x1F\x7F]/", "", $originalName);
    if ($originalName == "" || strlen($originalName) > 255) {
        $result["error"] = "The attachment name is not valid or is too long.";
        return $result;
    }

    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedTypes = [
        "jpg" => ["mime" => "image/jpeg", "extension" => "jpg"],
        "jpeg" => ["mime" => "image/jpeg", "extension" => "jpg"],
        "png" => ["mime" => "image/png", "extension" => "png"],
        "pdf" => ["mime" => "application/pdf", "extension" => "pdf"]
    ];
    if (!isset($allowedTypes[$extension])) {
        $result["error"] = "Attachments must be a JPG, PNG or PDF file.";
        return $result;
    }

    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($fileInfo === false) {
        $result["error"] = "We could not check this attachment. Please try another file.";
        return $result;
    }
    $mimeType = finfo_file($fileInfo, $upload["tmp_name"]);
    finfo_close($fileInfo);
    if ($mimeType !== $allowedTypes[$extension]["mime"]) {
        $result["error"] = "The attachment contents do not match a JPG, PNG or PDF file.";
        return $result;
    }

    $directory = complaintUploadDirectory();
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        $result["error"] = "We could not save the attachment. Please try again.";
        return $result;
    }

    $storedFile = bin2hex(random_bytes(16)) . "." . $allowedTypes[$extension]["extension"];
    if (!move_uploaded_file($upload["tmp_name"], $directory . DIRECTORY_SEPARATOR . $storedFile)) {
        $result["error"] = "We could not save the attachment. Please try again.";
        return $result;
    }

    $result["original_name"] = $originalName;
    $result["stored_file"] = $storedFile;
    return $result;
}

function deleteComplaintAttachment($storedFile) {
    if (!is_string($storedFile) || !preg_match("/\A[a-f0-9]{32}\.(jpg|png|pdf)\z/D", $storedFile)) {
        return;
    }

    $path = complaintUploadDirectory() . DIRECTORY_SEPARATOR . $storedFile;
    if (is_file($path)) {
        unlink($path);
    }
}

function iconSvg($name) {
    $icons = [
        "voice" => '<path d="M5 5.5h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-7l-5 3v-3H5a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2Z"/><path d="M8 11v2m4-4v6m4-4v2"/>',
        "menu" => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        "home" => '<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-6v-7h-4v7H4a1 1 0 0 1-1-1V10Z"/>',
        "inbox" => '<path d="M4 4h16l2 11v4a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1v-4L4 4Z"/><path d="M2 14h6l2 3h4l2-3h6"/>',
        "plus" => '<path d="M12 5v14M5 12h14"/>',
        "logout" => '<path d="M10 17l5-5-5-5M15 12H3"/><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/>',
        "login" => '<path d="m14 17 5-5-5-5m5 5H7"/><path d="M12 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h6"/>',
        "mail" => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/>',
        "phone" => '<path d="M7 3H5a2 2 0 0 0-2 2c0 8.8 7.2 16 16 16a2 2 0 0 0 2-2v-2l-5-2-2 3a14 14 0 0 1-7-7l3-2-3-5Z"/>',
        "pin" => '<path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
        "check" => '<path d="m5 12 4 4L19 6"/>',
        "alert" => '<path d="M12 3 2.8 19h18.4L12 3Z"/><path d="M12 9v4m0 3h.01"/>',
        "info" => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5m0-8h.01"/>',
        "close" => '<path d="m6 6 12 12M18 6 6 18"/>',
        "message" => '<path d="M4 5h16v12H9l-5 4V5Z"/><path d="M8 9h8m-8 4h5"/>',
        "reference" => '<path d="M5 4h14v16H5z"/><path d="M8 8h8m-8 4h8m-8 4h5"/>',
        "progress" => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        "book" => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v17H6.5A2.5 2.5 0 0 0 4 22V5.5Z"/><path d="M4 6h13m-9 4h8m-8 4h8"/>',
        "science" => '<path d="M9 3h6m-5 0v7l-6 9a1 1 0 0 0 1 2h14a1 1 0 0 0 1-2l-6-9V3"/><path d="M7 16h10m-8-3h6"/>',
        "tools" => '<path d="m14 7 3-3 4 4-3 3m-4-4L5 16a2.1 2.1 0 0 0 3 3l9-9"/><path d="m4 4 5 5m-6-2 4-4"/>',
        "people" => '<circle cx="9" cy="8" r="3"/><path d="M3 20v-1a6 6 0 0 1 12 0v1H3Zm12-9a3 3 0 1 0 0-6m2 9a5 5 0 0 1 4 5"/>',
        "health" => '<path d="M12 21s-8-4.5-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 10c0 6.5-8 11-8 11Z"/><path d="M8 12h2l1-2 2 4 1-2h2"/>',
        "cap" => '<path d="m2 9 10-5 10 5-10 5L2 9Z"/><path d="M6 11v5c3.5 3 8.5 3 12 0v-5m4-2v7"/>',
        "star" => '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.3l-5.6 2.9 1.1-6.2L3 9.6l6.2-.9L12 3Z"/>'
    ];

    if (!isset($icons[$name])) {
        return "";
    }

    return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $icons[$name] . '</svg>';
}

function sampleAnnouncements() {
    return [
        [
            "title" => "The new learning commons is open",
            "tag" => "Campus life",
            "body" => "A brighter study space, group rooms and extended evening hours are now available in the North Quad.",
            "created_at" => "2026-10-28 09:00:00",
            "author_name" => "HVU Communications"
        ],
        [
            "title" => "Spring scholarship applications begin",
            "tag" => "Admissions",
            "body" => "Applications for the Horizon Scholars award are open through January 16. The student support office can help with the application.",
            "created_at" => "2026-10-16 10:30:00",
            "author_name" => "Student Services"
        ],
        [
            "title" => "Student research teams earn regional honors",
            "tag" => "Research",
            "body" => "Three interdisciplinary teams were recognized for community-led projects in water quality, public health and accessible design.",
            "created_at" => "2026-10-04 14:15:00",
            "author_name" => "Office of Academic Affairs"
        ]
    ];
}
