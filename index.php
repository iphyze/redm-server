<?php

require_once __DIR__ . '/vendor/autoload.php';

// Set CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 86400");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

include_once('includes/connection.php');


// Normalize request URI
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = '/redm-server/api';
$relativePath = str_replace($basePath, '', $requestUri);



// $uploadPath = '/utils/imageUploads/mahjong-uploads/';

// Check if the request is for an uploaded image
// if (strpos($relativePath, '/utils/imageUploads/mahjong-uploads/') === 0) {
//     // Extract the file name from the request
//     $filename = basename($relativePath);
//     $filePath = $uploadPath . $filename;

//     // Check if the file exists
//     if (file_exists($filePath)) {
//         // Determine MIME type
//         $mimeType = mime_content_type($filePath);
//         header("Content-Type: $mimeType");
//         readfile($filePath); // Output the image
//         exit;
//     } else {
//         http_response_code(404);
//         echo json_encode(["message" => "Image not found!"]);
//         exit;
//     }
// }


$routes = [
    '/' => function () {
        echo json_encode(["message" => "Welcome to REDM API 😊"]);
    },
    '/welcome' => 'routes/welcome.php',
    '/auth/login' => 'routes/auth/login.php',
    '/auth/register' => 'routes/auth/register.php',
    '/logs/createLog' => 'routes/logs/createLog.php',
    '/logs/createLogResponse' => 'routes/logs/createLogResponse.php',
    '/logs/setReminder' => 'routes/logs/setReminder.php',
    '/logs/editLog' => 'routes/logs/editLog.php',
    '/logs/editResponseLog' => 'routes/logs/editResponseLog.php',
    '/logs/deleteLog' => 'routes/logs/deleteLog.php',
    '/logs/deleteResponse' => 'routes/logs/deleteResponse.php',
    '/logs/restoreLog' => 'routes/logs/restoreLog.php',
    '/events/logs' => 'routes/events/logEvents.php',
    '/websocket/handler' => 'routes/websocket/handler.php',
    '/clients/createClient' => 'routes/clients/createClient.php',
    '/clients/editClient' => 'routes/clients/editClient.php',
    '/clients/getAllClients' => 'routes/clients/getAllClients.php',
    '/clients/deleteClient' => 'routes/clients/deleteClient.php',
    '/clients/restoreClient' => 'routes/clients/restoreClient.php',
    '/logs/getAllLogs' => 'routes/logs/getAllLogs.php',
    '/logs/getAllLogResponses' => 'routes/logs/getAllLogResponses.php',
    '/categories/createCategory' => 'routes/categories/createCategory.php',
    '/categories/editCategory' => 'routes/categories/editCategory.php',
    '/categories/getAllCategory' => 'routes/categories/getAllCategory.php',
    '/categories/deleteCategory' => 'routes/categories/deleteCategory.php',
    '/projects/createProject' => 'routes/projects/createProject.php',
    '/projects/editProject' => 'routes/projects/editProject.php',
    '/projects/getAllProject' => 'routes/projects/getAllProject.php',
    '/projects/deleteProject' => 'routes/projects/deleteProject.php',
    '/contacts/createContact' => 'routes/contacts/createContact.php',
    '/contacts/editContact' => 'routes/contacts/editContact.php',
    '/contacts/getAllContacts' => 'routes/contacts/getAllContacts.php',
    '/contacts/deleteContact' => 'routes/contacts/deleteContact.php',
    '/contacts/restoreContact' => 'routes/contacts/restoreContact.php',
];


if (array_key_exists($relativePath, $routes)) {
    if (is_callable($routes[$relativePath])) {
        $routes[$relativePath](); // Execute function
    } else {
        include_once($routes[$relativePath]);
    }
    exit;
}

$dynamicRoutes = [
    '/logs/getSingleClientLog/(.+)' => 'routes/logs/getSingleClientLog.php',
    '/contacts/contactsByCategory/(.+)' => 'routes/contacts/contactsByCategory.php',
    '/clients/getSingleClient/(.+)' => 'routes/clients/getSingleClient.php',
    '/projects/getSingleProject/(.+)' => 'routes/projects/getSingleProject.php',
    '/contacts/getSingleContact/(.+)' => 'routes/contacts/getSingleContact.php',
    '/logs/getSingleLogResponses/(.+)' => 'routes/logs/getSingleLogResponses.php',
];


foreach ($dynamicRoutes as $pattern => $file) {
    if (preg_match('#^' . $pattern . '$#', $relativePath, $matches)) {
        $params = explode('/', $matches[1]);

        // If there's only one parameter, store it as a string, else store as an array
        $_GET['params'] = count($params) === 1 ? $params[0] : $params;
        include_once($file);
        exit;
    }
}

http_response_code(404);
echo json_encode(["message" => "Page not found!"]);
exit;

// Close connection
mysqli_close($conn);

?>