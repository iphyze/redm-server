<?php

require 'vendor/autoload.php';
require_once 'includes/connection.php';
require_once 'includes/authMiddleware.php';


header('Content-Type: application/json');


try{


    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        throw new Exception("Route not found", 400);
    }


    // Check if the user is authenticated
    $userData = authenticateUser();
    $loggedInUserId = $userData['id'];
    $loggedInUserRole = $userData['role'];
    

    if($loggedInUserRole !== 'Admin' && $loggedInUserRole !== 'Super_Admin') {
        throw new Exception("Unauthorized: Only Admins can create logs", 401);
    }

    
    $data = json_decode(file_get_contents("php://input"), true);
    if (!isset($data['categoryId'], $data['name'], $data['description'], $data['createdBy'], $data['userEmail'])) {
        throw new Exception("All fields are required", 400);
    }
    
    $categoryId = intval(trim($data['categoryId']));
    $name = trim($data['name']);
    $description = trim($data['description']);
    $userEmail = trim($data['userEmail']);
    $updatedBy = $userEmail;
    $updatedAt = date('Y-m-d H:i:s');
    


    $checkId = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $checkId->bind_param("i", $categoryId);
    $checkId->execute();
    $checkIdResult = $checkId->get_result();

    if ($checkIdResult->num_rows === 0) {
        throw new Exception("Category not found", 404);
    }


    $checkStmt = $conn->prepare("SELECT * FROM categories WHERE name = ? && id != ?");
    $checkStmt->bind_param("si", $name, $categoryId);
    $checkStmt->execute();
    $checkStmtResult = $checkStmt->get_result();


    if ($checkStmtResult->num_rows > 0) {
        throw new Exception($name . " already exists as category.", 400);
    }


    
    // Insert new user
    $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ?, updatedBy = ?, updatedAt = ? WHERE id = ?");
    
    // Make sure the number of placeholders matches the number of bind parameters
    $stmt->bind_param("ssssi", $name, $description, $updatedBy, $updatedAt, $categoryId);
    
    
    if (!$stmt) {
        throw new Exception("Database error: Failed to prepare statement", 500);
        exit;
    }
    
    
    if ($stmt->execute()) {
        
        $result = $stmt->get_result();
        $data = $stmt->fetch_assoc();

            http_response_code(200);
            echo json_encode([
            "status" => "Success",
            "message" => "Category has been updated successfully!",
            "data" => [
                "id" => $data['id'],
                "name" => $data['name'],
                "description" => $data['description'],
                "createdBy" => $data['createdBy'],
                "updatedBy" => $data['updatedBy'],
                "updatedAt" => $data['updatedAt'],
            ],
        ]);
    
    } else {
        throw new Exception("Failed to create log!", 500);
    }

    $stmt->close();
    

}catch(Exception $e){
    error_log("Error: " . $e->getMessage());
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        "status" => "Failed",
        "message" => $e->getMessage()
    ]);
}

?>
