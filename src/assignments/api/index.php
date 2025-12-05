<?php
// index.php – REST API for assignments + comments

header('Content-Type: application/json');

// Simple helper to send JSON and exit
function send_json($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Database connection (matches init.sh / schema.sql)
$dsn  = 'mysql:host=127.0.0.1;dbname=course;charset=utf8mb4';
$user = 'admin';
$pass = 'password123';

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    send_json(['error' => 'Database connection failed'], 500);
}

// Read resource and method
$resource = $_GET['resource'] ?? null;
$method   = $_SERVER['REQUEST_METHOD'];

// Helper to read JSON body
function get_json_body(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return [];
    }
    return $data;
}

// ---------------------------------------------------------------------
// ASSIGNMENTS RESOURCE
// ---------------------------------------------------------------------
if ($resource === 'assignments') {
    if ($method === 'GET') {
        // If id is provided, return one; otherwise all
        if (isset($_GET['id'])) {
            $id = (int) $_GET['id'];
            $stmt = $pdo->prepare(
                'SELECT id, title, description, due_date, files
                 FROM assignments
                 WHERE id = :id'
            );
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch();

            if (!$row) {
                send_json(['error' => 'Assignment not found'], 404);
            }

            $files = $row['files'] ? json_decode($row['files'], true) : [];
            if (!is_array($files)) {
                $files = [];
            }

            send_json([
                'id'          => (int) $row['id'],
                'title'       => $row['title'],
                'description' => $row['description'],
                'dueDate'     => $row['due_date'],
                'files'       => $files,
            ]);
        } else {
            // All assignments
            $stmt = $pdo->query(
                'SELECT id, title, description, due_date, files
                 FROM assignments
                 ORDER BY due_date ASC'
            );
            $rows = $stmt->fetchAll();

            $out = [];
            foreach ($rows as $row) {
                $files = $row['files'] ? json_decode($row['files'], true) : [];
                if (!is_array($files)) {
                    $files = [];
                }
                $out[] = [
                    'id'          => (int) $row['id'],
                    'title'       => $row['title'],
                    'description' => $row['description'],
                    'dueDate'     => $row['due_date'],
                    'files'       => $files,
                ];
            }

            send_json($out);
        }
    }

    if ($method === 'POST') {
        $data = get_json_body();

        $title       = trim($data['title']       ?? '');
        $description = trim($data['description'] ?? '');
        $dueDate     = trim($data['dueDate']     ?? '');
        $files       = $data['files']           ?? [];

        if ($title === '' || $description === '' || $dueDate === '') {
            send_json(['error' => 'Missing required fields'], 400);
        }

        if (!is_array($files)) {
            $files = [];
        }

        $stmt = $pdo->prepare(
            'INSERT INTO assignments (title, description, due_date, files)
             VALUES (:t, :d, :dd, :f)'
        );
        $stmt->execute([
            ':t'  => $title,
            ':d'  => $description,
            ':dd' => $dueDate,
            ':f'  => json_encode($files),
        ]);

        $id = (int) $pdo->lastInsertId();

        send_json([
            'message' => 'Assignment created',
            'id'      => $id,
        ], 201);
    }

    if ($method === 'PUT') {
        $data = get_json_body();
        $id   = isset($data['id']) ? (int) $data['id'] : 0;

        if ($id <= 0) {
            send_json(['error' => 'Missing or invalid id'], 400);
        }

        $title       = trim($data['title']       ?? '');
        $description = trim($data['description'] ?? '');
        $dueDate     = trim($data['dueDate']     ?? '');
        $files       = $data['files']           ?? [];

        if (!is_array($files)) {
            $files = [];
        }

        $stmt = $pdo->prepare(
            'UPDATE assignments
             SET title = :t,
                 description = :d,
                 due_date = :dd,
                 files = :f
             WHERE id = :id'
        );
        $stmt->execute([
            ':t'  => $title,
            ':d'  => $description,
            ':dd' => $dueDate,
            ':f'  => json_encode($files),
            ':id' => $id,
        ]);

        if ($stmt->rowCount() === 0) {
            send_json(['error' => 'Assignment not found or unchanged'], 404);
        }

        send_json(['message' => 'Assignment updated']);
    }

    if ($method === 'DELETE') {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            send_json(['error' => 'Missing or invalid id'], 400);
        }

        $stmt = $pdo->prepare('DELETE FROM assignments WHERE id = :id');
        $stmt->execute([':id' => $id]);

        if ($stmt->rowCount() === 0) {
            send_json(['error' => 'Assignment not found'], 404);
        }

        send_json(['message' => 'Assignment deleted']);
    }

    // Unsupported method
    send_json(['error' => 'Method not allowed'], 405);
}

// ---------------------------------------------------------------------
// COMMENTS RESOURCE (assignment comments)
// ---------------------------------------------------------------------
if ($resource === 'comments') {
    if ($method === 'GET') {
        $assignmentId = isset($_GET['assignment_id'])
            ? (int) $_GET['assignment_id']
            : 0;

        if ($assignmentId <= 0) {
            send_json(['error' => 'Missing or invalid assignment_id'], 400);
        }

        $stmt = $pdo->prepare(
            'SELECT id, assignment_id, author, text, created_at
             FROM comments_assignment
             WHERE assignment_id = :aid
             ORDER BY created_at ASC, id ASC'
        );
        $stmt->execute([':aid' => $assignmentId]);
        $rows = $stmt->fetchAll();

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id'           => (int) $row['id'],
                'assignmentId' => (int) $row['assignment_id'],
                'author'       => $row['author'],
                'text'         => $row['text'],
                'createdAt'    => $row['created_at'],
            ];
        }

        send_json($out);
    }

    if ($method === 'POST') {
        $data = get_json_body();

        $assignmentId = isset($data['assignmentId'])
            ? (int) $data['assignmentId']
            : 0;
        $author = trim($data['author'] ?? 'Anonymous');
        $text   = trim($data['text']   ?? '');

        if ($assignmentId <= 0 || $text === '') {
            send_json(['error' => 'Missing required fields'], 400);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO comments_assignment (assignment_id, author, text)
             VALUES (:aid, :a, :t)'
        );
        $stmt->execute([
            ':aid' => $assignmentId,
            ':a'   => $author,
            ':t'   => $text,
        ]);

        $id = (int) $pdo->lastInsertId();

        send_json([
            'message'      => 'Comment created',
            'id'           => $id,
            'assignmentId' => $assignmentId,
        ], 201);
    }

    if ($method === 'PUT') {
        $data = get_json_body();
        $id   = isset($data['id']) ? (int) $data['id'] : 0;

        if ($id <= 0) {
            send_json(['error' => 'Missing or invalid id'], 400);
        }

        $author = trim($data['author'] ?? 'Anonymous');
        $text   = trim($data['text']   ?? '');

        if ($text === '') {
            send_json(['error' => 'Comment text cannot be empty'], 400);
        }

        $stmt = $pdo->prepare(
            'UPDATE comments_assignment
             SET author = :a,
                 text   = :t
             WHERE id = :id'
        );
        $stmt->execute([
            ':a'  => $author,
            ':t'  => $text,
            ':id' => $id,
        ]);

        if ($stmt->rowCount() === 0) {
            send_json(['error' => 'Comment not found or unchanged'], 404);
        }

        send_json(['message' => 'Comment updated']);
    }

    if ($method === 'DELETE') {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            send_json(['error' => 'Missing or invalid id'], 400);
        }

        $stmt = $pdo->prepare(
            'DELETE FROM comments_assignment WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);

        if ($stmt->rowCount() === 0) {
            send_json(['error' => 'Comment not found'], 404);
        }

        send_json(['message' => 'Comment deleted']);
    }

    send_json(['error' => 'Method not allowed'], 405);
}

// ---------------------------------------------------------------------
// Unknown resource
// ---------------------------------------------------------------------
send_json(['error' => 'Invalid resource'], 400);
