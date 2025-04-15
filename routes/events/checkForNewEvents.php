<?php
function checkForNewEvents($conn, $lastEventTime) {
    $events = [];

    // Get events from events table
    $eventQuery = $conn->prepare("
        SELECT * FROM events 
        WHERE created_at > ? AND processed = FALSE
        ORDER BY created_at ASC
    ");
    
    $eventQuery->bind_param("s", $lastEventTime);
    $eventQuery->execute();
    $newEvents = $eventQuery->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($newEvents as $event) {
        $events[] = [
            'type' => $event['type'],
            'data' => json_decode($event['data'], true)
        ];

        // Mark event as processed
        $updateStmt = $conn->prepare("UPDATE events SET processed = TRUE WHERE id = ?");
        $updateStmt->bind_param("i", $event['id']);
        $updateStmt->execute();
        $updateStmt->close();
    }

    $eventQuery->close();
    return $events;
}
?>