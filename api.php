
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db_connect.php';

$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'],'/'));
$input = json_decode(file_get_contents('php://input'), true);

// Handle preflight requests
if ($method == 'OPTIONS') {
    exit(0);
}

// RESTful endpoints
switch ($method) {
    case 'GET':
        handleGetRequest($request);
        break;
    case 'POST':
        handlePostRequest($input);
        break;
    case 'PUT':
        handlePutRequest($request, $input);
        break;
    case 'DELETE':
        handleDeleteRequest($request, $input);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function handleGetRequest($request) {
    global $mysqli;
   
    if (empty($request[0])) {
        // GET /api.php - Get all tasks
        $query = "SELECT * FROM tasks ORDER BY created_at DESC";
        $result = $mysqli->query($query);
       
        $tasks = [];
        while ($row = $result->fetch_assoc()) {
            $tasks[] = $row;
        }
       
        echo json_encode(['success' => true, 'data' => $tasks]);
    } else {
        // GET /api.php/{id} - Get single task
        $id = (int)$request[0];
        $query = "SELECT * FROM tasks WHERE id = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
       
        if ($result->num_rows > 0) {
            echo json_encode(['success' => true, 'data' => $result->fetch_assoc()]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Task not found']);
        }
    }
}

function handlePostRequest($input) {
    global $mysqli;
   
    if (!isset($input['task_name']) || empty(trim($input['task_name']))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Task name is required']);
        return;
    }
   
    $task_name = trim($input['task_name']);
    $query = "INSERT INTO tasks (task_name) VALUES (?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("s", $task_name);
   
    if ($stmt->execute()) {
        $task_id = $stmt->insert_id;
        $query = "SELECT * FROM tasks WHERE id = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("i", $task_id);
        $stmt->execute();
        $result = $stmt->get_result();
       
        echo json_encode(['success' => true, 'data' => $result->fetch_assoc()]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $mysqli->error]);
    }
}

function handlePutRequest($request, $input) {
    global $mysqli;
   
    if (empty($request[0])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Task ID is required']);
        return;
    }
   
    $id = (int)$request[0];
   
    // Handle bulk operations
    if (isset($input['action'])) {
        switch ($input['action']) {
            case 'mark_all_completed':
                $query = "UPDATE tasks SET status = 'completed' WHERE status = 'pending'";
                if ($mysqli->query($query)) {
                    echo json_encode(['success' => true, 'message' => 'All pending tasks marked as completed']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => $mysqli->error]);
                }
                return;
        }
    }
   
    $updates = [];
    $params = [];
    $types = '';
   
    if (isset($input['task_name'])) {
        $updates[] = "task_name = ?";
        $params[] = trim($input['task_name']);
        $types .= 's';
    }
   
    if (isset($input['status'])) {
        $updates[] = "status = ?";
        $params[] = $input['status'];
        $types .= 's';
    }
   
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No fields to update']);
        return;
    }
   
    $query = "UPDATE tasks SET " . implode(', ', $updates) . " WHERE id = ?";
    $types .= 'i';
    $params[] = $id;
   
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param($types, ...$params);
   
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Task updated successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Task not found or no changes made']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $mysqli->error]);
    }
}

function handleDeleteRequest($request, $input) {
    global $mysqli;
   
    if (isset($input['action'])) {
        switch ($input['action']) {
            case 'delete_completed':
                $query = "DELETE FROM tasks WHERE status = 'completed'";
                if ($mysqli->query($query)) {
                    echo json_encode(['success' => true, 'message' => 'All completed tasks deleted']);
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => $mysqli->error]);
                }
                return;
        }
    }
   
    if (empty($request[0])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Task ID is required']);
        return;
    }
   
    $id = (int)$request[0];
    $query = "DELETE FROM tasks WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $id);
   
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Task deleted successfully']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Task not found']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $mysqli->error]);
    }
}

$mysqli->close();
?>
