<?php
/**
 * Weekly Course Breakdown API
 * 
 * RESTful API for CRUD operations on weekly course content and discussion comments.
 * 
 * Database Schema:
 * 
 * Table: weeks
 *   - id (INT UNSIGNED, PRIMARY KEY, AUTO_INCREMENT)
 *   - title (VARCHAR(200))
 *   - start_date (DATE)
 *   - description (TEXT)
 *   - links (TEXT) - JSON encoded array of links
 *   - created_at (TIMESTAMP)
 *   - updated_at (TIMESTAMP)
 * 
 * Table: comments_week
 *   - id (INT UNSIGNED, PRIMARY KEY, AUTO_INCREMENT)
 *   - week_id (INT UNSIGNED) - Foreign key to weeks.id
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods: GET, POST, PUT, DELETE
 * Response Format: JSON
 */

// ============================================================================
// SETUP AND CONFIGURATION
// ============================================================================

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


// ============================================================================
// TEMPORARY AUTH FOR TESTING - ADD THIS AT THE TOP
// ============================================================================
session_start();

// For testing comments - always set a test user
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'id' => 1,
        'name' => 'Test Student',
        'email' => 'test@student.com',
        'role' => 'student'
    ];
}

// Helper function to get current user name
function getCurrentUserName() {
    return $_SESSION['user']['name'] ?? 'Anonymous';
}




if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config/Database.php';
$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);
$resource = $_GET['resource'] ?? 'weeks';



// ============================================================================
// WEEKS CRUD OPERATIONS
// ============================================================================

function getAllWeeks($db) {
    $search = $_GET['search'] ?? '';
    $sort = $_GET['sort'] ?? 'start_date';
    $order = $_GET['order'] ?? 'asc';

    $sql = "SELECT id, title, start_date, description, links, created_at, updated_at FROM weeks";
    
    $params = [];
    if (!empty(trim($search))) {
        $sql .= " WHERE title LIKE ? OR description LIKE ?";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $allowedSortFields = ['title', 'start_date', 'created_at', 'updated_at'];
    if (!in_array($sort, $allowedSortFields)) {
        $sort = 'start_date';
    }

    $order = strtolower($order);
    if (!in_array($order, ['asc', 'desc'])) {
        $order = 'asc';
    }
    $order = strtoupper($order);
    
    $sql .= " ORDER BY $sort $order";
    $stmt = $db->prepare($sql);
    
    if (!empty(trim($search))) {
        $searchTerm = "%$search%";
        $stmt->bindParam(1, $searchTerm, PDO::PARAM_STR);
        $stmt->bindParam(2, $searchTerm, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $weeks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($weeks as &$week) {
        $week['links'] = json_decode($week['links'], true) ?? [];
    }
    
    sendResponse(['success' => true, 'data' => $weeks]);
}


function getWeekById($db, $id) {
    if (!isset($id)) {
        sendError('id is required', 400);
        return;
    }

    $sql = "SELECT id, title, start_date, description, links, created_at, updated_at 
            FROM weeks 
            WHERE id = ?";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(1, $id);
    $stmt->execute();
    $week = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($week) {
        $week['links'] = json_decode($week['links'], true) ?? [];
        sendResponse(['success' => true, 'data' => $week]);
    } else {
        sendResponse(['success' => false, 'message' => 'Week not found'], 404);
    }
}


function createWeek($db, $data) {
    if (empty($data['title']) || empty($data['start_date']) || empty($data['description'])) {
        sendResponse(['success' => false, 'message' => 'Missing required fields'], 400);
        return;
    }

    $title = sanitizeInput(trim($data['title']));
    $startDate = sanitizeInput(trim($data['start_date']));
    $description = sanitizeInput(trim($data['description']));

    $date = DateTime::createFromFormat('Y-m-d', $startDate);
    if (!$date || $date->format('Y-m-d') !== $startDate) {
        sendResponse(['success' => false, 'message' => 'Invalid start_date format. Use YYYY-MM-DD'], 400);
        return;
    }

    $linksJson = isset($data['links']) && is_array($data['links']) 
        ? json_encode($data['links']) 
        : json_encode([]);

    $sql = "INSERT INTO weeks (title, start_date, description, links) VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $success = $stmt->execute([$title, $startDate, $description, $linksJson]);

    if ($success) {
        $newId = $db->lastInsertId();
        sendResponse([
            'success' => true,
            'message' => 'Week created successfully',
            'data' => [
                'id' => $newId,
                'title' => $title,
                'start_date' => $startDate,
                'description' => $description,
                'links' => json_decode($linksJson, true)
            ]
        ], 201);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to create week'], 500);
    }
}
function updateWeek($db, $data) {
    if (!isset($data['id'])) {
        sendResponse(['success' => false, 'message' => 'id is required'], 400);
        return;
    }

    $id = $data['id'];

    $checkSql = "SELECT * FROM weeks WHERE id = ?";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute([$id]);
    $existingWeek = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingWeek) {
        sendResponse(['success' => false, 'message' => 'Week not found'], 404);
        return;
    }

    $setClauses = [];
    $values = [];

    if (!empty($data['title'])) {
        $setClauses[] = "title = ?";
        $values[] = sanitizeInput(trim($data['title']));
    }

    if (!empty($data['start_date'])) {
        $startDate = sanitizeInput(trim($data['start_date']));
        $date = DateTime::createFromFormat('Y-m-d', $startDate);
        if (!$date || $date->format('Y-m-d') !== $startDate) {
            sendResponse(['success' => false, 'message' => 'Invalid start_date format. Use YYYY-MM-DD'], 400);
            return;
        }
        $setClauses[] = "start_date = ?";
        $values[] = $startDate;
    }

    if (!empty($data['description'])) {
        $setClauses[] = "description = ?";
        $values[] = sanitizeInput(trim($data['description']));
    }

    if (isset($data['links'])) {
        $setClauses[] = "links = ?";
        $values[] = is_array($data['links']) ? json_encode($data['links']) : json_encode([]);
    }

    if (empty($setClauses)) {
        sendResponse(['success' => false, 'message' => 'No fields provided for update'], 400);
        return;
    }

    $setClauses[] = "updated_at = CURRENT_TIMESTAMP";
    $sql = "UPDATE weeks SET " . implode(', ', $setClauses) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    $values[] = $id;
    $success = $stmt->execute($values);

    if ($success) {
        $stmt = $db->prepare("SELECT id, title, start_date, description, links, created_at, updated_at FROM weeks WHERE id = ?");
        $stmt->execute([$id]);
        $updatedWeek = $stmt->fetch(PDO::FETCH_ASSOC);
        $updatedWeek['links'] = json_decode($updatedWeek['links'], true) ?? [];
        sendResponse(['success' => true, 'message' => 'Week updated successfully', 'data' => $updatedWeek]);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to update week'], 500);
    }
}




function deleteWeek($db, $id) {
    if (empty($id)) {
        sendResponse(['success' => false, 'message' => 'id is required'], 400);
        return;
    }

    $checkStmt = $db->prepare("SELECT id FROM weeks WHERE id = ?");
    $checkStmt->execute([$id]);
    if ($checkStmt->rowCount() === 0) {
        sendResponse(['success' => false, 'message' => 'Week not found'], 404);
        return;
    }

    $deleteCommentsStmt = $db->prepare("DELETE FROM comments_week WHERE week_id = ?");
    $deleteCommentsStmt->execute([$id]);

    $deleteWeekStmt = $db->prepare("DELETE FROM weeks WHERE id = ?");
    $deleteWeekStmt->bindParam(1, $id);
    $success = $deleteWeekStmt->execute();

    if ($success) {
        sendResponse(['success' => true, 'message' => 'Week and comments deleted successfully']);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to delete week'], 500);
    }
}


// ============================================================================
// COMMENTS CRUD OPERATIONS
// ============================================================================

function getCommentsByWeek($db, $weekId) {
    if (!isset($weekId) || empty($weekId)) {
        sendResponse(['success' => false, 'message' => 'week_id is required'], 400);
        return;
    }

    $stmt = $db->prepare(
        "SELECT id, week_id, author, text, created_at 
         FROM comments_week 
         WHERE week_id = ? 
         ORDER BY created_at ASC"
    );

    $stmt->bindParam(1, $weekId);
    $stmt->execute();
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(['success' => true, 'data' => $comments]);
}


function createComment($db, $data) {
    // TODO: Validate required fields
    // Check if week_id, author, and text are provided
    // If any field is missing, return error response with 400 status
    if (empty($data['week_id']) || empty($data['text'])) {
        sendResponse(['success' => false, 'message' => 'week_id and text are required'], 400);
        return;
    }
    // TODO: Sanitize input data
    // Trim whitespace from all fields
    $weekId = sanitizeInput(trim($data['week_id']));
    $text = sanitizeInput(trim($data['text']));
    $author = getCurrentUserName();

    if (empty($text)) {
        sendResponse(['success' => false, 'message' => 'Comment text cannot be empty'], 400);
        return;
    }

    $checkStmt = $db->prepare("SELECT id FROM weeks WHERE id = ?");
    $checkStmt->execute([$weekId]);
    $week = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$week) {
        sendResponse(['success' => false, 'message' => 'Week not found'], 404);
        return;
    }

    $stmt = $db->prepare("INSERT INTO comments_week (week_id, author, text) VALUES (?, ?, ?)");
    $stmt->bindParam(1, $weekId);
    $stmt->bindParam(2, $author);
    $stmt->bindParam(3, $text);
    $success = $stmt->execute();

    if ($success) {
        $newCommentId = $db->lastInsertId();
        sendResponse([
            'success' => true,
            'message' => 'Comment created successfully',
            'data' => [
                'id' => $newCommentId,
                'week_id' => $weekId,
                'author' => $author,
                'text' => $text
            ]
        ], 201);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to create comment'], 500);
    }
}
    

function deleteComment($db, $commentId) {
    if (empty($commentId)) {
        sendResponse(['success' => false, 'message' => 'Comment ID is required'], 400);
        return;
    }

    $checkStmt = $db->prepare("SELECT id FROM comments_week WHERE id = ?");
    $checkStmt->execute([$commentId]);
    $comment = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$comment) {
        sendResponse(['success' => false, 'message' => 'Comment not found'], 404);
        return;
    }

    $deleteStmt = $db->prepare("DELETE FROM comments_week WHERE id = ?");
    $deleteStmt->bindParam(1, $commentId);
    $success = $deleteStmt->execute();

    if ($success) {
        sendResponse(['success' => true, 'message' => 'Comment deleted successfully'], 200);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to delete comment'], 500);
    }
}



// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    if ($resource === 'weeks') {
        if ($method === 'GET') {
            isset($_GET['id']) ? getWeekById($db, $_GET['id']) : getAllWeeks($db);
        } elseif ($method === 'POST') {
            createWeek($db, $data);
        } elseif ($method === 'PUT') {
            updateWeek($db, $data);
        } elseif ($method === 'DELETE') {
            deleteWeek($db, $_GET['id'] ?? $data['id']);
        } else {
            sendError("Method not allowed", 405);
        }
    } elseif ($resource === 'comments') {
        if ($method === 'GET') {
            getCommentsByWeek($db, $_GET['week_id']);
        } elseif ($method === 'POST') {
            createComment($db, $data);
        } elseif ($method === 'DELETE') {
            deleteComment($db, $_GET['id'] ?? $data['id']);
        } else {
            sendError("Method not allowed", 405);
        }
    } else {
        sendError("Invalid resource. Use 'weeks' or 'comments'", 400);
    }
    
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    sendError("Database error occurred", 500);
} catch (Exception $e) {
    error_log("Server Error: " . $e->getMessage());
    sendError("Server error occurred", 500);
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

function sendError($message, $statusCode = 400) {
    sendResponse(['success' => false, 'error' => $message], $statusCode);
}

function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = strip_tags($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function isValidSortField($field, $allowedFields) {
    return in_array($field, $allowedFields);
}

?>
