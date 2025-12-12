<?php
/**
* Authentication Handler for Login Form
*
* This PHP script handles user authentication via POST requests from the Fetch API.
* It validates credentials against a MySQL database using PDO,
* creates sessions, and returns JSON responses.
*/
 
// --- Session Management ---
// TODO: Start a PHP session using session_start()
// This must be called before any output is sent to the browser
// Sessions allow us to store user data across multiple pages
session_start();
 
// --- Set Response Headers ---
// TODO: Set the Content-Type header to 'application/json'
// This tells the browser that we're sending JSON data back
header("Content-Type: application/json; charset=UTF-8");
 
// TODO: (Optional) Set CORS headers if your frontend and backend are on different domains
// You'll need headers for Access-Control-Allow-Origin, Methods, and Headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
 
// --- Check Request Method ---
// TODO: Verify that the request method is POST
// Use the $_SERVER superglobal to check the REQUEST_METHOD
// If the request is not POST, return an error response and exit
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid request method. POST required."
    ]);
    exit;
}
 
// --- Get POST Data ---
// TODO: Retrieve the raw POST data
// The Fetch API sends JSON data in the request body
// Use file_get_contents with 'php://input' to read the raw request body
$rawData = file_get_contents("php://input");
 
// TODO: Decode the JSON data into a PHP associative array
// Use json_decode with the second parameter set to true
$data = json_decode($rawData, true);
 
// TODO: Extract the email and password from the decoded data
// Check if both 'email' and 'password' keys exist in the array
// If either is missing, return an error response and exit
if (!isset($data['email']) || !isset($data['password'])) {
    echo json_encode([
        "success" => false,
        "message" => "Email and password are required."
    ]);
    exit;
}
 
// TODO: Store the email and password in variables
// Trim any whitespace from the email
$email = trim($data['email']);
$password = $data['password'];
 
// --- Server-Side Validation (Optional but Recommended) ---
// TODO: Validate the email format on the server side
// Use the appropriate filter function for email validation
// If invalid, return an error response and exit
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid email format."
    ]);
    exit;
}
 
// TODO: Validate the password length (minimum 8 characters)
// If invalid, return an error response and exit
if (strlen($password) < 8) {
    echo json_encode([
        "success" => false,
        "message" => "Password must be at least 8 characters."
    ]);
    exit;
}
 
// --- Database Connection ---
// TODO: Get the database connection using the provided function
// Assume getDBConnection() returns a PDO instance with error mode set to exception
// The function is defined elsewhere (e.g., in a config file or db.php)
 
try {
    // TODO: Wrap database operations in a try-catch block to handle PDO exceptions
 
    // REAL PDO CONNECTION REPLACING JSON MOCK
    $pdo = new PDO(
    "mysql:host=localhost;dbname=course;charset=utf8mb4",  // correct DSN
    "admin",            // DB_USER from init.sh
    "password123",      // DB_PASS from init.sh
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);
 
    // --- Prepare SQL Query ---
    // TODO: Write a SQL SELECT query to find the user by email
    // Select the following columns: id, name, email, password
    // Use a WHERE clause to filter by email
    // IMPORTANT: Use a placeholder (? or :email) for the email value
    // This prevents SQL injection attacks
   
    $sql = "SELECT id, name, email, password, is_admin FROM users WHERE email = :email";  // TODO: Write a SQL SELECT query to find the user by email
    $stmt = $pdo->prepare($sql);  // TODO: Prepare the SQL statement using PDO prepare
    // --- Prepare the Statement ---
    // TODO: Prepare the SQL statement using the PDO prepare method
    // Store the result in a variable
    // Prepared statements protect against SQL injection
   
    // --- Execute the Query ---
    // TODO: Execute the prepared statement with the email parameter
    // Bind the email value to the placeholder
    $stmt->execute(["email" => $email]);
 
    // --- Fetch User Data ---
    // TODO: Fetch the user record from the database
    // Use the fetch method with PDO::FETCH_ASSOC
    // This returns an associative array of the user data, or false if no user found
    $userFound = $stmt->fetch();
 
    // --- Verify User Exists and Password Matches ---
    // TODO: Check if a user was found
    if ($userFound) {
 
        // TODO: If user exists, verify the password
        // Use password_verify() to compare the submitted password with the hashed password from database
        if (password_verify($password, $userFound['password'])) {
 
            // --- Handle Successful Authentication ---
            // TODO: Store user information in session variables
            // Store: user_id, user_name, user_email, logged_in
            $_SESSION['user_id'] = $userFound['id'];
            $_SESSION['user_name'] = $userFound['name'];
            $_SESSION['user_email'] = $userFound['email'];
            $_SESSION['is_admin'] = $userFound['is_admin'];
            $_SESSION['logged_in'] = true;
 
            // TODO: Prepare a success response array
            $response = [
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $userFound['id'],
                    'name' => $userFound['name'],
                    'email' => $userFound['email'],
                    'is_admin' => $userFound['is_admin']
                ]
            ];
 
            // TODO: Encode the response array as JSON and echo it
            echo json_encode($response);
 
            // TODO: Exit the script to prevent further execution
            exit;
 
        } else {
            // --- Handle Failed Authentication ---
            // TODO: Prepare an error response array
            $response = [
                'success' => false,
                'message' => 'Invalid email or password'
            ];
 
            // TODO: Encode the error response as JSON and echo it
            echo json_encode($response);
 
            // TODO: Exit the script
            exit;
        }
    } else {
        // User not found
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email or password'
        ]);
        exit;
    }
 
} catch (PDOException $e) {
    // TODO: Catch PDO exceptions in the catch block
    // TODO: Log the error for debugging
     error_log("Login error: " . $e->getMessage());
 
    // TODO: Return a generic error message to the client
   echo json_encode([
        'success' => false,
       'message' => 'An internal error occurred. Please try again later.'
   ]);
 
    // TODO: Exit the script
   exit;
}
 
// --- End of Script ---
?>
 
