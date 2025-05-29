<?php
    
    require 'vendor/autoload.php';
    require_once 'includes/connection.php';
    require_once 'includes/authMiddleware.php';
    
    
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            throw new Exception("Route not found", 400);
        }
    
        // Check if the user is authenticated
        $userData = authenticateUser();
        $loggedInUserId = $userData['id'];
        $loggedInUserRole = $userData['role'];
        
        if($loggedInUserRole !== 'Admin' && $loggedInUserRole !== 'Super_Admin') {
            throw new Exception("Unauthorized: Only Admins can create logs", 401);
        }

        // Get and decode the category name parameter
        $categoryName = urldecode($_GET['params']);

        $check = $conn->prepare("SELECT * FROM contacts WHERE categoryName = ?");

        if(!$check){
            throw new Exception("Failed to prepare statement", 400);
        }

        $check->bind_param('s', $categoryName);
        $check->execute();
        $checkResult = $check->get_result();
        $numResult = $checkResult->num_rows;

        if($numResult === 0){
            // Return a proper 404 response instead of 500 for "not found" scenarios
            http_response_code(404);
            echo json_encode([
                "status" => "Failed",
                "message" => "No agents found for " . $categoryName
            ]);
            exit;
        }
        
        $contacts = $checkResult->fetch_all(MYSQLI_ASSOC);
        
        http_response_code(200);
        echo json_encode([
            "status" => "Success",
            "message" => "Contacts have been fetched successfully!",
            "data" => $contacts
        ]);
    
        $check->close();
        
    } catch(Exception $e) {
        error_log("Error: " . $e->getMessage());
        $code = $e->getCode();
        // Ensure we're using a valid HTTP status code
        $httpCode = ($code >= 100 && $code < 600) ? $code : 500;
        http_response_code($httpCode);
        echo json_encode([
            "status" => "Failed",
            "message" => $e->getMessage()
        ]);
    }
?>