<?php
/**
 * Student Management API
 * 
 * This is a RESTful API that handles all CRUD operations for student management.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structure (for reference):
 * Table: students
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - student_id (VARCHAR(50), UNIQUE) - The student's university ID
 *   - name (VARCHAR(100))
 *   - email (VARCHAR(100), UNIQUE)
 *   - password (VARCHAR(255)) - Hashed password
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
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// TODO: Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// TODO: Include the database connection class
// For simplicity, we can create PDO here directly
$dsn = 'mysql:host=127.0.0.1;dbname=course;charset=utf8mb4';
$user = 'admin';
$pass = 'password123';

try {
    // TODO: Get the PDO database connection
    $db = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    sendResponse(['error' => 'Database connection failed'], 500);
}

// TODO: Get the HTTP request method
$method = $_SERVER['REQUEST_METHOD'];

// TODO: Get the request body for POST and PUT requests
$input = json_decode(file_get_contents('php://input'), true) ?? [];

// TODO: Parse query parameters for filtering and searching
$queryParams = $_GET;

/**
 * Function: Get all students or search for specific students
 * Method: GET
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by name, student_id, or email
 *   - sort: Optional field to sort by (name, student_id, email)
 *   - order: Optional sort order (asc or desc)
 */
function getStudents($db) {
    global $queryParams;

    // TODO: Check if search parameter exists
    $search = isset($queryParams['search']) ? '%' . $queryParams['search'] . '%' : null;

    // TODO: Check if sort and order parameters exist
    $allowedSort = ['name', 'student_id', 'email'];
    $sort = in_array($queryParams['sort'] ?? '', $allowedSort) ? $queryParams['sort'] : 'id';
    $order = strtolower($queryParams['order'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';

    // TODO: Prepare the SQL query using PDO
    $sql = "SELECT student_id, name, email, created_at FROM students";
    if ($search) {
        $sql .= " WHERE name LIKE :search OR student_id LIKE :search OR email LIKE :search";
    }
    $sql .= " ORDER BY $sort $order";

    $stmt = $db->prepare($sql);

    // TODO: Bind parameters if using search
    if ($search) {
        $stmt->bindParam(':search', $search);
    }

    // TODO: Execute the query
    $stmt->execute();

    // TODO: Fetch all results as an associative array
    $students = $stmt->fetchAll();

    // TODO: Return JSON response with success status and data
    sendResponse(['success' => true, 'data' => $students]);
}

/**
 * Function: Get a single student by student_id
 * Method: GET
 * 
 * Query Parameters:
 *   - student_id: The student's university ID
 */
function getStudentById($db, $studentId) {
    // TODO: Prepare SQL query to select student by student_id
    $stmt = $db->prepare("SELECT student_id, name, email, created_at FROM students WHERE student_id = :sid");

    // TODO: Bind the student_id parameter
    $stmt->bindParam(':sid', $studentId);

    // TODO: Execute the query
    $stmt->execute();

    // TODO: Fetch the result
    $student = $stmt->fetch();

    // TODO: Check if student exists
    if ($student) {
        sendResponse(['success' => true, 'data' => $student]);
    } else {
        sendResponse(['error' => 'Student not found'], 404);
    }
}

/**
 * Function: Create a new student
 * Method: POST
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (must be unique)
 *   - name: Student's full name
 *   - email: Student's email (must be unique)
 *   - password: Default password (will be hashed)
 */
function createStudent($db, $data) {
    // TODO: Validate required fields
    $required = ['student_id', 'name', 'email', 'password'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            sendResponse(['error' => "$field is required"], 400);
        }
    }

    // TODO: Sanitize input data
    $student_id = sanitizeInput($data['student_id']);
    $name       = sanitizeInput($data['name']);
    $email      = sanitizeInput($data['email']);
    $password   = $data['password'];

    if (!validateEmail($email)) {
        sendResponse(['error' => 'Invalid email format'], 400);
    }

    // TODO: Check if student_id or email already exists
    $stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE student_id = :sid OR email = :email");
    $stmt->execute([':sid' => $student_id, ':email' => $email]);
    if ($stmt->fetchColumn() > 0) {
        sendResponse(['error' => 'student_id or email already exists'], 409);
    }

    // TODO: Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // TODO: Prepare INSERT query
    $stmt = $db->prepare("INSERT INTO students (student_id, name, email, password) VALUES (:sid, :name, :email, :password)");

    // TODO: Bind parameters
    $success = $stmt->execute([
        ':sid' => $student_id,
        ':name' => $name,
        ':email' => $email,
        ':password' => $hashedPassword
    ]);

    // TODO: Check if insert was successful
    if ($success) {
        sendResponse(['success' => true, 'message' => 'Student created', 'student_id' => $student_id], 201);
    } else {
        sendResponse(['error' => 'Failed to create student'], 500);
    }
}

/**
 * Function: Update an existing student
 * Method: PUT
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (to identify which student to update)
 *   - name: Updated student name (optional)
 *   - email: Updated student email (optional)
 */
function updateStudent($db, $data) {
    if (empty($data['student_id'])) {
        sendResponse(['error' => 'student_id is required'], 400);
    }
    $student_id = sanitizeInput($data['student_id']);

    // Check if student exists
    $stmt = $db->prepare("SELECT * FROM students WHERE student_id = :sid");
    $stmt->execute([':sid' => $student_id]);
    $student = $stmt->fetch();
    if (!$student) {
        sendResponse(['error' => 'Student not found'], 404);
    }

    $fields = [];
    $params = [':sid' => $student_id];

    if (!empty($data['name'])) {
        $fields[] = "name = :name";
        $params[':name'] = sanitizeInput($data['name']);
    }
    if (!empty($data['email'])) {
        $email = sanitizeInput($data['email']);
        if (!validateEmail($email)) {
            sendResponse(['error' => 'Invalid email format'], 400);
        }
        // Check if email exists for other student
        $stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE email = :email AND student_id != :sid");
        $stmt->execute([':email' => $email, ':sid' => $student_id]);
        if ($stmt->fetchColumn() > 0) {
            sendResponse(['error' => 'Email already in use'], 409);
        }
        $fields[] = "email = :email";
        $params[':email'] = $email;
    }

    if (empty($fields)) {
        sendResponse(['error' => 'No fields to update'], 400);
    }

    $sql = "UPDATE students SET " . implode(', ', $fields) . " WHERE student_id = :sid";
    $stmt = $db->prepare($sql);
    $success = $stmt->execute($params);

    if ($success) {
        sendResponse(['success' => true, 'message' => 'Student updated']);
    } else {
        sendResponse(['error' => 'Failed to update student'], 500);
    }
}

/**
 * Function: Delete a student
 * Method: DELETE
 * 
 * Query Parameters or JSON Body:
 *   - student_id: The student's university ID
 */
function deleteStudent($db, $studentId) {
    if (empty($studentId)) {
        sendResponse(['error' => 'student_id is required'], 400);
    }
    $studentId = sanitizeInput($studentId);

    // Check if student exists
    $stmt = $db->prepare("SELECT * FROM students WHERE student_id = :sid");
    $stmt->execute([':sid' => $studentId]);
    if (!$stmt->fetch()) {
        sendResponse(['error' => 'Student not found'], 404);
    }

    // Delete student
    $stmt = $db->prepare("DELETE FROM students WHERE student_id = :sid");
    $success = $stmt->execute([':sid' => $studentId]);

    if ($success) {
        sendResponse(['success' => true, 'message' => 'Student deleted']);
    } else {
        sendResponse(['error' => 'Failed to delete student'], 500);
    }
}

/**
 * Function: Change password
 * Method: POST with action=change_password
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (identifies whose password to change)
 *   - current_password: The student's current password
 *   - new_password: The new password to set
 */
function changePassword($db, $data) {
    $required = ['student_id', 'current_password', 'new_password'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            sendResponse(['error' => "$field is required"], 400);
        }
    }

    $student_id = sanitizeInput($data['student_id']);
    $current_password = $data['current_password'];
    $new_password = $data['new_password'];

    if (strlen($new_password) < 8) {
        sendResponse(['error' => 'New password must be at least 8 characters'], 400);
    }

    // Retrieve current password hash
    $stmt = $db->prepare("SELECT password FROM students WHERE student_id = :sid");
    $stmt->execute([':sid' => $student_id]);
    $row = $stmt->fetch();
    if (!$row) {
        sendResponse(['error' => 'Student not found'], 404);
    }

    // Verify current password
    if (!password_verify($current_password, $row['password'])) {
        sendResponse(['error' => 'Current password is incorrect'], 401);
    }

    // Hash new password
    $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);

    // Update password in DB
    $stmt = $db->prepare("UPDATE students SET password = :password WHERE student_id = :sid");
    $success = $stmt->execute([':password' => $hashedPassword, ':sid' => $student_id]);

    if ($success) {
        sendResponse(['success' => true, 'message' => 'Password changed']);
    } else {
        sendResponse(['error' => 'Failed to change password'], 500);
    }
}

// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    if ($method === 'GET') {
        if (!empty($queryParams['student_id'])) {
            getStudentById($db, $queryParams['student_id']);
        } else {
            getStudents($db);
        }
    } elseif ($method === 'POST') {
        if (!empty($queryParams['action']) && $queryParams['action'] === 'change_password') {
            changePassword($db, $input);
        } else {
            createStudent($db, $input);
        }
    } elseif ($method === 'PUT') {
        updateStudent($db, $input);
    } elseif ($method === 'DELETE') {
        $sid = $queryParams['student_id'] ?? $input['student_id'] ?? null;
        deleteStudent($db, $sid);
    } else {
        sendResponse(['error' => 'Method not allowed'], 405);
    }
} catch (PDOException $e) {
    sendResponse(['error' => 'Database error'], 500);
} catch (Exception $e) {
    sendResponse(['error' => 'Server error'], 500);
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

?>
