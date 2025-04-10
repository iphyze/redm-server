
<?php
    
    require 'vendor/autoload.php';
    require_once 'includes/connection.php';
    require_once 'includes/authMiddleware.php';
    
    header('Content-Type: application/json');
    
    try {
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

        // all fields
        // organization, representative, tel, email, projectId, projectTitle, agentId, categoryName, comment, userEmail
        // required fields
        // organization, representative, tel, email, projectId, projectTitle, agentId, categoryName, userEmail

    
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['agentId'])) {
            throw new Exception("agentId is required", 400);
        }
    
        if (!intval($data['agentId'])) {
            throw new Exception("agentId must be a number", 400);
        }
    
        if (!isset($data['organization'])) {
            throw new Exception("Organization's name is required!", 400);
        }
    
        if (!isset($data['representative'])) {
            throw new Exception("Representative's name is required!", 400);
        }
    
        if (!isset($data['tel'])) {
            throw new Exception("Phone number is required!", 400);
        }
    
        if (!isset($data['userEmail'])) {
            throw new Exception("User Email is required!", 400);
        }

        $agentId = intval(trim($data['agentId']));
        $organization = trim($data['organization']);
        $representative = trim($data['representative']);
        $tel = trim($data['tel']);
        $email = trim($data['email']);
        $projectId = intval(trim($data['projectId']));
        $projectTitle = trim($data['projectTitle']);
        $categoryId = intval(trim($data['categoryId']));
        $categoryName = trim($data['categoryName']);
        $comment = isset($data['comment']) ? trim($data['comment']) : '';
        $userEmail = trim($data['userEmail']);
        $createdBy = $userEmail;
        $updatedBy = $userEmail;

        date_default_timezone_set('UTC');
        $datetime = new DateTime('now', new DateTimeZone('Africa/Lagos'));
        $updatedAt = $datetime->format('Y-m-d H:i:s');
        
        // Check if category exists
        $checkId = $conn->prepare("SELECT * FROM contacts WHERE id = ?");
        $checkId->bind_param("i", $agentId);
        $checkId->execute();
        $checkIdResult = $checkId->get_result();
    
        if ($checkIdResult->num_rows === 0) {
            throw new Exception("Agent not found", 404);
        }
    
        // Check for duplicate name
        $checkStmt = $conn->prepare("SELECT * FROM contacts WHERE organization = ? AND projectId = ? AND id != ?");
        $checkStmt->bind_param("sii", $organization, $projectId, $agentId);
        $checkStmt->execute();
        $checkStmtResult = $checkStmt->get_result();
    
        if ($checkStmtResult->num_rows > 0) {
            throw new Exception($organization . " already exists as an agent for " . $projectTitle, 400);
        }
    
        // Update contact
        $stmt = $conn->prepare("UPDATE contacts SET organization = ?, representative = ?, 
        tel = ?, email = ?, projectId = ?, projectTitle = ?, categoryId = ?, categoryName = ?, comment = ?, updatedBy = ?, updatedAt = ? WHERE id = ?");
        $stmt->bind_param("ssssisissssi", $organization, $representative, $tel, $email, $projectId, $projectTitle, $categoryId, $categoryName, $comment, $updatedBy, $updatedAt, $agentId);
        
        if (!$stmt) {
            throw new Exception("Database error: Failed to prepare statement", 500);
        }
        
        if ($stmt->execute()) {
            // Fetch the updated data with a new query
            $selectStmt = $conn->prepare("SELECT * FROM contacts WHERE id = ?");
            $selectStmt->bind_param("i", $agentId);
            $selectStmt->execute();
            $result = $selectStmt->get_result();
            $updatedData = $result->fetch_assoc();
            
            http_response_code(200);
            echo json_encode([
                "status" => "Success",
                "message" => "Agent has been updated successfully!",
                "data" => [
                    "id" => $updatedData['id'],
                    "organization" => $updatedData['organization'],
                    "representative" => $updatedData['representative'],
                    "tel" => $updatedData['tel'],
                    "email" => $updatedData['email'],
                    "projectId" => $updatedData['projectId'],
                    "projectTitle" => $updatedData['projectTitle'],
                    "categoryId" => $updatedData['categoryId'],
                    "categoryName" => $updatedData['categoryName'],
                    "comment" => $updatedData['comment'],
                    "createdBy" => $updatedData['createdBy'],
                    "updatedBy" => $updatedData['updatedBy'],
                    "updatedAt" => $updatedData['updatedAt'],
                ],
            ]);
            
            $selectStmt->close();
        } else {
            throw new Exception("Failed to update agent!", 500);
        }
    
        $stmt->close();
        
    } catch(Exception $e) {
        error_log("Error: " . $e->getMessage());
        http_response_code($e->getCode() ?: 500);
        echo json_encode([
            "status" => "Failed",
            "message" => $e->getMessage()
        ]);
    }
?>
