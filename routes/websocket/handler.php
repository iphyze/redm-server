<?php
// First, set the correct content type for SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Prevent timeout and ensure proper chunked encoding
set_time_limit(0);
ignore_user_abort(false); // Changed to false to detect client disconnection
ini_set('output_buffering', 'off');
ini_set('zlib.output_compression', false);
ini_set('implicit_flush', true);
apache_setenv('no-gzip', 1);

// Clear any existing output buffers
while (ob_get_level() > 0) {
    ob_end_flush();
}
flush();

require_once('includes/connection.php');
require_once('includes/authMiddleware.php');
require_once('routes/events/reminderEvents.php');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception("Route not found", 400);
    }

    // Extract token and timestamp from path parameters
    $params = explode('/', $_GET['params']);
    
    if (count($params) < 2) {
        throw new Exception("Invalid parameters", 400);
    }

    $token = $params[0];
    $timestamp = $params[1];

    if (!$token) {
        throw new Exception("Unauthorized", 401);
    }

    // Authenticate user
    $userData = authenticateUser();

    // Send initial connection success message
    echo "event: connection\n";
    echo "data: " . json_encode(['status' => 'connected']) . "\n\n";
    flush();

    // Keep connection alive
    $lastEventId = 0;
    $lastKeepAlive = time();
    $keepAliveInterval = 15; // Send keepalive every 15 seconds

    while (true) {
        // Check if client is still connected
        if (connection_aborted()) {
            error_log("Client disconnected");
            break;
        }

        // Send keepalive more frequently
        if (time() - $lastKeepAlive >= $keepAliveInterval) {
            echo ": keepalive " . date('Y-m-d H:i:s') . "\n\n";
            flush();
            $lastKeepAlive = time();
        }

        // Check for new events
        $query = "SELECT * FROM events WHERE id > ? AND processed = FALSE ORDER BY created_at ASC LIMIT 10";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $lastEventId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($event = $result->fetch_assoc()) {
            $lastEventId = max($lastEventId, $event['id']);
            echo "event: {$event['type']}\n";
            echo "data: " . json_encode($event['data']) . "\n\n";
            flush();

            // Mark as processed
            $updateStmt = $conn->prepare("UPDATE events SET processed = TRUE WHERE id = ?");
            $updateStmt->bind_param('i', $event['id']);
            $updateStmt->execute();
            $updateStmt->close();
        }
        $stmt->close();

        // Check for reminders
        $reminderEvent = checkAndSendReminders($conn);
        if ($reminderEvent) {
            echo "event: {$reminderEvent['type']}\n";
            echo "data: " . json_encode($reminderEvent['data']) . "\n\n";
            flush();
        }

        // Sleep briefly to prevent CPU overuse
        usleep(250000); // Reduced to 0.25 seconds
    }
} catch (Exception $e) {
    error_log("SSE Error: " . $e->getMessage());
    echo "event: error\n";
    echo "data: " . json_encode([
        'status' => 'Failed',
        'message' => $e->getMessage(),
        'code' => $e->getCode() ?: 500
    ]) . "\n\n";
    flush();
}

$conn->close();