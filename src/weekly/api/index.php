<?php
/**
 * Weekly Course Breakdown API
 * 
 * This is a RESTful API that handles all CRUD operations for weekly course content
 * and discussion comments. It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: weeks
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - week_id (VARCHAR(50), UNIQUE) - Unique identifier (e.g., "week_1")
 *   - title (VARCHAR(200))
 *   - start_date (DATE)
 *   - description (TEXT)
 *   - links (TEXT) - JSON encoded array of links
 *   - created_at (TIMESTAMP)
 *   - updated_at (TIMESTAMP)
 * 
 * Table: comments // commets_week
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - week_id (VARCHAR(50)) - Foreign key reference to weeks.week_id
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve week(s) or comment(s)
 *   - POST: Create a new week or comment
 *   - PUT: Update an existing week
 *   - DELETE: Delete a week or comment
 * 
 * Response Format: JSON
 */

// ============================================================================
// SETUP AND CONFIGURATION
// ============================================================================

// TODO: Set headers for JSON response and CORS
// Set Content-Type to application/json
// Allow cross-origin requests (CORS) if needed
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
// Allow specific headers (Content-Type, Authorization)
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");



// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// TODO: Include the database connection class
// Assume the Database class has a method getConnection() that returns a PDO instance
// Example: require_once '../config/Database.php';
require_once '../config/Database.php';
// TODO: Get the PDO database connection
// Example: $database = new Database();
//          $db = $database->getConnection();
$database = new Database();
$db = $database->getConnection();

// TODO: Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']
$method = $_SERVER['REQUEST_METHOD'];

// TODO: Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode()
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);

// TODO: Parse query parameters
// Get the 'resource' parameter to determine if request is for weeks or comments
// Example: ?resource=weeks or ?resource=comments
$resource = $_GET['resource'] ?? 'weeks';



// ============================================================================
// WEEKS CRUD OPERATIONS
// ============================================================================

/**
 * Function: Get all weeks or search for specific weeks
 * Method: GET
 * Resource: weeks
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by title or description
 *   - sort: Optional field to sort by (title, start_date)
 *   - order: Optional sort order (asc or desc, default: asc)
 */
function getAllWeeks($db) {
    // TODO: Initialize variables for search, sort, and order from query parameters
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $sort   = isset($_GET['sort']) ? $_GET['sort'] : 'start_date';
    $order  = isset($_GET['order']) ? $_GET['order'] : 'asc';

    // TODO: Start building the SQL query
    // Base query: SELECT week_id, title, start_date, description, links, created_at FROM weeks
    $sql = "SELECT id, title, start_date, description, links, created_at, updated_at FROM weeks";    
    // TODO: Check if search parameter exists
    // If yes, add WHERE clause using LIKE for title and description
    // Example: WHERE title LIKE ? OR description LIKE ?
   $params = [];
    if (isset($_GET['search']) && trim($search) !== '') {
        $sql .= " WHERE title LIKE ? OR description LIKE ?";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    // TODO: Check if sort parameter exists
    // Validate sort field to prevent SQL injection (only allow: title, start_date, created_at)
    // If invalid, use default sort field (start_date)
    $allowedSortFields = ['title', 'start_date', 'created_at', 'updated_at'];
    if (!in_array($sort, $allowedSortFields)) {
        $sort = 'start_date';
    }
    // TODO: Check if order parameter exists
    // Validate order to prevent SQL injection (only allow: asc, desc)
    // If invalid, use default order (asc)

    $order = strtolower($order);
    if (!in_array($order, ['asc', 'desc'])) {
        $order = 'asc';
    }
    $order = strtoupper($order);
    // TODO: Add ORDER BY clause to the query
    $sql .= " ORDER BY $sort $order";
    // TODO: Prepare the SQL query using PDO
    //$sql = "SELECT week_id, title, start_date, description, links, created_at FROM weeks";
    $stmt = $db->prepare($sql);
    // TODO: Bind parameters if using search
    // Use wildcards for LIKE: "%{$searchTerm}%"
    if (isset($_GET['search']) && trim($search) !== '') {
        $searchTerm = "%$search%";
        $stmt->bindParam(1, $searchTerm, PDO::PARAM_STR);
        $stmt->bindParam(2, $searchTerm, PDO::PARAM_STR);
    }
      
    // TODO: Execute the query
    $stmt->execute(); 
    // TODO: Fetch all results as an associative array
    $weeks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // TODO: Process each week's links field
    // Decode the JSON string back to an array using json_decode()
    foreach ($weeks as &$week) {
        $week['links'] = json_decode($week['links'], true) ?? [];
    }
    // TODO: Return JSON response with success status and data
    // Use sendResponse() helper functiom
    sendResponse(['success' => true, 'data' => $weeks]);
}


/**
 * Function: Get a single week by week_id
 * Method: GET
 * Resource: weeks
 * 
 * Query Parameters:
 *   - week_id: The unique week identifier (e.g., "week_1")
 */
function getWeekById($db, $id) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    if (!isset($id)) {
        sendError('id is required', 400);
        return;
    }
    // TODO: Prepare SQL query to select week by week_id
    // SELECT week_id, title, start_date, description, links, created_at FROM weeks WHERE week_id = ?
     $sql = "SELECT id, title, start_date, description, links, created_at, updated_at 
            FROM weeks 
            WHERE id = ?";

    $stmt = $db->prepare($sql);
    // TODO: Bind the week_id parameter
    $stmt->bindValue(1, $id);
    // TODO: Execute the query
    $stmt->execute();
    // TODO: Fetch the result
    $week = $stmt->fetch(PDO::FETCH_ASSOC);
    // TODO: Check if week exists
    // If yes, decode the links JSON and return success response with week data
    // If no, return error response with 404 status
    if ($week) {
        // Decode links JSON
        $week['links'] = json_decode($week['links'], true) ?? [];

        sendResponse([
            'success' => true,
            'data'    => $week
        ]);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Week not found'
        ], 404);
    }

}


/**
 * Function: Create a new week
 * Method: POST
 * Resource: weeks
 * 
 * Required JSON Body:
 *   - week_id: Unique week identifier (e.g., "week_1")
 *   - title: Week title (e.g., "Week 1: Introduction to HTML")
 *   - start_date: Start date in YYYY-MM-DD format
 *   - description: Week description
 *   - links: Array of resource links (will be JSON encoded)
 */
function createWeek($db, $data) {
    // TODO: Validate required fields
    // Check if week_id, title, start_date, and description are provided
    // If any field is missing, return error response with 400 status
    if (
        empty($data['id']) ||
        empty($data['title']) ||
        empty($data['start_date']) ||
        empty($data['description']))
     {
        sendResponse([
            'success' => false,
            'message' => 'Missing required fields'
        ], 400);
        return;
    }
    // TODO: Sanitize input data
    // Trim whitespace from title, description, and week_id
    $id      = sanitizeInput(trim($data['id']));
    $title       = sanitizeInput(trim($data['title']));
    $startDate   = sanitizeInput(trim($data['start_date']));
    $description = sanitizeInput(trim($data['description']));
    // TODO: Validate start_date format
    // Use a regex or DateTime::createFromFormat() to verify YYYY-MM-DD format
    // If invalid, return error response with 400 status
    $date = DateTime::createFromFormat('Y-m-d', $startDate);

    if (!$date || $date->format('Y-m-d') !== $startDate) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid start_date format. Use YYYY-MM-DD'
        ], 400);
        return;
    }
    // TODO: Check if week_id already exists
    // Prepare and execute a SELECT query to check for duplicates
    // If duplicate found, return error response with 409 status (Conflict)
    $checkSql = "SELECT id FROM weeks WHERE id = ?";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute([$id]);

    if ($checkStmt->fetch()) {
        sendResponse([
            'success' => false,
            'message' => 'id already exists'
        ], 409);
        return;
    }

    // TODO: Handle links array
    // If links is provided and is an array, encode it to JSON using json_encode()
    // If links is not provided, use an empty array []
    if (isset($data['links']) && is_array($data['links'])) {
        $linksJson = json_encode($data['links']);
    } else {
        $linksJson = json_encode([]); // empty array
    }
    // TODO: Prepare INSERT query
    // INSERT INTO weeks (week_id, title, start_date, description, links) VALUES (?, ?, ?, ?, ?)
    $sql = "INSERT INTO weeks (id, title, start_date, description, links) 
        VALUES (?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    // TODO: Bind parameters
    $success = $stmt->execute([
        $id,
        $title,
        $startDate,
        $description,
        $linksJson
    ]);
    // TODO: Execute the query
    
    // TODO: Check if insert was successful
    // If yes, return success response with 201 status (Created) and the new week data
    // If no, return error response with 500 status
    if ($success) {
        sendResponse([
            'success' => true,
            'message' => 'Week created successfully',
            'data' => [
            'week_id' => $id,
            'title' => $title,
            'start_date' => $startDate,
            'description' => $description,
            'links' => json_decode($linksJson, true)
            ]
        ], 201);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to create week'
        ], 500);
    }
 

}
/**
 * Function: Update an existing week
 * Method: PUT
 * Resource: weeks
 * 
 * Required JSON Body:
 *   - week_id: The week identifier (to identify which week to update)
 *   - title: Updated week title (optional)
 *   - start_date: Updated start date (optional)
 *   - description: Updated description (optional)
 *   - links: Updated array of links (optional)
 */
function updateWeek($db, $data) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
   if (!isset($data['id'])) {
        sendResponse([
            'success' => false,
            'message' => 'id is required'
        ], 400);
        return;
    }


    $id = $data['id'];

    // TODO: Check if week exists
    // Prepare and execute a SELECT query to find the week
    // If not found, return error response with 404 status
    $checkSql = "SELECT * FROM weeks WHERE id = ?";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->execute([$id]);
    $existingWeek = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingWeek) {
        sendResponse([
            'success' => false,
            'message' => 'Week not found'
        ], 404);
        return;
    }
    // TODO: Build UPDATE query dynamically based on provided fields
    // Initialize an array to hold SET clauses
    // Initialize an array to hold values for binding
    $setClauses = [];
    $values = [];
    // TODO: Check which fields are provided and add to SET clauses
    // If title is provided, add "title = ?"
    // If start_date is provided, validate format and add "start_date = ?"
    // If description is provided, add "description = ?"
    // If links is provided, encode to JSON and add "links = ?"
    if (!empty($data['title'])) {
        $title = sanitizeInput(trim($data['title']));
        $setClauses[] = "title = ?";
        $values[] = $title;
    }
    if (!empty($data['start_date'])) {
        $startDate = sanitizeInput(trim($data['start_date']));
        $date = DateTime::createFromFormat('Y-m-d', $startDate);
        if (!$date || $date->format('Y-m-d') !== $startDate) {
            sendResponse([
                'success' => false,
                'message' => 'Invalid start_date format. Use YYYY-MM-DD'
            ], 400);
            return;
        }
        $setClauses[] = "start_date = ?";
        $values[] = $startDate;
    }

     if (!empty($data['description'])) {
        $description = sanitizeInput(trim($data['description']));
        $setClauses[] = "description = ?";
        $values[] = $description;
    }

    if (isset($data['links'])) {
        if (is_array($data['links'])) {
            $linksJson = json_encode($data['links']);
        } else {
            $linksJson = json_encode([]);
        }
        $setClauses[] = "links = ?";
        $values[] = $linksJson;
    }


    // TODO: If no fields to update, return error response with 400 status
    if (empty($setClauses)) {
        sendResponse([
            'success' => false,
            'message' => 'No fields provided for update'
        ], 400);
        return;
    }
    // TODO: Add updated_at timestamp to SET clauses
    // Add "updated_at = CURRENT_TIMESTAMP"
    $setClauses[] = "updated_at = CURRENT_TIMESTAMP";
    // TODO: Build the complete UPDATE query
    // UPDATE weeks SET [clauses] WHERE week_id = ?
    $sql = "UPDATE weeks SET " . implode(', ', $setClauses) . " WHERE id = ?";    // TODO: Prepare the query
    $stmt = $db->prepare($sql);
    // TODO: Bind parameters dynamically
    // Bind values array and then bind week_id at the end
    $values[] = $id;

    // TODO: Execute the query
    $success = $stmt->execute($values);
    // TODO: Check if update was successful
    // If yes, return success response with updated week data
    // If no, return error response with 500 status
      if ($success) {
        $stmt = $db->prepare("SELECT id, title, start_date, description, links, created_at, updated_at FROM weeks WHERE id = ?");
        $stmt->execute([$id]);
        $updatedWeek = $stmt->fetch(PDO::FETCH_ASSOC);
        $updatedWeek['links'] = json_decode($updatedWeek['links'], true) ?? [];
        
        sendResponse([
            'success' => true,
            'message' => 'Week updated successfully',
            'data' => $updatedWeek
        ]);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to update week'
        ], 500);
    }
}




/**
 * Function: Delete a week
 * Method: DELETE
 * Resource: weeks
 * 
 * Query Parameters or JSON Body:
 *   - week_id: The week identifier
 */
function deleteWeek($db, $id) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
     if (empty($id)) {
        sendResponse([
            'success' => false,
            'message' => 'id is required'
        ], 400);
        return;
    }
    // TODO: Check if week exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    $checkStmt = $db->prepare("SELECT id FROM weeks WHERE id = ?");
    $checkStmt->execute([$id]);
    if ($checkStmt->rowCount() === 0) {
        sendResponse([
            'success' => false,
            'message' => 'Week not found'
        ], 404);
        return;
    }
    // TODO: Delete associated comments first (to maintain referential integrity)
    // Prepare DELETE query for comments table
    // DELETE FROM comments WHERE week_id = ?
    $deleteCommentsStmt = $db->prepare("DELETE FROM comments_week WHERE week_id = ?");
      
    // TODO: Execute comment deletion query
    $deleteCommentsStmt->execute();
    // TODO: Prepare DELETE query for week
    // DELETE FROM weeks WHERE week_id = ?
    $deleteWeekStmt = $db->prepare("DELETE FROM weeks WHERE id = ?");
    // TODO: Bind the week_id parameter
    $deleteWeekStmt->bindParam(1, $id);
    // TODO: Execute the query
    $success = $deleteWeekStmt->execute();
    // TODO: Check if delete was successful
    // If yes, return success response with message indicating week and comments deleted
    // If no, return error response with 500 status
    if ($success) {
        sendResponse([
            'success' => true,
            'message' => 'Week and comments deleted successfully'
        ]);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to delete week'
        ], 500);
    }
}


// ============================================================================
// COMMENTS CRUD OPERATIONS
// ============================================================================

/**
 * Function: Get all comments for a specific week
 * Method: GET
 * Resource: comments
 * 
 * Query Parameters:
 *   - week_id: The week identifier to get comments for
 */
function getCommentsByWeek($db, $weekId) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
   if (!isset($weekId) || empty($weekId)) {
        sendResponse([
            'success' => false,
            'message' => 'week_id is required'
        ], 400);
        return;
    }
    // TODO: Prepare SQL query to select comments for the week
    // SELECT id, week_id, author, text, created_at FROM comments WHERE week_id = ? ORDER BY created_at ASC
        $stmt = $db->prepare(
        "SELECT id, week_id, author, text, created_at 
         FROM comments_week 
         WHERE week_id = ? 
         ORDER BY created_at ASC"
    );

    // TODO: Bind the week_id parameter
    $stmt->bindParam(1, $weekId);
    // TODO: Execute the query
    $stmt->execute();
    // TODO: Fetch all results as an associative array
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // TODO: Return JSON response with success status and data
    // Even if no comments exist, return an empty array
    sendResponse([
        'success' => true,
        'data' => $comments
    ]);
}


/**
 * Function: Create a new comment
 * Method: POST
 * Resource: comments
 * 
 * Required JSON Body:
 *   - week_id: The week identifier this comment belongs to
 *   - author: Comment author name
 *   - text: Comment text content
 */
function createComment($db, $data) {
    // TODO: Validate required fields
    // Check if week_id, author, and text are provided
    // If any field is missing, return error response with 400 status
    if (
        empty($data['week_id']) ||
        empty($data['author']) ||
        empty($data['text'])
    ) {
        sendResponse([
            'success' => false,
            'message' => 'week_id, author, and text are required'
        ], 400);
        return;
    }
    // TODO: Sanitize input data
    // Trim whitespace from all fields
    $weekId = sanitizeInput(trim($data['week_id']));
    $author = sanitizeInput(trim($data['author']));
    $text   = sanitizeInput(trim($data['text']));
    // TODO: Validate that text is not empty after trimming
    // If empty, return error response with 400 status
    if (empty($text)) {
        sendResponse([
            'success' => false,
            'message' => 'Comment text cannot be empty'
        ], 400);
        return;
    }
    // TODO: Check if the week exists
    // Prepare and execute a SELECT query on weeks table
    // If week not found, return error response with 404 status
    $checkStmt = $db->prepare("SELECT id FROM weeks WHERE id = ?");
    $checkStmt->execute([$weekId]);
    $week = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$week) {
        sendResponse([
            'success' => false,
            'message' => 'Week not found'
        ], 404);
        return;
    }
    // TODO: Prepare INSERT query
    // INSERT INTO comments (week_id, author, text) VALUES (?, ?, ?)
     $stmt = $db->prepare(
        "INSERT INTO comments_week (week_id, author, text) VALUES (?, ?, ?)"
    );
    // TODO: Bind parameters
    $stmt->bindParam(1, $weekId);
    $stmt->bindParam(2, $author);
    $stmt->bindParam(3, $text);
    // TODO: Execute the query
    $success = $stmt->execute();
    // TODO: Check if insert was successful
    // If yes, get the last insert ID and return success response with 201 status
    // Include the new comment data in the response
    // If no, return error response with 500 status
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
        sendResponse([
            'success' => false,
            'message' => 'Failed to create comment'
        ], 500);
    }
}
    

/**
 * Function: Delete a comment
 * Method: DELETE
 * Resource: comments
 * 
 * Query Parameters or JSON Body:
 *   - id: The comment ID to delete
 */
function deleteComment($db, $commentId) {
    // TODO: Validate that id is provided
    // If not, return error response with 400 status
    if (empty($commentId)) {
        sendResponse([
            'success' => false,
            'message' => 'Comment ID is required'
        ], 400);
        return;
    }

    // TODO: Check if comment exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    $checkStmt = $db->prepare("SELECT id FROM comments_week WHERE id = ?");
    $checkStmt->execute([$commentId]);
    $comment = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$comment) {
        sendResponse([
            'success' => false,
            'message' => 'Comment not found'
        ], 404);
        return;
    }
    // TODO: Prepare DELETE query
    // DELETE FROM comments WHERE id = ?
    $deleteStmt = $db->prepare("DELETE FROM comments_week WHERE id = ?");
    // TODO: Bind the id parameter
    $deleteStmt->bindParam(1, $commentId);
    // TODO: Execute the query
    $success = $deleteStmt->execute();
    // TODO: Check if delete was successful
    // If yes, return success response
    // If no, return error response with 500 status
    if ($success) {
        sendResponse([
            'success' => true,
            'message' => 'Comment deleted successfully'
        ], 200);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to delete comment'
        ], 500);
    }
}



// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Determine the resource type from query parameters
    // Get 'resource' parameter (?resource=weeks or ?resource=comments)
    // If not provided, default to 'weeks'
    
    
    // Route based on resource type and HTTP method
    
    // ========== WEEKS ROUTES ==========
    if ($resource === 'weeks') {
        
        if ($method === 'GET') {
            // TODO: Check if week_id is provided in query parameters
            // If yes, call getWeekById()
            // If no, call getAllWeeks() to get all weeks (with optional search/sort)
            isset($_GET['id']) ? getWeekById($db, $_GET['id']) : getAllWeeks($db);
        } 
            
        elseif ($method === 'POST') {
            // TODO: Call createWeek() with the decoded request body
            createWeek($db, $data);
        } elseif ($method === 'PUT') {
            // TODO: Call updateWeek() with the decoded request body
            updateWeek($db, $data);
        } elseif ($method === 'DELETE') {
            // TODO: Get week_id from query parameter or request body
            // Call deleteWeek()
            deleteWeek($db, $_GET['id'] ?? $data['id']);
            // TODO: Return error for unsupported methods
            // Set HTTP status to 405 (Method Not Allowed)
            sendError("Method not allowed", 405);
        }
    }
    
    // ========== COMMENTS ROUTES ==========
    elseif ($resource === 'comments') {
        
        if ($method === 'GET') {
            // TODO: Get week_id from query parameters
            // Call getCommentsByWeek()
            getCommentsByWeek($db, $_GET['week_id']);
        } elseif ($method === 'POST') {
            // TODO: Call createComment() with the decoded request body
            createComment($db, $data);
        } elseif ($method === 'DELETE') {
            // TODO: Get comment id from query parameter or request body
            // Call deleteComment()
            deleteComment($db, $_GET['id'] ?? $data['id']);
        } else {
            // TODO: Return error for unsupported methods
            // Set HTTP status to 405 (Method Not Allowed)
            sendError("Method not allowed", 405);
        }
    }
    
    // ========== INVALID RESOURCE ==========
    else {
        // TODO: Return error for invalid resource
        // Set HTTP status to 400 (Bad Request)
        // Return JSON error message: "Invalid resource. Use 'weeks' or 'comments'"
        sendError("Invalid resource. Use 'weeks' or 'comments'", 400);
    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
    // Log the error message (optional, for debugging)
    // error_log($e->getMessage());
     error_log("Database Error: " . $e->getMessage());
    // TODO: Return generic error response with 500 status
    // Do NOT expose database error details to the client
    // Return message: "Database error occurred"
    sendError("Database error occurred", 500);

    
} catch (Exception $e) {
    // TODO: Handle general errors
    // Log the error message (optional)
    // Return error response with 500 status
    sendError("Server error occurred", 500);
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response
 * 
 * @param mixed $data - Data to send (will be JSON encoded)
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    // Use http_response_code($statusCode)
    http_response_code($statusCode);
    
    // TODO: Echo JSON encoded data
    // Use json_encode($data)
    echo json_encode($data);
    
    // TODO: Exit to prevent further execution
    exit();

}


/**
 * Helper function to send error response
 * 
 * @param string $message - Error message
 * @param int $statusCode - HTTP status code
 */
function sendError($message, $statusCode = 400) {
    // TODO: Create error response array
    // Structure: ['success' => false, 'error' => $message]
    // TODO: Call sendResponse() with the error array and status code
    sendResponse(['success' => false, 'error' => $message], $statusCode);
}


/**
 * Helper function to validate date format (YYYY-MM-DD)
 * 
 * @param string $date - Date string to validate
 * @return bool - True if valid, false otherwise
 */
function validateDate($date) {
    // TODO: Use DateTime::createFromFormat() to validate
    // Format: 'Y-m-d'
    // Check that the created date matches the input string
    // Return true if valid, false otherwise
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}


/**
 * Helper function to sanitize input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace
    $data = trim($data);
    // TODO: Strip HTML tags using strip_tags()
    $data = strip_tags($data);
    // TODO: Convert special characters using htmlspecialchars()
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    // TODO: Return sanitized data
    return $data;
}


/**
 * Helper function to validate allowed sort fields
 * 
 * @param string $field - Field name to validate
 * @param array $allowedFields - Array of allowed field names
 * @return bool - True if valid, false otherwise
 */
function isValidSortField($field, $allowedFields) {
    // TODO: Check if $field exists in $allowedFields array
    // Use in_array()
    // Return true if valid, false otherwise
    return in_array($field, $allowedFields);
}

?>
