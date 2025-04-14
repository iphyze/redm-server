<?php
require_once 'includes/connection.php';
require_once 'includes/authMiddleware.php';
require_once 'routes/events/reminderEvents.php';

// Verify user authentication
$userData = authenticateUser();
$loggedInUserId = $userData['id'];
$loggedInUserRole = $userData['role'];

// Set headers for SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable nginx buffering

// Function to send SSE message
function sendSSE($eventType, $data) {
    echo "event: {$eventType}\n";
    echo "data: " . json_encode($data) . "\n\n";
    ob_flush();
    flush();
}

// Keep track of last event time
$lastEventTime = isset($_GET['lastEventTime']) ? $_GET['lastEventTime'] : date('Y-m-d H:i:s');

// Main event loop
while (true) {


    $reminderEvent = checkAndSendReminders($conn);
    if ($reminderEvent) {
        if ($reminderEvent['type'] === 'reminderError') {
            error_log("Reminder error: " . json_encode($reminderEvent['data']));
        }
        sendSSE($reminderEvent['type'], $reminderEvent['data']);
    }

    // Check for new logs
    $logsQuery = $conn->prepare("
        SELECT * FROM logs 
        WHERE createdAt > ? 
        ORDER BY createdAt ASC
    ");
    $logsQuery->bind_param("s", $lastEventTime);
    $logsQuery->execute();
    $newLogs = $logsQuery->get_result()->fetch_all(MYSQLI_ASSOC);

    // Check for new responses
    $responsesQuery = $conn->prepare("
        SELECT * FROM log_responses 
        WHERE createdAt > ? 
        ORDER BY createdAt ASC
    ");
    $responsesQuery->bind_param("s", $lastEventTime);
    $responsesQuery->execute();
    $newResponses = $responsesQuery->get_result()->fetch_all(MYSQLI_ASSOC);

    // Send new logs
    foreach ($newLogs as $log) {
        sendSSE('newLog', $log);
        $lastEventTime = $log['createdAt'];
    }

    // Send new responses
    foreach ($newResponses as $response) {
        sendSSE('newResponse', $response);
        $lastEventTime = $response['createdAt'];
    }

    // Clear queries
    $logsQuery->close();
    $responsesQuery->close();

    // Prevent CPU overuse
    sleep(2);

    // Clear output buffer
    if (connection_aborted()) {
        break;
    }
}

// Close connection when client disconnects
$conn->close();