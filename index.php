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
    '/logs/setReminder' => 'routes/logs/setReminder.php',
    '/logs/editLog' => 'routes/logs/editLog.php',
    '/clients/createClient' => 'routes/clients/createClient.php',
    '/clients/editClient' => 'routes/clients/editClient.php',
    '/logs/getAllLogs' => 'routes/logs/getAllLogs.php',
    '/categories/createCategory' => 'routes/categories/createCategory.php',
    '/categories/editCategory' => 'routes/categories/editCategory.php',
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