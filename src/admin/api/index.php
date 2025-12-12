<?php
/**
 * Student Management API
 * 
 * This is a RESTful API that handles all CRUD operations for student management.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structure (for reference):
 * Table: users
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - student_id (VARCHAR(50), UNIQUE) - The student's university ID
 *   - name (VARCHAR(100))
 *   - email (VARCHAR(100), UNIQUE)
 *   - password (VARCHAR(255)) - Hashed password
 *   - is_admin (TINYINT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve student(s)
 *   - POST: Create a new student OR change password
 *   - PUT: Update an existing student
 *   - DELETE: Delete a student
 * 
 * Response Format: JSON
 */

// TODO: Set headers for JSON response and CORS
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// TODO: Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// TODO: Include the database connection class
// Using direct PDO connection instead
$host = 'localhost';
$db   = 'course';
$user = 'admin';
$pass = 'password123';
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

// TODO: Get the PDO database connection
try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    sendResponse(['error' => 'Database connection failed: ' . $e->getMessage()], 500);
}

// TODO: Get the HTTP request method
$method = $_SERVER['REQUEST_METHOD'];

// TODO: Get the request body for POST and PUT requests
$requestBody = json_decode(file_get_contents('php://input'), true);

// TODO: Parse query parameters for filtering and searching
$queryParams = $_GET;

/**
 * Function: Get all students or search for specific students
 * Method: GET
 */
function getStudents($db) {
    global $queryParams;

    $search = isset($queryParams['search']) ? strtolower(trim($queryParams['search'])) : '';
    $sort = isset($queryParams['sort']) ? $queryParams['sort'] : '';
    $order = isset($queryParams['order']) ? strtolower($queryParams['order']) : 'asc';

    $allowedSortFields = ['name', 'email', 'id'];
    $allowedOrder = ['asc', 'desc'];

    $sql = "SELECT id, name, email FROM users WHERE is_admin = 0";

    if ($search !== '') {
        $sql .= " AND (LOWER(name) LIKE :search OR LOWER(email) LIKE :search)";
    }

    if (in_array($sort, $allowedSortFields)) {
        $sql .= " ORDER BY $sort " . (in_array($order, $allowedOrder) ? $order : 'ASC');
    }

    $stmt = $db->prepare($sql);

    if ($search !== '') {
        $stmt->bindValue(':search', "%$search%");
    }

    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(['success' => true, 'data' => $students]);
}

/**
 * Function: Get a single student by student_id
 * Method: GET
 */
function getStudentById($db, $studentId) {
    $stmt = $db->prepare("SELECT id, name, email FROM users WHERE id = :id AND is_admin = 0");
    $stmt->bindValue(':id', $studentId);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        sendResponse(['success' => true, 'data' => $student]);
    } else {
        sendResponse(['error' => 'Student not found'], 404);
    }
}

/**
 * Function: Create a new student
 * Method: POST
 */
function createStudent($db, $data) {
    if (!isset($data['name'], $data['email'], $data['password'])) {
        sendResponse(['error' => 'Missing required fields'], 400);
    }

    $name = sanitizeInput($data['name']);
    $email = sanitizeInput($data['email']);
    $password = $data['password'];

    if (!validateEmail($email)) {
        sendResponse(['error' => 'Invalid email format'], 400);
    }

    $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->bindValue(':email', $email);
    $stmt->execute();
    if ($stmt->fetch()) {
        sendResponse(['error' => 'Email already exists'], 409);
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $db->prepare("INSERT INTO users (name, email, password, is_admin) VALUES (:name, :email, :password, 0)");
    $stmt->bindValue(':name', $name);
    $stmt->bindValue(':email', $email);
    $stmt->bindValue(':password', $passwordHash);

    if ($stmt->execute()) {
        sendResponse(['success' => true, 'message' => 'Student created successfully'], 201);
    } else {
        sendResponse(['error' => 'Failed to create student'], 500);
    }
}

/**
 * Function: Update an existing student
 * Method: PUT
 */
function updateStudent($db, $data) {
    if (!isset($data['id'])) {
        sendResponse(['error' => 'Student ID is required'], 400);
    }

    $id = $data['id'];

    $stmt = $db->prepare("SELECT id FROM users WHERE id = :id AND is_admin = 0");
    $stmt->bindValue(':id', $id);
    $stmt->execute();
    if (!$stmt->fetch()) {
        sendResponse(['error' => 'Student not found'], 404);
    }

    $fields = [];
    if (isset($data['name'])) {
        $fields['name'] = sanitizeInput($data['name']);
    }
    if (isset($data['email'])) {
        if (!validateEmail($data['email'])) {
            sendResponse(['error' => 'Invalid email format'], 400);
        }
        $fields['email'] = sanitizeInput($data['email']);
    }

    if (isset($fields['email'])) {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
        $stmt->bindValue(':email', $fields['email']);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        if ($stmt->fetch()) {
            sendResponse(['error' => 'Email already exists'], 409);
        }
    }

    $set = [];
    foreach ($fields as $key => $val) {
        $set[] = "$key = :$key";
    }
    $sql = "UPDATE users SET " . implode(", ", $set) . " WHERE id = :id";
    $stmt = $db->prepare($sql);
    foreach ($fields as $key => $val) {
        $stmt->bindValue(":$key", $val);
    }
    $stmt->bindValue(':id', $id);

    if ($stmt->execute()) {
        sendResponse(['success' => true, 'message' => 'Student updated successfully']);
    } else {
        sendResponse(['error' => 'Failed to update student'], 500);
    }
}

/**
 * Function: Delete a student
 * Method: DELETE
 */
function deleteStudent($db, $studentId) {
    $stmt = $db->prepare("SELECT id FROM users WHERE id = :id AND is_admin = 0");
    $stmt->bindValue(':id', $studentId);
    $stmt->execute();
    if (!$stmt->fetch()) {
        sendResponse(['error' => 'Student not found'], 404);
    }

    $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
    $stmt->bindValue(':id', $studentId);
    if ($stmt->execute()) {
        sendResponse(['success' => true, 'message' => 'Student deleted successfully']);
    } else {
        sendResponse(['error' => 'Failed to delete student'], 500);
    }
}

/**
 * Function: Change password
 * Method: POST with action=change_password
 */
function changePassword($db, $data) {
    if (!isset($data['id'], $data['current_password'], $data['new_password'])) {
        sendResponse(['error' => 'Missing required fields'], 400);
    }

    if (strlen($data['new_password']) < 8) {
        sendResponse(['error' => 'Password must be at least 8 characters'], 400);
    }

    $stmt = $db->prepare("SELECT password FROM users WHERE id = :id");
    $stmt->bindValue(':id', $data['id']);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($data['current_password'], $user['password'])) {
        sendResponse(['error' => 'Current password incorrect'], 401);
    }

    $newHash = password_hash($data['new_password'], PASSWORD_DEFAULT);

    $stmt = $db->prepare("UPDATE users SET password = :password WHERE id = :id");
    $stmt->bindValue(':password', $newHash);
    $stmt->bindValue(':id', $data['id']);
    if ($stmt->execute()) {
        sendResponse(['success' => true, 'message' => 'Password updated successfully']);
    } else {
        sendResponse(['error' => 'Failed to update password'], 500);
    }
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    if ($method === 'GET') {
        if (isset($queryParams['id'])) {
            getStudentById($pdo, $queryParams['id']);
        } else {
            getStudents($pdo);
        }
    } elseif ($method === 'POST') {
        if (isset($queryParams['action']) && $queryParams['action'] === 'change_password') {
            changePassword($pdo, $requestBody);
        } else {
            createStudent($pdo, $requestBody);
        }
    } elseif ($method === 'PUT') {
        updateStudent($pdo, $requestBody);
    } elseif ($method === 'DELETE') {
        $studentId = $queryParams['id'] ?? $requestBody['id'] ?? null;
        if (!$studentId) {
            sendResponse(['error' => 'Student ID is required'], 400);
        }
        deleteStudent($pdo, $studentId);
    } else {
        sendResponse(['error' => 'Method not allowed'], 405);
    }
} catch (PDOException $e) {
    sendResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    sendResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
}
?>

