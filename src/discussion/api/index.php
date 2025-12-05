<?php
/**
 * Discussion Board API
 * 
 * This is a RESTful API that handles all CRUD operations for the discussion board.
 * It manages both discussion topics and their replies.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: topics
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - topic_id (VARCHAR(50), UNIQUE) - The topic's unique identifier (e.g., "topic_1234567890")
 *   - subject (VARCHAR(255)) - The topic subject/title
 *   - message (TEXT) - The main topic message
 *   - author (VARCHAR(100)) - The author's name
 *   - created_at (TIMESTAMP) - When the topic was created
 * 
 * Table: replies
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - reply_id (VARCHAR(50), UNIQUE) - The reply's unique identifier (e.g., "reply_1234567890")
 *   - topic_id (VARCHAR(50)) - Foreign key to topics.topic_id
 *   - text (TEXT) - The reply message
 *   - author (VARCHAR(100)) - The reply author's name
 *   - created_at (TIMESTAMP) - When the reply was created
 * 
 * API Endpoints:
 * 
 * Topics:
 *   GET    /api/discussion.php?resource=topics              - Get all topics (with optional search)
 *   GET    /api/discussion.php?resource=topics&id={id}      - Get single topic
 *   POST   /api/discussion.php?resource=topics              - Create new topic
 *   PUT    /api/discussion.php?resource=topics              - Update a topic
 *   DELETE /api/discussion.php?resource=topics&id={id}      - Delete a topic
 * 
 * Replies:
 *   GET    /api/discussion.php?resource=replies&topic_id={id} - Get all replies for a topic
 *   POST   /api/discussion.php?resource=replies              - Create new reply
 *   DELETE /api/discussion.php?resource=replies&id={id}      - Delete a reply
 * 
 * Response Format: JSON
 */

// TODO: Set headers for JSON response and CORS

// Set Content-Type to application/json
// Allow cross-origin requests (CORS) if needed
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
// Allow specific headers (Content-Type, Authorization)

  header("Content-Type: application/json");
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
try {
// TODO: Get the PDO database connection
// $db = $database->getConnection();
    $database = new Database();
    $db = $database->getConnection();
}catch (PDOException $e){
    sendResponse(['success' => false, 'message' => 'Database connection failed'], 500);
exit;
}






// TODO: Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']
$method = $_SERVER['REQUEST_METHOD'];



// TODO: Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode()


$input = null;

 if ($method === 'POST' || $method === 'PUT') {
      $rawData = file_get_contents('php://input');   
      $input = json_decode($rawData, true);          
    }
// TODO: Parse query parameters for filtering and searching

$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

// ============================================================================
// TOPICS FUNCTIONS
// ============================================================================

/**
 * Function: Get all topics or search for specific topics
 * Method: GET
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by subject, message, or author
 *   - sort: Optional field to sort by (subject, author, created_at)
 *   - order: Optional sort order (asc or desc, default: desc)
 */
function getAllTopics($db) {
    // TODO: Initialize base SQL query
    // Select topic_id, subject, message, author, and created_at (formatted as date)

   $sql = "SELECT  id, subject, message, author, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') AS created_at FROM topics";

    // TODO: Initialize an array to hold bound parameters

    $params=[];
    
    // TODO: Check if search parameter exists in $_GET
    // If yes, add WHERE clause using LIKE for subject, message, OR author
    // Add the search term to the params array

    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = $_GET['search'];
        $sql .= " WHERE subject LIKE :search OR message LIKE :search OR author LIKE :search";
        $params[':search'] = "%$search%"; 
    }
    // TODO: Add ORDER BY clause
    // Check for sort and order parameters in $_GET
    // Validate the sort field (only allow: subject, author, created_at)
    // Validate order (only allow: asc, desc)
    // Default to ordering by created_at DESC
        $allowedSortFields = ['subject', 'author', 'created_at'];
        $allowedOrder = ['asc', 'desc'];

        $sort = isset($_GET['sort']) && in_array($_GET['sort'], $allowedSortFields) 
        ? $_GET['sort'] 
        : 'created_at';

        $order = isset($_GET['order']) && in_array(strtolower($_GET['order']), $allowedOrder) 
        ? strtoupper($_GET['order']) 
        : 'DESC';

        $sql .= " ORDER BY $sort $order";

    
    // TODO: Prepare the SQL statement
        $stmt = $db->prepare($sql);

    // TODO: Bind parameters if search was used
    // Loop through $params array and bind each parameter
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    // TODO: Execute the query
    $stmt->execute();
    // TODO: Fetch all results as an associative array
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // TODO: Return JSON response with success status and data
    // Call sendResponse() helper function or echo json_encode directly
    echo json_encode([
        'success' => true,
        'data' => $results
    ]);
}


/**
 * Function: Get a single topic by topic_id
 * Method: GET
 * 
 * Query Parameters:
 *   - id: The topic's unique identifier
 */
function getTopicById($db, $topicId) {
    // TODO: Validate that topicId is provided
    // If empty, return error with 400 status
        if (empty($topicId)) {
        echo json_encode([
            'success' => false,
            'message' => 'Topic ID is required'
        ]);
        http_response_code(400);
        exit();
    }
    // TODO: Prepare SQL query to select topic by topic_id
    // Select topic_id, subject, message, author, and created_at
    $sql = "SELECT 
                id, 
                subject, 
                message, 
                author, 
                DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') AS created_at
            FROM topics
            WHERE id = :id
            LIMIT 1";
                
    // TODO: Prepare and bind the topic_id parameter
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':id', $topicId);
    // TODO: Execute the query
     $stmt->execute();
    
    // TODO: Fetch the result
    $topic = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // TODO: Check if topic exists
    // If topic found, return success response with topic data
    // If not found, return error with 404 status
    if ($topic) {
        echo json_encode([
            'success' => true,
            'data' => $topic
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Topic not found'
        ]);
        http_response_code(404);
    }
}


/**
 * Function: Create a new topic
 * Method: POST
 * 
 * Required JSON Body:
 *   - topic_id: Unique identifier (e.g., "topic_1234567890")
 *   - subject: Topic subject/title
 *   - message: Main topic message
 *   - author: Author's name
 */
function createTopic($db, $data) {
    // TODO: Validate required fields
    // Check if topic_id, subject, message, and author are provided
    // If any required field is missing, return error with 400 status
        $requiredFields = ['id', 'subject', 'message', 'author'];
         foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            echo json_encode([
                'success' => false,
                'message' => "$field is required"
            ]);
            http_response_code(400); // Bad Request
            exit(); // Stop execution
        }
    }
    
    
    // TODO: Sanitize input data
    // Trim whitespace from all string fields
    // Use the sanitizeInput() helper function
    $data['id'] = sanitizeInput(trim($data['id']));
    $data['subject']  = sanitizeInput(trim($data['subject']));
    $data['message']  = sanitizeInput(trim($data['message']));
    $data['author']   = sanitizeInput(trim($data['author']));

      

    
    // TODO: Check if topic_id already exists
    // Prepare and execute a SELECT query to check for duplicate
    // If duplicate found, return error with 409 status (Conflict)
     $checkSql = "SELECT id FROM topics WHERE id = :id LIMIT 1";
     $stmtCheck = $db->prepare($checkSql);
     $stmtCheck->bindValue(':id', $data['id']);
     $stmtCheck->execute();

         $existingTopic = $stmtCheck->fetch(PDO::FETCH_ASSOC);

          if ($existingTopic) {
           echo json_encode([
           'success' => false,
           'message' => 'Topic ID already exists']);
         http_response_code(409); // Conflict
         exit();

}

    
    
    // TODO: Prepare INSERT query
    // Insert topic_id, subject, message, and author
    // The created_at field should auto-populate with CURRENT_TIMESTAMP
    $sql = "INSERT INTO topics (id, subject, message, author, created_at) 
        VALUES (:id, :subject, :message, :author, NOW())";

    // TODO: Prepare the statement and bind parameters
    // Bind all the sanitized values
    
        $stmt = $db->prepare($sql);

     $stmt->bindValue(':id', $data['id']);
     $stmt->bindValue(':subject', $data['subject']);
     $stmt->bindValue(':message', $data['message']);
     $stmt->bindValue(':author', $data['author']);

    
    // TODO: Execute the query
    $stmt->execute();
    
    // TODO: Check if insert was successful
    // If yes, return success response with 201 status (Created)
    // Include the topic_id in the response
    // If no, return error with 500 status
    if ($stmt->rowCount() > 0) {
            echo json_encode([
        'success' => true,
        'message' => 'Topic created successfully',
        'id' => $data['id']
    ]);
    http_response_code(201); // Created
} else {echo json_encode([
        'success' => false,
        'message' => 'Failed to create topic'
    ]);
    http_response_code(500);
}
}


/**
 * Function: Update an existing topic
 * Method: PUT
 * 
 * Required JSON Body:
 *   - topic_id: The topic's unique identifier
 *   - subject: Updated subject (optional)
 *   - message: Updated message (optional)
 */
function updateTopic($db, $data) {
    // TODO: Validate that topic_id is provided
    // If not provided, return error with 400 status

    if (empty($data['id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'id is required'
    ]);
    return;}
     $topicId = $data['id'];

    // TODO: Check if topic exists
    // Prepare and execute a SELECT query
    // If not found, return error with 404 status
    $checkStmt = $db->prepare("SELECT * FROM topics WHERE id = :id");
    $checkStmt->execute([':id' => $topicId]);
    $topic = $checkStmt->fetch(PDO::FETCH_ASSOC);

   if (!$topic) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Topic not found'
    ]);
    return;}

    
    // TODO: Build UPDATE query dynamically based on provided fields
    // Only update fields that are provided in the request
    $updates = [];
    $params = [':id' => $topicId];

if (!empty($data['subject'])) {
    $updates[] = 'subject = :subject';
    $params[':subject'] = $data['subject'];
}

if (!empty($data['message'])) {
    $updates[] = 'message = :message';
    $params[':message'] = $data['message'];
}

    
    // TODO: Check if there are any fields to update
    // If $updates array is empty, return error
    if (empty($updates)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'No fields provided to update'
    ]);
    return;
}

    
    // TODO: Complete the UPDATE query

    $sql = "UPDATE topics SET " . implode(', ', $updates) . " WHERE id = :id";

    // TODO: Prepare statement and bind parameters
    // Bind all parameters from the $params array
    $stmt = $db->prepare($sql);


    // TODO: Execute the query
    $executed = $stmt->execute($params);

    // TODO: Check if update was successful
    // If yes, return success response
    // If no rows affected, return appropriate message
    // If error, return error with 500 status
    if ($executed && $stmt->rowCount() > 0) {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Topic updated successfully'
    ]);
} elseif ($executed && $stmt->rowCount() === 0) {

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'No changes were made to the topic'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update topic'
    ]);
}

}


/**
 * Function: Delete a topic
 * Method: DELETE
 * 
 * Query Parameters:
 *   - id: The topic's unique identifier
 */
function deleteTopic($db, $topicId) {
    // TODO: Validate that topicId is provided
    // If not, return error with 400 status
    if (empty($topicId)) {
    echo json_encode([
        'success' => false,
        'message' => 'Topic ID is required'
    ]);
    http_response_code(400);
    exit();}

    
    // TODO: Check if topic exists
    // Prepare and execute a SELECT query
    // If not found, return error with 404 status$checkSql = "SELECT topic_id FROM topics WHERE topic_id = :topic_id LIMIT 1";

    
     $checkSql = "SELECT id FROM topics WHERE id = :id LIMIT 1";
     $stmtCheck = $db->prepare($checkSql);
     $stmtCheck->bindValue(':id', $topicId);
     $stmtCheck->execute();
     $existingTopic = $stmtCheck->fetch(PDO::FETCH_ASSOC);

     if (!$existingTopic) {
        echo json_encode([
            'success' => false,
            'message' => 'Topic not found'
        ]);
        http_response_code(404); 
        exit();
    }


    // TODO: Delete associated replies first (foreign key constraint)
    // Prepare DELETE query for replies table

    $deleteRepliesSql = "DELETE FROM replies WHERE topic_id = :id";
    $stmtReplies = $db->prepare($deleteRepliesSql);
    $stmtReplies->bindValue(':id', $topicId);
    $stmtReplies->execute();

    
    // TODO: Prepare DELETE query for the topic
        $deleteTopicSql = "DELETE FROM topics WHERE id = :id";
        $stmtDelete = $db->prepare($deleteTopicSql);

    
    // TODO: Prepare, bind, and execute

     $stmtDelete->bindValue(':id', $topicId);
     $stmtDelete->execute();

    
    // TODO: Check if delete was successful
    // If yes, return success response
    // If no, return error with 500 status
    if ($stmtDelete->rowCount() > 0) {
    echo json_encode([
        'success' => true,
        'message' => 'Topic deleted successfully'
    ]);
    http_response_code(200); 
    } else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete topic'
    ]);
    http_response_code(500); 
   }

}


// ============================================================================
// REPLIES FUNCTIONS
// ============================================================================

/**
 * Function: Get all replies for a specific topic
 * Method: GET
 * 
 * Query Parameters:
 *   - topic_id: The topic's unique identifier
 */
function getRepliesByTopicId($db, $id) {
    // TODO: Validate that topicId is provided
    // If not provided, return error with 400 status
        if (empty($id)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'topic_id is required'
        ]);
        return;
    }

    
    // TODO: Prepare SQL query to select all replies for the topic
    // Select reply_id, topic_id, text, author, and created_at (formatted as date)
    // Order by created_at ASC (oldest first)
        $sql = "SELECT 
                id, 
                topic_id, 
                text, 
                author, 
                DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') AS created_at
            FROM replies
            WHERE topic_id = :topic_id
            ORDER BY created_at ASC";
    
    // TODO: Prepare and bind the topic_id parameter
     $stmt = $db->prepare($sql);
     $stmt->bindValue(':topic_id', $id);
    // TODO: Execute the query
            $stmt->execute();
    // TODO: Fetch all results as an associative array
            $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // TODO: Return JSON response
    // Even if no replies found, return empty array (not an error)
        echo json_encode([
        'success' => true,
        'data' => $replies
    ]);
    http_response_code(200);
}


/**
 * Function: Create a new reply
 * Method: POST
 * 
 * Required JSON Body:
 *   - reply_id: Unique identifier (e.g., "reply_1234567890")
 *   - topic_id: The parent topic's identifier
 *   - text: Reply message text
 *   - author: Author's name
 */
function createReply($db, $data) {
    // TODO: Validate required fields
    // Check if reply_id, topic_id, text, and author are provided
    // If any field is missing, return error with 400 status
        $requiredFields = ['id', 'topic_id', 'text', 'author'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => "$field is required"
            ]);
            return;
        }
    }
    
    // TODO: Sanitize input data
    // Trim whitespace from all fields
        $id = trim($data['id']);
        $topic_id = trim($data['topic_id']);
        $text = trim($data['text']);
        $author = trim($data['author']);
    // TODO: Verify that the parent topic exists
    // Prepare and execute SELECT query on topics table
    // If topic doesn't exist, return error with 404 status (can't reply to non-existent topic)
    $stmtTopic = $db->prepare("SELECT topic_id FROM topics WHERE topic_id = :topic_id");
    $stmtTopic->bindValue(':topic_id', $topic_id);
    $stmtTopic->execute();
    if (!$stmtTopic->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Parent topic not found'
        ]);
        return;
    }
    
    // TODO: Check if reply_id already exists
    // Prepare and execute SELECT query to check for duplicate
    // If duplicate found, return error with 409 status
     $stmtCheck = $db->prepare("SELECT id FROM replies WHERE id = :id");
    $stmtCheck->bindValue(':id', $id);
    $stmtCheck->execute();
    if ($stmtCheck->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Reply ID already exists'
        ]);
        return;
    }
    
    // TODO: Prepare INSERT query
    // Insert reply_id, topic_id, text, and author
     $sql = "INSERT INTO replies (id, topic_id, text, author, created_at) 
            VALUES (:id, :topic_id, :text, :author, NOW())";
    
    // TODO: Prepare statement and bind parameters
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':id', $id);
    $stmt->bindValue(':topic_id', $topic_id);
    $stmt->bindValue(':text', $text);
    $stmt->bindValue(':author', $author);
    
    // TODO: Execute the query
        $executed = $stmt->execute();

    // TODO: Check if insert was successful
    // If yes, return success response with 201 status
    // Include the reply_id in the response
    // If no, return error with 500 status
    if ($executed && $stmt->rowCount() > 0) {
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Reply created successfully',
            'id' => $id
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create reply'
        ]);
    }
}



/**
 * Function: Delete a reply
 * Method: DELETE
 * 
 * Query Parameters:
 *   - id: The reply's unique identifier
 */
function deleteReply($db, $replyId) {
    // TODO: Validate that replyId is provided
    // If not, return error with 400 status
    if (empty($replyId)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'id is required'
         ]);
        return;
    }
    
    // TODO: Check if reply exists
    // Prepare and execute SELECT query
    // If not found, return error with 404 status
    $stmtCheck = $db->prepare("SELECT id FROM replies WHERE id = :id");
    $stmtCheck->bindValue(':id', $replyId);
    $stmtCheck->execute();
    $reply = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$reply) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Reply not found'
        ]);
        return;
    }

    
    // TODO: Prepare DELETE query
        $sql = "DELETE FROM replies WHERE id = :id";

    // TODO: Prepare, bind, and execute
     $stmtDelete = $db->prepare($sql);
    $stmtDelete->bindValue(':id', $replyId );
    $executed = $stmtDelete->execute();
    
    // TODO: Check if delete was successful
    // If yes, return success response
    // If no, return error with 500 status
     if ($executed && $stmtDelete->rowCount() > 0) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Reply deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete reply'
        ]);
    }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================


try {
    // TODO: Route the request based on resource and HTTP method
    $resource = $_GET['resource'] ?? null;
    // TODO: For GET requests, check for 'id' parameter in $_GET
    $id = $_GET['id'] ?? null; 
        // TODO: For invalid resources, return 400 Bad Request
    if (!$resource){

             http_response_code(400);
             echo json_encode([
             'success' => false,
            'message' => 'Invalid resource requested'
        ]);
        exit();
    }
       // ===================== TOPICS =====================
    if ($resource === 'topics') {

        if ($method === 'GET') {
            if ($id) {
                getTopicById($db, $id);
            } else {
                getAllTopics($db);
            }
            exit();
        }

        if ($method === 'POST') {
            createTopic($db, $input);
            exit();
        }

        if ($method === 'PUT') {
            updateTopic($db, $input);
            exit();
        }
    // TODO: For DELETE requests, get id from query parameter or request body
        if ($method === 'DELETE') {
            $deleteId = $_GET['id'] ?? ($input['id'] ?? null);
            if (!$deleteId) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID is required for deletion'
                ]);
                exit();
            }
            deleteTopic($db, $deleteId);
            exit();
        }
    // TODO: For unsupported methods, return 405 Method Not Allowed
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method Not Allowed'
        ]);
        exit();
    }

    // ===================== REPLIES =====================
    if ($resource === 'replies') {

        if ($method === 'GET') {
            $topicId = $_GET['topic_id'] ?? null;
            getRepliesByTopicId($db, $topicId);
            exit();
        }

        if ($method === 'POST') {
            createReply($db, $input);
            exit();
        }
    // TODO: For DELETE requests, get id from query parameter or request body
        if ($method === 'DELETE') {
            $replyId = $_GET['id'] ?? ($input['id'] ?? null);
            if (!$replyId) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'id is required'
                ]);
                exit();
            }
            deleteReply($db, $replyId);
            exit();
        }
    // TODO: For unsupported methods, return 405 Method Not Allowed
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method Not Allowed'
        ]);
        exit();
    }
    // TODO: For invalid resources, return 400 Bad Request
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid resource'
    ]);
    exit();
}
catch (PDOException $e) {
    // TODO: Handle database errors
    // DO NOT expose the actual error message to the client (security risk)
    // Log the error for debugging (optional)
    // Return generic error response with 500 status
        error_log("Database error: " . $e->getMessage()); 
        http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal Server Error'
    ]);
    exit();

    
} catch (Exception $e) {
    // TODO: Handle general errors
    // Log the error for debugging
    // Return error response with 500 status
     error_log("General error: " . $e->getMessage()); 
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred'
    ]);
    exit();
}



// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response and exit
 * 
 * @param mixed $data - Data to send (will be JSON encoded)
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
        http_response_code($statusCode);

    // TODO: Echo JSON encoded data
    // Make sure to handle JSON encoding errors
     $json = json_encode($data);

    if ($json === false) { 
        $json = json_encode([
            "success" => false,
            "message" => "JSON Encoding Error"
        ]);
        http_response_code(500);
    }

    echo $json;
    
    // TODO: Exit to prevent further execution
        exit();

}


/**
 * Helper function to sanitize string input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Check if data is a string
    // If not, return as is or convert to string

        if (!is_string($data)) {
        return $data;
    }
    // TODO: Trim whitespace from both ends

        $data = trim($data);
    // TODO: Remove HTML and PHP tags

        $data = strip_tags($data);
    // TODO: Convert special characters to HTML entities (prevents XSS)

        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    // TODO: Return sanitized data
        return $data;

}


/**
 * Helper function to validate resource name
 * 
 * @param string $resource - Resource name to validate
 * @return bool - True if valid, false otherwise
 */
function isValidResource($resource) {
    // TODO: Define allowed resources
        $allowed = ['topics', 'replies'];

    // TODO: Check if resource is in the allowed list
        return in_array($resource, $allowed);

}

?>
