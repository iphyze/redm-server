<?php
require_once 'includes/connection.php';
require_once 'includes/authMiddleware.php';
require_once 'routes/events/reminderEvents.php';
require_once 'routes/events/checkForNewEvents.php';

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
    
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
    flush();
    
    // Log event for debugging
    error_log("SSE Event sent - Type: {$eventType}, Data: " . json_encode($data));
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

    // Check for new logs and responses
    $newEvents = checkForNewEvents($conn, $lastEventTime);
    foreach ($newEvents as $event) {
        sendSSE($event['type'], $event['data']);
        if (isset($event['data']['createdAt'])) {
            $lastEventTime = $event['data']['createdAt'];
        }
    }

    // Prevent CPU overuse
    sleep(2);

    // Clear output buffer
    if (connection_aborted()) {
        break;
    }
}

// Close connection when client disconnects
$conn->close();

?>