<?php
/**
 * ITCS333 – Assignments API (MySQL + PDO)
 * This file replaces the JSON version. 
 * Uses `assignments` and `comments_assignment` tables.
 */

/* ============================================================
   HEADERS + CORS
============================================================ */
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

/* ============================================================
   DATABASE CONNECTION
============================================================ */
require_once __DIR__ . "/Database.php";
$database = new Database();
$db = $database->getConnection();

/* ============================================================
   INPUT PARSING
============================================================ */
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = [];
$params = $_GET;

/* ============================================================
   ASSIGNMENTS FUNCTIONS
============================================================ */

function getAllAssignments($db) {
    $query = "SELECT * FROM assignments ORDER BY id ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $row["files"] = $row["files"] ? json_decode($row["files"], true) : [];
        $row["dueDate"] = $row["due_date"];
        unset($row["due_date"]);
    }

    sendResponse($rows);
}

function getAssignmentById($db, $id) {
    if (!$id) sendResponse(["error" => "Assignment ID required"], 400);

    $stmt = $db->prepare("SELECT * FROM assignments WHERE id = :id");
    $stmt->bindValue(":id", $id);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) sendResponse(["error" => "Assignment not found"], 404);

    $row["files"] = $row["files"] ? json_decode($row["files"], true) : [];
    $row["dueDate"] = $row["due_date"];
    unset($row["due_date"]);

    sendResponse($row);
}

function createAssignment($db, $data) {
    if (empty($data["title"]) || empty($data["description"]) || empty($data["dueDate"])) {
        sendResponse(["error" => "title, description, dueDate required"], 400);
    }

    $files = isset($data["files"]) && is_array($data["files"])
        ? json_encode($data["files"])
        : json_encode([]);

    $sql = "INSERT INTO assignments (title, description, due_date, files, created_at, updated_at)
            VALUES (:title, :description, :due_date, :files, NOW(), NOW())";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(":title", $data["title"]);
    $stmt->bindValue(":description", $data["description"]);
    $stmt->bindValue(":due_date", $data["dueDate"]);
    $stmt->bindValue(":files", $files);

    $stmt->execute();

    sendResponse([
        "id" => $db->lastInsertId(),
        "title" => $data["title"],
        "description" => $data["description"],
        "dueDate" => $data["dueDate"],
        "files" => json_decode($files)
    ], 201);
}

function updateAssignment($db, $data) {
    if (empty($data["id"])) sendResponse(["error" => "ID required"], 400);

    $id = $data["id"];
    $fields = [];
    $params = [":id" => $id];

    if (isset($data["title"]))        { $fields[] = "title = :title";               $params[":title"] = $data["title"]; }
    if (isset($data["description"]))  { $fields[] = "description = :description";   $params[":description"] = $data["description"]; }
    if (isset($data["dueDate"]))      { $fields[] = "due_date = :due_date";         $params[":due_date"] = $data["dueDate"]; }
    if (isset($data["files"]))        { $fields[] = "files = :files";               $params[":files"] = json_encode($data["files"]); }

    if (empty($fields)) sendResponse(["error" => "No fields to update"], 400);

    $sql = "UPDATE assignments SET " . implode(", ", $fields) . ", updated_at = NOW() WHERE id = :id";

    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }

    $stmt->execute();
    sendResponse(["success" => true]);
}

function deleteAssignment($db, $id) {
    if (!$id) sendResponse(["error" => "ID required"], 400);

    // delete dependent comments first
    $stmt = $db->prepare("DELETE FROM comments_assignment WHERE assignment_id = :id");
    $stmt->bindValue(":id", $id);
    $stmt->execute();

    // delete assignment
    $stmt = $db->prepare("DELETE FROM assignments WHERE id = :id");
    $stmt->bindValue(":id", $id);
    $stmt->execute();

    sendResponse(["success" => true]);
}

/* ============================================================
   COMMENTS FUNCTIONS (comments_assignment table)
============================================================ */

function getCommentsByAssignment($db, $assignmentId) {
    if (!$assignmentId) sendResponse(["error" => "assignment_id required"], 400);

    $stmt = $db->prepare("SELECT * FROM comments_assignment WHERE assignment_id = :aid ORDER BY created_at ASC");
    $stmt->bindValue(":aid", $assignmentId);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $comments = [];
    foreach ($rows as $c) {
        $comments[] = [
            "id" => (int)$c["id"],
            "assignmentId" => (int)$c["assignment_id"],
            "author" => $c["author"],
            "text" => $c["text"],
            "createdAt" => $c["created_at"]
        ];
    }

    sendResponse($comments);
}

function createComment($db, $data) {
    if (empty($data["assignmentId"]) && empty($data["assignment_id"])) {
        sendResponse(["error" => "assignmentId required"], 400);
    }

    $assignmentId = $data["assignmentId"] ?? $data["assignment_id"];
    $author = $data["author"] ?? "Anonymous";
    $text = trim($data["text"] ?? "");

    if ($text === "") sendResponse(["error" => "Comment text required"], 400);

    $sql = "INSERT INTO comments_assignment (assignment_id, author, text, created_at)
            VALUES (:aid, :author, :text, NOW())";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(":aid", $assignmentId);
    $stmt->bindValue(":author", $author);
    $stmt->bindValue(":text", $text);
    $stmt->execute();

    sendResponse([
        "id" => $db->lastInsertId(),
        "assignmentId" => $assignmentId,
        "author" => $author,
        "text" => $text,
        "createdAt" => date("Y-m-d H:i:s")
    ], 201);
}

function deleteComment($db, $id) {
    if (!$id) sendResponse(["error" => "ID required"], 400);

    $stmt = $db->prepare("DELETE FROM comments_assignment WHERE id = :id");
    $stmt->bindValue(":id", $id);
    $stmt->execute();

    sendResponse(["success" => true]);
    
    function updateComment($db, $data) {
        if (empty($data['id'])) {
            sendResponse(['error' => 'Comment ID required'], 400);
        }

        $id = $data['id'];
        $author = isset($data['author']) ? $data['author'] : null;
        $text = isset($data['text']) ? trim($data['text']) : '';

        if ($text === '') {
            sendResponse(['error' => 'Comment text required'], 400);
        }

        $fields = ["text = :text"];
        $params = [
            ':id'   => $id,
            ':text' => $text
        ];

        if ($author !== null && $author !== '') {
            $fields[] = "author = :author";
            $params[':author'] = $author;
        }

        $sql = "UPDATE comments_assignment SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $db->prepare($sql);

        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }

        $stmt->execute();

        sendResponse(['success' => true]);
    }

}

/* ============================================================
   ROUTER
============================================================ */

$resource = $params["resource"] ?? null;

try {
    if ($method === "GET") {

        if ($resource === "assignments") {
            if (isset($params["id"])) {
                getAssignmentById($db, $params["id"]);
            } else {
                getAllAssignments($db);
            }

        } elseif ($resource === "comments") {
            getCommentsByAssignment($db, $params["assignment_id"] ?? null);

        } else {
            sendResponse(["error" => "Invalid resource"], 400);
        }

    } elseif ($method === "POST") {

        if ($resource === "assignments") {
            createAssignment($db, $input);
        } elseif ($resource === "comments") {
            createComment($db, $input);
        } else {
            sendResponse(["error" => "Invalid resource"], 400);
        }

    } elseif ($method === "PUT") {

        if ($resource === "assignments") {
            updateAssignment($db, $input);
        } elseif ($resource === "comments") {
            updateComment($db, $input);
        } else {
            sendResponse(["error" => "PUT not allowed here"], 405);
        }

    } elseif ($method === "DELETE") {

        if ($resource === "assignments") {
            deleteAssignment($db, $params["id"] ?? null);
        } elseif ($resource === "comments") {
            deleteComment($db, $params["id"] ?? null);
        } else {
            sendResponse(["error" => "Invalid resource"], 400);
        }

    } else {
        sendResponse(["error" => "Method not allowed"], 405);
    }

} catch (Exception $e) {
    sendResponse(["error" => $e->getMessage()], 500);
}

/* ============================================================
   HELPER
============================================================ */
function sendResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}
?>
