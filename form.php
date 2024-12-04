<?php

session_start();
define('INSTANCE_SETTINGS_DIR', __DIR__ . '/instance_settings');
require_once(__DIR__ . '/cextrest.php');
require_once(__DIR__ . '/BitrixSPAManager.php');
require_once(__DIR__ . '/email/EmailComponent.php');
require_once(__DIR__ . '/fpdf/fpdf.php');
require_once(__DIR__ . '/PDFGenerator.php');

$instanceId=isset($_GET['instanceId']) ? $_GET['instanceId'] : '';
$isForminit=isset($_GET['forminit']) ? $_GET['forminit'] : '';
$recordId=isset($_GET['recordId']) ? $_GET['recordId'] : '';

/////////////////////////////////////////////////
$userType = isset($_GET['user']) ? $_GET['user'] : 'reviewer';
$appraisalType = isset($_GET['type']) ? $_GET['type'] : 'final-year';
$spaid = isset($_GET['spaId']) ? $_GET['spaId'] : '';
$team = isset($_GET['team']) ? $_GET['team'] : '';
$role = isset($_GET['role']) ? $_GET['role'] : '';
$appraisalFre = isset($_GET['frequency']) ? $_GET['frequency'] : 'Annually';

// Read the JSON configuration file
$jsonConfig = file_get_contents("appraisal.json");
$config = json_decode($jsonConfig, true);


// Extract valid appraisal types from the JSON
$validAppraisalTypes = [];
if (isset($config['appraisalTypes']) && is_array($config['appraisalTypes'])) {
    $validAppraisalTypes = array_column($config['appraisalTypes'], 'id');
}

// Validate user type
$validUserTypes = ['reviewee', 'reviewer', 'partner', 'view-only'];
if (!in_array($userType, $validUserTypes)) {
    $userType = 'reviewer'; // Default to reviewer if an invalid type is provided
}
// Validate and set appraisal type
if (!in_array($appraisalType, $validAppraisalTypes)) {
    $appraisalType = $validAppraisalTypes[0]; // Default to the first valid type if an invalid type is provided
}

// Generate appraisal type options with the correct type selected
$appraisalTypeOptions = '';
foreach ($config['appraisalTypes'] as $type) {
    $selected = ($type['id'] === $appraisalType) ? 'selected' : '';
    $appraisalTypeOptions .= "<option value='{$type['id']}' {$selected}>{$type['name']}</option>";
}

$reviewee = '';


//HERE CALL USER DATA
CRestExt::setCurrentBitrix24($instanceId);

if ($isForminit!=='yes'){
   
    $entityTypeId = getEntityTypeIdFromSettings($instanceId, $spaid);

    //fetch SPARecord Form DATA
    $SPARecordData = CRestExt::call(
        'crm.item.get',
        [

            'entityTypeId' => $entityTypeId,
            'id' => $recordId
            
        ]
    );
   // echo "<script>console.log('API returned SPA data:', " . json_encode($SPARecordData['result']) . ");</script>";
}

//SPA RECORD STORED FORM DATA
$SPARecordFormData = $SPARecordData['result']['item'] ?? [];

function getEntityTypeIdFromSettings($instanceId, $spaid) {
  //  echo "<script>console.log($spaid);</script>";
    $settingsPath = INSTANCE_SETTINGS_DIR . '/' . $instanceId . '/spa_settings.json';
    
    if (!file_exists($settingsPath)) {
        throw new Exception("Settings file not found for instance: " . $instanceId);
    }
    
    $settings = json_decode(file_get_contents($settingsPath), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON in settings file");
    }

   // $spaid = (int) $spaid;
    
    // Check if 'spas' and the specified spaId exist in the settings
    if (isset($settings['spas']) && isset($settings['spas'][$spaid])) {
        return $settings['spas'][$spaid]['entityTypeId'] ?? null; // Return entityTypeId if it exists, otherwise null
    }
    
    throw new Exception("No entityTypeId found for SPA ID: " . $spaid);
}


// // Call the 'user.get.json' API method with desired parameters
// $userResult = CRestExt::call(
//     'user.get',
//     [
//         'FILTER' => [
//             'USER_TYPE' => 'employee',
//             'ACTIVE' => 'true'
//         ]
//     ]
// );

function fetchAllUsers() {
    $allUsers = [];
    $start = 0;
    
    do {
        // Call the 'user.get.json' API method with pagination
        $userResult = CRestExt::call(
            'user.get',
            [
                'FILTER' => [
                    'USER_TYPE' => 'employee',
                    'ACTIVE' => 'true'
                ],
                'start' => $start
            ]
        );
        
        // Get the current batch of users
        $users = $userResult['result'] ?? [];
        
        // Add current batch to all users array
        if (!empty($users)) {
            $allUsers = array_merge($allUsers, $users);
        }
        
        // Move to next batch
        $start += 50;
        
        // Continue if we received a full batch (indicating there might be more)
    } while (!empty($users) && count($users) === 50);
    
    return $allUsers;
}

// Use the function to get all users
$userData = fetchAllUsers();


//$userData = $userResult['result'] ?? [];

//get spa file location
$spaSettingsPath = "instance_settings/$instanceId/spa_settings.json";

if (file_exists($spaSettingsPath)) {
    $spaSettingsData = file_get_contents($spaSettingsPath);
   // echo "<script>const spaSettingsData = " . json_encode($spaSettingsData) . ";</script>";
} 

function getSpaIdFromSettingsForm($instanceId) {
    $settingsPath = INSTANCE_SETTINGS_DIR . '/' . $instanceId . '/spa_settings.json';
    
    if (!file_exists($settingsPath)) {
        throw new Exception("Settings file not found for instance: " . $instanceId);
    }
    
    $settings = json_decode(file_get_contents($settingsPath), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON in settings file");
    }
    
    // Get the first spa ID from the spas array
    if (isset($settings['spas']) && !empty($settings['spas'])) {
        // Get the first key from the spas array
        $spaIds = array_keys($settings['spas']);
        return $spaIds[0];
    }
    
    throw new Exception("No SPA ID found in settings");
}


// Turn off error display for production
ini_set('display_errors', 0);
error_reporting(E_ALL);
// Start output buffering at the very beginning
ob_start();

// // Function to send JSON response
// function sendJsonResponse($success, $message, $data = null) {
//     // Clear any previous output
//     while (ob_get_level()) {
//         ob_end_clean();
//     }
    
//     // Set JSON header
//     header('Content-Type: application/json');
    
//     // Send response
//     echo json_encode([
//         'success' => $success,
//         'message' => $message
//     ]);
//     exit;
// }

// Function to send JSON response
function sendJsonResponse($success, $message, $data = null) {  // Added optional $data parameter
    // Clear any previous output - good practice!
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set JSON header
    header('Content-Type: application/json');
    
    // Prepare response array
    $response = [
        'success' => $success,
        'message' => $message
    ];

    // Add data if provided
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    // Send response
    echo json_encode($response);
    exit;
}
// Log errors instead of displaying them
function logError($error) {
    file_put_contents(__DIR__ . '/error.log', 
        date('[Y-m-d H:i:s] ') . $error . "\n", 
        FILE_APPEND
    );
}


// Enable error reporting
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// // Initial debug logging
// file_put_contents('debug_log.txt', '=== New Request ===\n', FILE_APPEND);
// file_put_contents('debug_log.txt', 'Script accessed at: ' . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
// file_put_contents('debug_log.txt', 'REQUEST_METHOD: ' . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);
// file_put_contents('debug_log.txt', 'Script path: ' . __FILE__ . "\n", FILE_APPEND);
// file_put_contents('debug_log.txt', 'Current working directory: ' . getcwd() . "\n", FILE_APPEND);

// // Log all request headers
// $headers = getallheaders();
// file_put_contents('debug_log.txt', 'Request Headers: ' . print_r($headers, true) . "\n", FILE_APPEND);


// // Add this at the top of your form.php
// $logFile1 = 'form_debug.log';
// $logData1 = array(
//     'timestamp' => date('Y-m-d H:i:s'),
//     'method' => $_SERVER['REQUEST_METHOD'],
//     'headers' => getallheaders(),
//     'get_params' => $_GET,
//     'post_params' => $_POST
// );
// file_put_contents($logFile1, print_r($logData1, true) . "\n\n", FILE_APPEND);

    
    
    // Use php://input to capture raw POST data
    $rawPostData = file_get_contents('php://input');
    parse_str($rawPostData, $parsedData); // Parse it into an array
    
   // file_put_contents('debug_logParsed.txt', 'Parsed POST data: ' . print_r($parsedData, true) . "\n", FILE_APPEND);

    $formType = $parsedData['formType'] ?? 'not set';
    $instanceId = $parsedData['instanceId'] ?? null;

    file_put_contents('debug_log.txt', 'POST request detected\n', FILE_APPEND);
    file_put_contents('debug_log.txt', 'POST data: ' . print_r($_POST, true) . "\n", FILE_APPEND);
    file_put_contents('debug_log.txt', 'SERVER data: ' . print_r($_SERVER, true) . "\n", FILE_APPEND);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    

    
    // Test if we can read basic POST data
    $formType = $_POST['formType'] ?? 'not set';
  //  file_put_contents('debug_log.txt', 'formType: ' . $formType . "\n", FILE_APPEND);
   
   
    $formType = $_POST['formType'];
    $instanceId = $_POST['instanceId'];
    $recordId = $_POST['recordId'];
    $userType = $_POST['user'];
    $allUsersDataJson = $_POST['allUsersData'] ?? null;
    $revieweeIds = isset($_POST['REVIEWEE']) ? $_POST['REVIEWEE'] : [];
    $reviewerId = $_POST['REVIEWER'] ?? null;
    $partnerId = $_POST['PARTNER'] ?? null;
    $revieweeEmail = '';
    $reviewerEmail = '';
    $partnerEmail = '';
    $email = 'laura.lai@fusioneta.com'; // Default email

    file_put_contents('INITform_submission_log.txt', print_r($_POST), FILE_APPEND);

    if ($formType === 'formInitYes') {
        // Handle the submission for formInitYes
        
         //find email function by returned id
        if ($allUsersDataJson) {
            // Decode the JSON string back into a PHP array
            $allUsersData = json_decode($allUsersDataJson, true);
        
                // Helper function to find email and return both email and ID
            function findEmailByIdInit($userId, $allUsersData) {
                foreach ($allUsersData as $user) {
                    if ($user['ID'] == $userId) {
                        return [
                            'email' => $user['EMAIL'],
                            'id' => $user['ID']
                        ];
                    }
                }
                return null;
            }

                // Array to store all reviewee emails
            $revieweeData = [];

            // Ensure $revieweeIds is an array; if it's a single value, make it an array
            if (!is_array($revieweeIds)) {
                  // Split the comma-separated string into an array
                $revieweeIds = explode(',', $revieweeIds);
                // Trim any whitespace
                $revieweeIds = array_map('trim', $revieweeIds);
                file_put_contents('debug_flow.txt', "Processed revieweeIds: " . print_r($revieweeIds, true) . "\n", FILE_APPEND);
            }

            /// Find email for each reviewee ID
            foreach ($revieweeIds as $revieweeId) {
                $userData = findEmailByIdInit($revieweeId, $allUsersData);
                if ($userData) {
                    $revieweeData[] = $userData;
                }
            }

            // Get emails for REVIEWEE, REVIEWER, and PARTNER
            //$revieweeEmail = $revieweeId ? findEmailById($revieweeId, $allUsersData) : '';
            // $reviewerEmail = $reviewerId ? findEmailByIdInit($reviewerId, $allUsersData) : '';
            // $partnerEmail = $partnerId ? findEmailByIdInit($partnerId, $allUsersData) : '';

            // Log the collected data for debugging
            file_put_contents('email_mapping_log.txt', 
            "Reviewee Data: " . print_r($revieweeData, true) . "\n" .
            FILE_APPEND
            );

        } else {
            file_put_contents('Before_submission_log.txt', print_r('Error in email processing'), FILE_APPEND);

        }

        try {
             file_put_contents('INITform_submission_log.txt', print_r('TRY ctaching'), FILE_APPEND);
            // Prepare custom fields
            $customFields = [];
            foreach ($_POST as $key => $value) {
                if ($key !== 'spaId') {
                    $customFields[$key] = $value;
                }
            }
        file_put_contents('INITform_submission_log.txt', "Custom Fields: " . print_r($customFields, true) . "\n", FILE_APPEND);
            // Initialize email component
            $emailComponent = new EmailComponent();
            $success = true;
             //file_put_contents('INITform_submission_log.txt', ("$currentCustomFields['instanceId']"), FILE_APPEND);
            // Loop through each reviewee email
            foreach ($revieweeData as $userData) {
               
                try {
                    // Skip if email is empty
                    if (empty($userData['email'])) {
                        continue;
                    }
        
                    // Clone custom fields for this specific reviewee
                    $currentCustomFields = $customFields;
                    $currentCustomFields['REVIEWEE'] = $userData['id'];
                     // Log current custom fields before passing to email component
        file_put_contents('INITform_submission_log.txt', "Current Custom Fields: " . print_r($currentCustomFields, true) . "\n", FILE_APPEND);

        
                    // Generate email for this reviewee
                    $emailComponent->handleFormGeneration(
                        $userData['email'], 
                        $instanceId, 
                        'init', 
                        $currentCustomFields
                    );
        
                } catch (Exception $individualError) {
                    $success = false;
                    break;
                }
            }
        
            sendJsonResponse(true, 'Process completed successfully');

        } catch (Exception $e) {
            logError($e->getMessage());
            sendJsonResponse(false, 'Error processing form initialization');
        }
        
      
        
        // // Send JSON response
        // header('Content-Type: application/json');
        // echo json_encode($response);
        // exit();
        

    } elseif ($formType === 'formInitNo') {

        if ($allUsersDataJson) {
            // Decode the JSON string back into a PHP array
            $allUsersData = json_decode($allUsersDataJson, true);
        
            // Helper function to find email by user ID
            function findEmailById($userId, $allUsersData) {
                foreach ($allUsersData as $user) {
                    if ($user['ID'] == $userId) {
                        return $user['EMAIL'];
                    }
                }
                return '';
            }

            function findNameById($userId, $allUsersData) {
                foreach ($allUsersData as $user) {
                    if ($user['ID'] == $userId) {
                        return $user['NAME'] . ' ' . $user['LAST_NAME'];
                    }
                }
                return '';
            }
        
            // Get emails for REVIEWEE, REVIEWER, and PARTNER
              $revieweeIdSPA = $SPARecordFormData['assignedById'] ?? 0;
               file_put_contents(__DIR__.'/all_logs/form_submission_log.txt', 'revieweeidspa: '.$revieweeIdSPA, FILE_APPEND);
               
            $revieweeEmail = $revieweeIds ? findEmailById($revieweeIdSPA, $allUsersData) : '';
          
            $reviewerEmail = $reviewerId ? findEmailById($reviewerId, $allUsersData) : '';
            $partnerEmail = $partnerId ? findEmailById($partnerId, $allUsersData) : '';

        } else {
            file_put_contents('Before_submission_log.txt', print_r('Error in email processing'), FILE_APPEND);

        }

        // Handle the submission for formInitNo

        try {
            // $instanceId = 'af1ef9af50426847e3d7e204c8160acb'; // Use your actual instance ID
            
            $spaManager = new BitrixSPAManager($instanceId);
            
            CRestExt::setCurrentBitrix24($instanceId); 
            // Get spa ID from settings
            $spaId = getSpaIdFromSettingsForm($instanceId);
    
            $formCustomFields = [];
            foreach ($_POST as $key => $value) {
                if ($key !== 'spaId') {
                    $formCustomFields[$key] = $value;
                }
            }

            $revieweeName = findNameById($revieweeIds, $allUsersData);

           // file_put_contents(__DIR__.'/all_logs/form_submission_log.txt', print_r('REVIEWEE NAME HERE: '.$revieweeName), FILE_APPEND);
           //  file_put_contents(__DIR__.'/all_logs/form_submission_log.txt', print_r('SPA Record: '.$SPARecordFormData), FILE_APPEND);
            $formCustomFields['REVIEWEE'] = $revieweeName;
                // Log custom fields for debugging
            file_put_contents(__DIR__.'/all_logs/form_submission_log.txt', print_r($formCustomFields, true), FILE_APPEND);
            
            
  file_put_contents(__DIR__.'/all_logs/form_submission_log.txt', 'reviewee email: '.$revieweeEmail, FILE_APPEND);
            //if user Type = reviewee/reviewer/partner , diff parameter in link in email

            // Email mapping array to eliminate the initial if-else chain
            $emailMap = [
                'reviewee' => $reviewerEmail,
                'reviewer' => $partnerEmail,
                'partner' => $revieweeEmail
            ];

            // Get email from map, with fallback
            $email = $emailMap[$userType] ?? throw new Exception("Invalid user type: $userType");

            // Log the email
            file_put_contents(__DIR__.'/all_logs/form_submission_log.txt', print_r($email, true), FILE_APPEND);

            // Initialize variables
            $pdfPath = null;

            // Handle partner-specific operations
            if ($userType === 'partner') {
                // Generate PDF only for partner
                $pdfPath = generatePDFReport($formCustomFields);
                //file_put_contents(__DIR__.'/all_logs/form_submission_log.txt', "Generated PDF Path: $pdfPath\n", FILE_APPEND);
            }

            file_put_contents(__DIR__.'/all_logs/form_submission_log.txt', "Email before sending: $email\n", FILE_APPEND);

            // Update SPA record
            $updateResult = $spaManager->updateSPARecord($spaId, $recordId, $userType, $formCustomFields);

            // Initialize email components
            $emailRouter = new EmailRouter();
            $emailComponent = new EmailComponent();

            // Common parameters for email routing
            $emailParams = [
                $userType,
                $recordId,
                $instanceId,
                $spaId,
                $emailComponent,
                $email
            ];

            // Add PDF path only if it exists (partner case)
            if ($pdfPath) {
                $emailParams[] = $pdfPath;
            }

            // Single call to routeAdditionalEmails with spread operator
            $emailRouter->routeAdditionalEmails(...$emailParams);
            

            sendJsonResponse(true, 'Process completed successfully');


        } catch (Exception $e) {
            // file_put_contents(__DIR__.'/all_logs/form_submission_log.txt', "Exception: " . $e->getMessage(), FILE_APPEND);
            // echo json_encode([
            //     'success' => false,
            //     'message' => $e->getMessage()
            // ]);

           logError($e->getMessage());
            sendJsonResponse(false, 'Error processing form submission');
            }


     
        //exit(json_encode(['success' => true, 'message' => 'Processed successfully']));

    }
   
}else{
    file_put_contents('debug_log.txt', 'Not a POST request\n', FILE_APPEND);
}


?>

<!DOCTYPE html>
<html lang="en">

<!-- head until style body -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Start Performance Appraisal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
       /* Base styles */
        body { 
            background-color: white; 
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        .form-container { 
            background-color: white; 
            border-radius: 10px; 
            /*box-shadow: 0 1px 5px rgba(0,0,0,0.08);*/
            padding: 1rem;
         margin: 2rem 0.5rem; /* Added small margin on sides */
    width: calc(100% - 1rem); /* Subtract the total side margins */
    box-sizing: border-box;
        }

        .form-header { 
            background: linear-gradient(135deg, #4a6bff 0%, #2541b2 100%);
            color: white; 
            border-top-left-radius: 16px; 
            border-top-right-radius: 16px;
            padding: 1.5rem !important;
        }

        /* Section Headers */
        .section-header { 
            background: linear-gradient(135deg, #4a6bff 0%, #2541b2 100%);
            color: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .subsection-header { 
            background: #eef1ff;
            color: #2541b2;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        /* Question Cards */
        .question.card {
            border: none;
            background-color: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            transition: all 0.2s ease;
        }

        .question .card-header {
            background-color: #f8f9fa;
            border-bottom: none;
            padding: 1.25rem;
            border-radius: 12px 12px 0 0;
        }

        .question .card-body {
            background-color: #ffffff;
            padding: 1.5rem;
            border-radius: 0 0 12px 12px;
        }

        .card-title {
            color: #2541b2;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        /* Form Controls */
        /* .form-control, .form-select {
            border-radius: 8px;
            border: 2px solid #e0e4e8;
            padding: 0.75rem;
            transition: all 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #4a6bff;
            box-shadow: 0 0 0 0.25rem rgba(74, 107, 255, 0.1);
        } */

        /* Form Controls - Base styles */
        .form-control, .form-select {
            border-radius: 8px;
            border: 2px solid #e0e4e8;
            padding: 0.75rem;
            transition: all 0.2s ease;
        }

        /* Focus states */
        .form-control:focus, .form-select:focus {
            border-color: #4a6bff;
            box-shadow: 0 0 0 0.25rem rgba(74, 107, 255, 0.1);
        }
        /* Adjust label padding to create space below */
        .form-floating label {
            transform: translateY(-20px); /* Move label slightly up */
            padding-bottom: 20px; /* Add more space below the label text */
            font-size: 1.1rem; /* Optional: Adjust font size for better readability */
        }
            /* Adjust dropdown padding to align with label */
        .form-select {
            padding-top: 1.5rem; /* Push down options slightly to match the label position */
        }

        /* Required field styles */
        .form-select[required] {
            background-color: #fff; /* Ensure background is white */
            border-left: 4px solid #ff4a4a; /* Red left border for required fields */
        }

        /* Add asterisk to labels of required fields */
        .form-floating:has(select[required]) label::after {
            content: " *";
            color: #ff4a4a;
            border-color: #4a6bff;
        }

        /* Invalid state - when field is touched but empty */
        /* .form-select[required]:invalid {
            border-color: #ff4a4a;
        } */

        Invalid state hover
        .form-select[required]:invalid:hover {
            border-color: #ff3333;
        }

        /* Optional: Add a subtle shake animation when invalid field is focused */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .form-select[required]:invalid:focus {
            animation: shake 0.2s ease-in-out;
            border-color: #ff4a4a;
            box-shadow: 0 0 0 0.25rem rgba(255, 74, 74, 0.1);
        }


        /* Read-only and Disabled States */
        .form-control:read-only,
        .form-control[readonly],
        select:disabled,
        .non-editable {
            background-color: #f8f9fa !important;
            opacity: 0.8;
            cursor: not-allowed !important;
            border-color: #e0e4e8;
        }

        /* Active Select Elements */
        select:not(:disabled):not(.non-editable) {
            background-color: #ffffff !important;
            opacity: 1;
            cursor: pointer !important;
            color: #1a1f36;
        }

        /* Hover Effects */
        select:not(:disabled):not(.non-editable):hover {
            border-color: #4a6bff;
            box-shadow: 0 0 0 0.25rem rgba(74, 107, 255, 0.1);
        }

        /* Text Areas */
        textarea {
            min-height: 120px;
        }

        textarea:read-only,
        textarea[readonly] {
            resize: none;
        }

        /* Question Card Hover Effect */
        .question.card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        /* Button Styles */
        .btn-primary { 
            background: linear-gradient(135deg, #4a6bff 0%, #2541b2 100%);
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-primary:hover { 
            background: linear-gradient(135deg, #5c7aff 0%, #2f4ccc 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(74, 107, 255, 0.2);
        }

        /* Form Section Styles */
        .form-section {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
        }

        .section-label {
            color: #566a7f;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 1.5rem;
        }

        /* Labels */
        label {
            color: #566a7f;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1050;
    }

    .modal-content {
        background: white;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        max-width: 500px;
        width: 90%;
        text-align: center;
        position: relative;
    }

    .modal-close {
        position: absolute;
        top: 1rem;
        right: 1rem;
        border: none;
        background: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #666;
    }

    .modal-icon {
        background-color: #ecfdf5;
        color: #059669;
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        font-size: 1.5rem;
    }

    .modal-title {
        color: #111827;
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .modal-description {
        color: #6b7280;
        margin-bottom: 1.5rem;
    }

    .modal-button {
        background-color: #059669;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        font-size: 1rem;
    }

    .modal-button:hover {
        background-color: #047857;
    }

    .fade-in {
        animation: fadeIn 0.2s ease-in;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .custom-multiselect {
        height: auto !important;
          max-height: 300px; /* Dropdown will grow up to 300px and then scroll */
         overflow-y: auto; /* Enables scrolling within the dropdown */
    }

      /* .custom-multiselect {
        position: relative; 
        height: auto !important;
        min-height: 38px;
    } */

  
    
    .custom-multiselect option {
        padding: 12px 12px;
        cursor: pointer;
        line-height: 2;
    }

    .custom-multiselect option:checked {
        background-color: #e2e8f0;
        color: #1a202c;
    }

    .custom-multiselect option:hover {
        background-color: #f7fafc;
    }

    .selected-options {
        margin-top: 10px; /* Add space between dropdown and selected options */
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }

    .selected-option {
        display: inline-block;
        background-color: #e9ecef;
        padding: 5px 10px;
        margin: 2px;
        border-radius: 3px;
        font-size: 0.9em;
    }

    .remove-option {
        margin-left: 5px;
        cursor: pointer;
        color: #dc3545;
    }

    .custom-multiselect {
        width: 100%;
    }

    .apf-question-container {
        width: 100%; /* Make container full width */
    padding: 0 1.5rem; /* Add some padding on the sides */
    }

    .apf-section {
        width: 100%;
    background: #fff;
    border-radius: 12px;
    margin-bottom: 2rem;
    padding: 1.5rem;
    transition: all 0.3s ease;
    border: 1px solid #eaecef;
    }

    .apf-section:hover {
        box-shadow: 0 7px 20px rgba(74, 107, 255, 0.7);
    }

    .apf-component {
        position: relative;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        background: #fafbfc;
        border-radius: 8px;
    }

    .apf-component-header {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #f0f2f5;
    }

    .apf-component-title {
        font-size: 1rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6b7280;
        font-weight: 600;
        margin: 0;
    }

    .apf-input-group {
        margin-bottom: 1.5rem;
    }

    .apf-label {
        display: block;
        font-size: 0.875rem;
        color: #374151;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }

    .apf-select {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1.5px solid #e5e7eb;
        border-radius: 6px;
        background-color: #fff;
        color: #1f2937;
        font-size: 0.875rem;
        transition: all 0.2s ease;
        appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 1rem center;
        background-size: 1rem;
    }

    .apf-select:not(:disabled):hover {
        border-color: #9ca3af;
    }

    .apf-select:not(:disabled):focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .apf-textarea {
        width: 100%;
        min-height: 100px;
        padding: 0.75rem 1rem;
        border: 1.5px solid #e5e7eb;
        border-radius: 6px;
        background-color: #fff;
        color: #1f2937;
        font-size: 0.875rem;
        transition: all 0.2s ease;
        resize: vertical;
    }

    .apf-textarea:not(:disabled):hover {
        border-color: #9ca3af;
    }

    .apf-textarea:not(:disabled):focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }
    /* Default full width */
    .apf-component-wrapper {
        width: 100%;
    }

    /* Read-only states */
    .apf-select:disabled,
    .apf-textarea:disabled,
    .apf-select.apf-readonly,
    .apf-textarea.apf-readonly {
        background-color: #f9fafb;
        border-color: #e5e7eb;
        color: #6b7280;
        cursor: not-allowed;
        opacity: 0.75;
    }

    /* Required field styles */
    .apf-required .apf-label::after {
        content: "*";
        color: #ef4444;
        margin-left: 0.25rem;
    }

    .apf-grid {
        display: grid;
    gap: 1.5rem;
    width: 100%;
    }

   /* For two column layout when needed */
    @media (min-width: 768px) {
        .apf-grid.apf-grid-split {
            grid-template-columns: repeat(2, 1fr);
        }
        
        /* For components that should remain full width even in split layout */
        .apf-component-wrapper.apf-full-width {
            grid-column: 1 / -1;
        }
    }

    /* Animation for invalid fields */
    @keyframes apf-shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-4px); }
        75% { transform: translateX(4px); }
    }

    .apf-select:invalid:not(:focus):not(:placeholder-shown) {
        border-color: #ef4444;
        animation: apf-shake 0.2s ease-in-out;
    }
    </style>
</head>
<body>

<input type="hidden" id="userType" value="<?php echo $userType; ?>">
<input type="hidden" id="appraisalFre" value="<?php echo $appraisalFre; ?>">

<!-- FORM HTML INIT AND NORMAL FORM -->

    <div class="form-container">
       
        <!-- here is the select to send email part -->
        <?php if ($isForminit=='yes'): ?>
            <div class="form-header">
                    <h4 class="text-center m-0">
                        <i class="fas fa-clipboard-check me-2"></i>
                        New Performance Appraisal Assignment
                    </h4>
             </div>
             <form id="appraisalForm" data-form-type="formInitYes" method="POST" class="mt-4">
                    <div class="form-section">
                        <div class="section-label">Performance Review Details</div>
                        <div class="row g-5 ">
                            <div class="col-md-7">
                                <div class="form-floating">    
                                    <select class="form-select custom-multiselect" id="REVIEWEE_select" multiple required>
                                            <option value="">Choose a Reviewee</option>
                                        </select>
                                    <input type="hidden" id="REVIEWEE" name="REVIEWEE">
                                    <label for="REVIEWEE">Reviewee</label>
                                </div>
                                  <div class="selected-options" id="selectedOptionsDisplay"></div>
                            </div>
                               
                            <!-- <div class="col-md-3">
                                <div class="form-floating">                        
                                        <select class="form-select" id="REVIEWER_select" >
                                            <option value="">Choose a Reviewer</option>
                                        </select>
                                        <input type="hidden" id="REVIEWER" name="REVIEWER">
                                    <label for="REVIEWER">Direct Supervisor</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-floating">
                                        <select class="form-select" id="PARTNER_select" >
                                            <option value="">Choose a Partner</option>
                                        </select>
                                        <input type="hidden" id="PARTNER" name="PARTNER">
                                    <label for="PARTNER">Supervising Partner</label>
                                </div>
                            </div>
                             -->
                            <div class="col-md-4">
                                <div class="form-floating">
                                        <select class="form-select" id="YEAR" required >
                                            <option value="">Select Review Year</option>
                                            <?php
                                            $currentYear = date('Y');
                                            for ($i = $currentYear; $i >= $currentYear - 5; $i--) {
                                                echo "<option value=\"$i\">$i</option>";
                                            }
                                            ?>
                                        </select>
                                    <label for="YEAR">Review Year</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="section-label">Employee Position Information</div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <select class="form-select" id="APPRAISAL_TYPE" name="APPRAISAL_TYPE" required>
                                        <option value="">Appraisal Type</option>
                                        <option value="Final Year Performance Appraisal">Final Year Performance Appraisal</option>
                                    </select>
                                    <label for="APPRAISAL_TYPE">Appraisal Type</label>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <select class="form-select" id="TEAM" name="TEAM" required>
                                        <option value="">Select Team</option>
                                        <option value="Audit Team">Audit Team</option>
                                        <option value="Customer Success Team">Customer Success Team</option>
                                        <option value="Accounting & Payroll Team">Accounting & Payroll Team</option>
                                        <option value="Company Secretarial and Licensing Team">Company Secretarial and Licensing Team</option>
                                        <option value="Tax Service Team">Tax Service Team</option>
                                        <option value="Marketing Team">Marketing Team</option>
                                        <option value="IT & Operation Team">IT & Operation Team</option>
                                    </select>
                                    <label for="TEAM">Team</label>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <select class="form-select" id="ROLE" name="ROLE" required>
                                        <option value="">Select Role</option>
                                        <option value="Audit Staff">Audit Staff</option>
                                        <option value="Associate 1">Associate 1</option>
                                        <option value="Associate 2">Associate 2</option>
                                        <option value="Senior 1">Senior 1</option>
                                        <option value="Senior 2">Senior 2</option>
                                        <option value="Assistant Manager">Assistant Manager</option>
                                        <option value="Manager">Manager</option>
                                    </select>
                                    <label for="ROLE">Role</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="dynamicForm"></div>
                    
                    <div class="text-center mt-4">
                        <!-- <button class="btn btn-outline-secondary" onclick="history.back()">
                            <i class="fas fa-arrow-left me-2"></i>
                            Back
                        </button> -->
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>
                            Initialize Review Process
                        </button>
                    </div>
                </form>
             <!-- if parameter says its a user submission form then populate form -->
        <?php else: ?>
        <div class="form-header p-3 mb-4">
            <h4 class="text-center m-0">Employee Performance Appraisal</h4>
        </div>
        <form id="appraisalForm" data-form-type="formInitNo" method="POST">
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="REVIEWEE" data-id="" value="<?php echo htmlspecialchars($reviewee); ?>" readonly>
                        <label for="REVIEWEE">Reviewee</label>
                    </div>
            </div>
            <div class="col-md-3">
                <div class="d-flex align-items-start">
                    <div class="form-floating flex-grow-1">
                        <?php if ($userType === 'reviewee'): ?>
                            <select class="form-select" id="REVIEWER_select" required>
                                <option value="">Select Reviewer</option>
                            </select>
                            <input type="hidden" name="reviewer_id" id="REVIEWER">
                        <?php else: ?>
                            <input type="text" class="form-control" id="REVIEWER" data-id="" readonly>  
                        <?php endif; ?>
                        <label for="REVIEWER">Direct Supervisor</label>
                    </div>
                    <i class="bi bi-info-circle-fill ms-2 mt-2" 
                    data-bs-toggle="tooltip" 
                    data-bs-placement="right" 
                    title="Your Direct Supervisor is the person who directly oversees your work and will provide primary feedback on your performance."></i>
                </div>
            </div>

            <div class="col-md-3">
                <div class="d-flex align-items-start">
                    <div class="form-floating flex-grow-1">
                        <?php if ($userType === 'reviewee'): ?>
                            <select class="form-select" id="PARTNER_select" required>
                                <option value="">Select Partner</option>
                            </select>
                            <input type="hidden" name="partner_id" id="PARTNER">
                        <?php else: ?>
                            <input type="text" class="form-control" id="PARTNER" data-id="" readonly>                           
                        <?php endif; ?>
                        <label for="PARTNER">Supervising Partner</label>
                    </div>
                    <i class="bi bi-info-circle-fill ms-2 mt-2" 
                    data-bs-toggle="tooltip" 
                    data-bs-placement="right" 
                    title="Supervising Partner is a more senior line manager who provides additional oversight and strategic direction for your work. In simple words, it could also be the 'final reviewer'."></i>
                </div>
            </div>
            <!-- <div class='col-md-1'>
            <i class="fas fa-info-circle ms-1" 
                        data-bs-toggle="tooltip" 
                        data-bs-placement="top" 
                        title="The Supervising Partner is a senior member who provides additional oversight and strategic direction for your work, it could also be the 'final approver'."></i>
                    </div> -->

                <div class="col-md-3">
                    <div class="form-floating">
                            <input type="text" class="form-control" id="YEAR" disabled>
                        <label for="YEAR">Year</label>
                    </div>
                </div>
            
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type='text' class="form-control" id="APPRAISAL_TYPE" name="APPRAISAL_TYPE" disabled>
                            <!-- <option value="">Select Appraisal Type</option> -->

                        <label for="APPRAISAL_TYPE">Appraisal Type</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                        <input type='text'class="form-control" id="TEAM" name="TEAM" disabled>
                        <label for="TEAM">Team</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating">
                    <input type='text' class="form-control" id="ROLE" name="ROLE" disabled>
                        <label for="ROLE">Role</label>
                    </div>
                </div>
            </div>
            
            <div id="dynamicForm"></div>
            
            <div class="text-center mt-4">
             <?php if ($userType !== 'view-only'): ?>
                <button type="submit" class="btn btn-primary px-5">Submit</button>
                <?php endif; ?>
            </div>
        </form>
        <?php endif; ?>
    </div>


<script>
var config = <?php echo $jsonConfig; ?>;
var userType = '<?php echo $userType; ?>';
var instanceId = '<?php echo $instanceId; ?>';
var isForminit = '<?php echo $isForminit; ?>';

console.log('this is trymember id: ',instanceId);
const appraisalFre = document.getElementById('appraisalFre').value;

//document.addEventListener('DOMContentLoaded', function()

document.addEventListener('DOMContentLoaded', function() {
    

    let config = <?php echo json_encode($config); ?>;
    let urlAppraisalType = <?php echo json_encode($appraisalType); ?>;
    let urlTeam = <?php echo json_encode($team); ?>;
    let urlRole = <?php echo json_encode($role); ?>;

    const appraisalTypeSelect = document.getElementById('APPRAISAL_TYPE');
    const teamSelect = document.getElementById('TEAM');
    const roleSelect = document.getElementById('ROLE');
    const dynamicForm = document.getElementById('dynamicForm');

    const spaid = <?php echo json_encode($spaid); ?>;
    const allUsers = <?php echo json_encode($userData); ?>; //user retrieve from btrix 

    const SPARecordFormData = <?php echo json_encode($SPARecordFormData); ?>; //crm.item.get SPA record data
    const SPARecordFormData2 = <?php echo json_encode($SPARecordFormData); ?>;
    console.log("Retrieved user data:", allUsers); // Log data to console
    var userType = document.getElementById('userType').value;


   // JavaScript function to populate multiple dropdowns for USERSSS
   function populateUserDropdowns(dropdownIds) {
        console.log("Attempting to populate user fields...");
        try {
            if (!allUsers || allUsers.length === 0) {
                console.error("No user data available to populate fields.");
                return;
            }

            // Sort users alphabetically by NAME
            allUsers.sort((a, b) => (a.NAME || '').localeCompare(b.NAME || ''));

            dropdownIds.forEach(dropdownId => {
                // Try both the select and direct input ID
                const selectId = `${dropdownId}_select`;
                const select = document.getElementById(selectId);
                const input = document.getElementById(dropdownId);
                
                console.log(`Looking for elements - Select ID: ${selectId}, Input ID: ${dropdownId}`);
                
                // Handle dropdown case
                if (select && select.tagName === 'SELECT') {
                    select.disabled = false;
                    select.innerHTML = '<option value="">Select User</option>';

                    // Populate the dropdown with user options
                    allUsers.forEach(user => {
                        const option = document.createElement('option');
                        option.value = user.ID;
                        option.text = `${user.NAME} ${user.LAST_NAME}`;
                        select.appendChild(option);
                    });
                }
                // Handle textbox case
                else if (input && input.tagName === 'INPUT') {
                    // Get the user ID from data-id attribute
                    const userId = input.getAttribute('data-id');
                    if (userId) {
                        // Find the corresponding user
                        const user = allUsers.find(u => u.ID === userId);
                        if (user) {
                            // Set the input value to the user's full name
                            input.value = `${user.NAME} ${user.LAST_NAME}`;
                        }
                    }
                } else {
                    console.log(`Neither select nor input found for ${dropdownId}`);
                }
            });
        } catch (error) {
            console.error("Error in populateUserDropdowns:", error);
        }
    }

    //TOOL tip ui

        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });


    //Audit team -> audit staff dropdown
    const teamDropdown = document.getElementById('TEAM');
    const roleDropdown = document.getElementById('ROLE');

    teamDropdown.addEventListener('change', function() {
        const selectedTeam = this.value;
        roleDropdown.innerHTML = '<option value="">Select Role</option>';
        
        if (selectedTeam === 'Audit Team') {
            const roles = ['Audit Staff'];
            roles.forEach(role => {
                const option = document.createElement('option');
                option.value = role;
                option.textContent = role;
                roleDropdown.appendChild(option);
            });
        } else {
            const roles = ['Associate 1', 'Associate 2', 'Senior 1', 'Senior 2', 
                        'Assistant Manager', 'Manager'];
            roles.forEach(role => {
                const option = document.createElement('option');
                option.value = role;
                option.textContent = role;
                roleDropdown.appendChild(option);
            });
        }
        roleDropdown.value = '';
    });


    //populate the Appraisal type dropdown
    //config is json data
    function populateDropdowns() {
        if (urlAppraisalType) {
            appraisalTypeSelect.value = urlAppraisalType;
            var selectedType = config.appraisalTypes.find(t => t.id === urlAppraisalType);
            
            if (selectedType) {
                selectedType.teams.forEach(function(team) {
                    var option = new Option(team.name, team.id);
                    teamSelect.add(option);
                });

                if (urlTeam) {
                    teamSelect.value = urlTeam;
                    var selectedTeam = selectedType.teams.find(team => team.id === urlTeam);
                    
                    if (selectedTeam) {
                        selectedTeam.roles.forEach(function(role) {
                            var option = new Option(role.name, role.id);
                            roleSelect.add(option);
                        });

                        if (urlRole) {
                            roleSelect.value = urlRole;
                           
                           
                        }
                    }
                }
            }
        }
    }


    if (isForminit !== 'yes'){
        generateForm();
    }

    function generateForm() {
        // var selectedType = appraisalTypeSelect.value;
        // var selectedTeam = teamSelect.value;
        // var selectedRole = roleSelect.value;
        dynamicForm.innerHTML = '';

        const formattedData = {};
        for (const key in SPARecordFormData2) {
            
            
            // Remove prefix (e.g., 'ufCrm50') and convert the rest to uppercase
            const formattedKey = key.replace(/^ufCrm\d+/, '').toUpperCase();
            formattedData[formattedKey] = SPARecordFormData2[key];
        }
        
        console.log(formattedData['APPRAISALTYPE']);
        console.log(formattedData['TEAM']);
        console.log(formattedData['ROLE']);
        var selectedType = formattedData['APPRAISALTYPE'];
        var selectedTeam = formattedData['TEAM'];
        var selectedRole = formattedData['ROLE'];

 
        if (selectedRole) {
            var sections = config.appraisalTypes.find(t => t.id === selectedType)
                .teams.find(team => team.id === selectedTeam)
                .roles.find(role => role.id === selectedRole).sections;

            sections.forEach(function(section, sectionId) {
                var sectionHtml = `
                    <div class="section mb-4">
                        <div class="section-header p-2 mb-3">
                            <h5 class="m-0">${section.name}</h5>
                        </div>
                        ${section.description ? `<p>${section.description}</p>` : ''}
                        ${section.details ? `<ul>${section.details.map(detail => `<li>${detail}</li>`).join('')}</ul>` : ''}
                `;

                section.questions.forEach(function(question, index) {
                    sectionHtml += `
                        <div class="subsection-header p-2 mb-3">
                            <h6 class="m-0">${question.text}</h6>
                        </div>
                        ${question.description ? `<p>${question.description}</p>` : ''}
                        ${generateQuestion(question, sectionId)}
                    `;
                });

                sectionHtml += `</div>`;
                dynamicForm.innerHTML += sectionHtml;
            });
        }
    }


    function generateQuestion(question, sectionId) {
    const id = question.id;
    let html = '';

    switch (question.type) {
        case 'complex-rating-comment':
        case 'complex-comment':
            html = `
               
                    <div class="apf-section">
                        <div class="apf-grid">
                            ${userType === 'reviewee' ? 
                                `<div class="apf-component-wrapper">
                                    ${generateComponentCard(question, id, 'reviewee')}
                                </div>` :
                                `<div class="apf-component-wrapper">
                                    ${generateComponentCard(question, id, 'reviewee')}
                                </div>
                                <div class="apf-component-wrapper">
                                    ${generateComponentCard(question, id, 'reviewer')}
                                </div>`
                            }
                        </div>
                        <div class="apf-component-wrapper">
                            ${generateComponentCard(question, id, 'partner')}
                        </div>
                    </div>
               `;
            break;

        case 'rating-comment':
            const isEditable = isComponentEditable('reviewee');
            const isVisible = isComponentVisible('reviewee');

            if (isVisible) {
                html = `
                  
                        <div class="apf-section">
                            <p class="apf-component-description">${question.description}</p>
                            <div class="apf-input-group ${isEditable ? 'apf-required' : ''}">
                                <label class="apf-label" for="${id}-rating">Rating</label>
                                <select 
                                    name="${id}-rating" 
                                    id="${id}-rating"
                                    class="apf-select"
                                    ${!isEditable ? 'disabled' : ''}
                                    ${isEditable ? 'required' : ''}>
                                    <option value="">Select Rating</option>
                                    <option value="1">1 - Not Adequate</option>
                                    <option value="2">2 - Below Expectation</option>
                                    <option value="3">3 - Meets Expectation</option>
                                    <option value="4">4 - Exceeds Expectation</option>
                                    <option value="5">5 - Distinguished Performance</option>
                                </select>
                            </div>
                            <div class="apf-input-group">
                                <label class="apf-label" for="${id}-comment">Comment</label>
                                <textarea 
                                    name="${id}-comment" 
                                    id="${id}-comment"
                                    class="apf-textarea"
                                    placeholder="Add your comment here"
                                    ${!isEditable ? 'readonly' : ''}
                                ></textarea>
                            </div>
                        </div>
                  `;
            }
            break;

        case 'textarea':
            const textareaEditable = isComponentEditable('reviewee');
            const textareaVisible = isComponentVisible('reviewee');

            if (textareaVisible) {
                html = `
                   
                        <div class="apf-section">
                            <p class="apf-component-description">${question.description}</p>
                            <div class="apf-input-group">
                                <label class="apf-label" for="${id}">Response</label>
                                <textarea 
                                    name="${id}" 
                                    id="${id}"
                                    class="apf-textarea"
                                    ${!textareaEditable ? 'readonly' : ''}
                                ></textarea>
                            </div>
                        </div>
                `;
            }
            break;

        default:
            html = `<div class="apf-alert">Unknown question type</div>`;
            break;
    }

    return html;
}

function generateComponentCard(question, id, componentType) {
    const component = question.components.find(c => c.type === componentType);
    if (!component || !isComponentVisible(componentType)) return '';

  //  const isEditable = isComponentEditable(componentType);

    let html = `
        <div class="apf-component">
            <div class="apf-component-header">
                <h3 class="apf-component-title">${componentType}</h3>
            </div>`;

    if (component.hasRating && component.ratingId) {
        const isRatingEditable = isFieldEditable(componentType, 'rating');
        html += `
            <div class="apf-input-group ${isRatingEditable ? 'apf-required' : ''}">
                <label class="apf-label" for="${component.ratingId}">Rating</label>
                <select 
                    name="${component.ratingId}" 
                    id="${component.ratingId}"
                    class="apf-select ${!isRatingEditable ? 'apf-readonly' : ''}"
                    ${!isRatingEditable ? 'disabled' : ''}
                    ${isRatingEditable ? 'required' : ''}>
                    <option value="">Select Rating</option>
                    <option value="1">1 - Not Adequate</option>
                    <option value="2">2 - Below Expectation</option>
                    <option value="3">3 - Meets Expectation</option>
                    <option value="4">4 - Exceeds Expectation</option>
                    <option value="5">5 - Distinguished Performance</option>
                </select>
            </div>`;
    }

    if (component.hasComment && component.commentId) {
        const isCommentEditable = isFieldEditable(componentType, 'comment');
        html += `
            <div class="apf-input-group">
                <label class="apf-label" for="${component.commentId}">Comment</label>
                <textarea 
                    name="${component.commentId}" 
                    id="${component.commentId}"
                    class="apf-textarea ${!isCommentEditable  ? 'apf-readonly' : ''}"
                    placeholder="Add your comment"
                    ${!isCommentEditable ? 'readonly' : ''}
                ></textarea>
            </div>`;
    }

    html += `</div>`;
    return html;
}


    function isFieldEditable(componentType, fieldType) {
        switch (userType) {
            case 'reviewee':
                return componentType === 'reviewee';
            case 'reviewer':
                return componentType === 'reviewer';
            case 'partner':
                if (componentType === 'partner') return true;
                if (componentType === 'reviewer') {
                    return fieldType === 'rating'; // Only allow rating edits for reviewer component
                }
                return false;
            case 'view-only':
                return false;
            default:
                return false;
        }
    }


    function isComponentEditable(componentType) {
        switch (userType) {
            case 'reviewee':
                return componentType === 'reviewee';
            case 'reviewer':
                return componentType === 'reviewer';
            case 'partner':
                return componentType === 'partner' ||   (componentType === 'reviewer' && isRatingField); // We'll handle this new param
            case 'view-only':
                return false; // Nothing is editable in view-only mode
            default:
                return false;
        }
    }

    function isComponentVisible(componentType) {
        switch (userType) {
            case 'reviewee':
                return componentType === 'reviewee';
            case 'reviewer':
                return componentType === 'reviewee' || componentType === 'reviewer';
            case 'partner':
            case 'view-only':
                return true; // All components are visible
            default:
                return false;
        }
    }

    if (isForminit === 'yes') {
        //INIT REVIEWEE drop down
        const selectRevieweeInit = document.getElementById('REVIEWEE_select');
        const selectedOptionsDisplay = document.getElementById('selectedOptionsDisplay');

        if (!selectRevieweeInit || !selectedOptionsDisplay) {
            console.error('Required elements not found');
            return;
        }

        function updateSelectedOptions() {
            // Clear current display
            selectedOptionsDisplay.innerHTML = '';
            
            // Get all selected options
            const selectedOptions = Array.from(selectRevieweeInit.selectedOptions);
            
            // Create display elements for each selected option
            selectedOptions.forEach(option => {
                if (option.value) {  // Skip empty value options
                    const optionElement = document.createElement('span');
                    optionElement.className = 'selected-option';
                    optionElement.innerHTML = `
                        ${option.text}
                        <span class="remove-option" data-value="${option.value}">×</span>
                    `;
                    selectedOptionsDisplay.appendChild(optionElement);
                }
            });
        }

        // Handle click on select options
        selectRevieweeInit.addEventListener('click', function(e) {
            if (e.target.tagName === 'OPTION') {
                e.preventDefault();
                const option = e.target;
                option.selected = !option.selected;
                updateSelectedOptions();
            }
        });

        // Prevent default browser behavior for multiple select
        selectRevieweeInit.addEventListener('mousedown', function(e) {
            e.preventDefault();
        });

        // Handle click on remove buttons
        selectedOptionsDisplay.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-option')) {
                const valueToRemove = e.target.getAttribute('data-value');
                const optionToUnselect = Array.from(selectRevieweeInit.options)
                    .find(option => option.value === valueToRemove);
                
                if (optionToUnselect) {
                    optionToUnselect.selected = false;
                    updateSelectedOptions();
                }
            }
        });
    }


    //retrived SPA Record and populate into elements
 
    console.log(SPARecordFormData);
    function getAllUppercaseElementIds() {
    const elements = document.querySelectorAll('*[id]');
    const upperCaseIds = [];

    elements.forEach(element => {
            // Remove underscores and convert to uppercase
            const formattedId = element.id.replace(/_/g, '').toUpperCase();
            upperCaseIds.push({ originalId: element.id, formattedId });
        });
        console.log("Formatted ID Mapping:", upperCaseIds);
        return upperCaseIds;
    }

    function updateSPARecordForm() {
        const allIds = getAllUppercaseElementIds();
        const formattedIdsMap = allIds.reduce((acc, item) => {
            acc[item.formattedId] = item.originalId; // Map formatted ID to the original ID
            return acc;
        }, {});

        console.log("Formatted IDs Map:", formattedIdsMap);

        let matchedElements = 0;
        let unmatchedElements = [];

        for (const key in SPARecordFormData) {
            
            
            // Remove prefix (e.g., 'ufCrm50') and convert the rest to uppercase
            const formattedKey = key.replace(/^ufCrm_?\d*_*/, '').replace(/_/g, '').toUpperCase();


            // Find the original ID in allIds that matches the formatted key
            const originalId = formattedIdsMap[formattedKey];

            if (originalId) {
                const element = document.getElementById(originalId);
                if (element) {
                    console.log(`Matched: ${formattedKey} -> ${originalId}`);
                    matchedElements++;

                
                    switch (element.tagName.toLowerCase()) {
                        case 'textarea':
                        case 'input':
                            element.value = SPARecordFormData[key];
                            break;
                        case 'select':
                            element.value = SPARecordFormData[key];
                            break;
                        default:
                            element.textContent = SPARecordFormData[key];
                    }
                }
            } else {
                console.log(`No match found for: ${formattedKey}`);
                unmatchedElements.push(formattedKey);
            }
        }

        console.log(`Total matched elements: ${matchedElements}`);
        console.log(`Unmatched elements:`, unmatchedElements);
    }

    
    // Modify transformUserIdtoDropdown function to handle the new IDs
    // Modify populateUserDropdowns to handle both dropdowns and textboxes
    function transformUserIdtoDropdown() {
        const dataArray1 = SPARecordFormData;
        const dataArray2 = allUsers;

        function findUserById(userId) {
            const user = dataArray2.find(user => user.ID == userId);
            return user ? { id: userId, name: `${user.NAME} ${user.LAST_NAME}` } : { id: userId, name: '' };
        }

        const revieweePattern = /^ufCrm\d+Reviewee$/;
        const reviewerPattern = /^ufCrm\d+Reviewer$/;
        const partnerPattern = /^ufCrm\d+Partner$/;

        let revieweeId, reviewerId, partnerId;

        for (const key in dataArray1) {
            if (revieweePattern.test(key)) {
                revieweeId = dataArray1[key];
            } else if (reviewerPattern.test(key)) {
                reviewerId = dataArray1[key];
            } else if (partnerPattern.test(key)) {
                partnerId = dataArray1[key];
            }
        }

        if (!revieweeId) revieweeId = dataArray1.assignedById;

        // Handle reviewee field
        const revieweeElement = document.getElementById('REVIEWEE');
        if (revieweeId && revieweeElement) {
            const reviewee = findUserById(revieweeId);
            revieweeElement.value = reviewee.name;
            revieweeElement.setAttribute('data-id', reviewee.id);
        }

        // Handle reviewer field
        const reviewerSelect = document.getElementById('REVIEWER_select');
        const reviewerInput = document.getElementById('REVIEWER');
        if (reviewerId) {
            const reviewer = findUserById(reviewerId);
            if (reviewerSelect) {
                reviewerSelect.value = reviewerId;
                // Update hidden input with ID
                if (reviewerInput) {
                    reviewerInput.value = reviewerId;
                    reviewerInput.setAttribute('data-id', reviewerId);
                }
            } else if (reviewerInput) {
                // If it's a textbox, show the name
                reviewerInput.value = reviewer.name;
                reviewerInput.setAttribute('data-id', reviewer.id);
            }
        }

        // Handle partner field
        const partnerSelect = document.getElementById('PARTNER_select');
        const partnerInput = document.getElementById('PARTNER');
        if (partnerId) {
            const partner = findUserById(partnerId);
            if (partnerSelect) {
                partnerSelect.value = partnerId;
                // Update hidden input with ID
                if (partnerInput) {
                    partnerInput.value = partnerId;
                    partnerInput.setAttribute('data-id', partnerId);
                }
            } else if (partnerInput) {
                // If it's a textbox, show the name
                partnerInput.value = partner.name;
                partnerInput.setAttribute('data-id', partner.id);
            }
        }
    }

   
   // Add change event listeners to dropdowns
    function addDropdownListeners() {
        const dropdowns = ['REVIEWEE','REVIEWER', 'PARTNER'];
        dropdowns.forEach(id => {
            const selectId = `${id}_select`;
            const select = document.getElementById(selectId);
            const hiddenInput = document.getElementById(id);
            
            if (select && select.tagName === 'SELECT') {
                select.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const selectedId = this.value;
                    
                    // Update the hidden input with the selected ID
                    if (hiddenInput) {
                        hiddenInput.value = selectedId;
                        hiddenInput.setAttribute('data-id', selectedId);
                    }
                });
            }
        });
    }

    
    // Initialize everything in the correct order
    // if (isForminit !== 'yes') {
    //     updateSPARecordForm();
    //     populateUserDropdowns(['REVIEWEE','REVIEWER', 'PARTNER']);
    //     transformUserIdtoDropdown();
    //     addDropdownListeners();
    // }

    // Initialize everything in the correct order
    if (isForminit === 'yes') {
        // For initialization form
        populateUserDropdowns(['REVIEWEE', 'REVIEWER', 'PARTNER']);
        addDropdownListeners();
    } else {
        // For pre-populated form
        updateSPARecordForm();
        populateUserDropdowns(['REVIEWEE', 'REVIEWER', 'PARTNER']);
        transformUserIdtoDropdown();
        addDropdownListeners();
    }

//Not working code
    document.getElementById('appraisalForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Enhanced function to get the correct ID value based on element type
        const getFieldValue = (fieldId) => {
            // First try to get the select element (for dropdown case)
            const selectElement = document.getElementById(`${fieldId}_select`);
            // Get the regular/hidden input element
            const inputElement = document.getElementById(fieldId);
            
            if (isForminit === 'yes') {
                // For initialization form, use select value directly
                if (selectElement) {
                   // return selectElement.value;
                    // Get all selected options and map their values
                    const selectedOptions = Array.from(selectElement.selectedOptions);
                    return selectedOptions.map(option => option.value);
                }
            } else {
                // For pre-populated form, use existing logic
                if (selectElement && selectElement.tagName === 'SELECT') {
                    return selectElement.value;
                } else if (inputElement) {
                    return inputElement.getAttribute('data-id') || inputElement.value || '';
                }
            }
            return '';
        };

        const formType = this.getAttribute('data-form-type');
        const submitBtn = this.querySelector('button[type="submit"]');

        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';


        //Modal for submission Ui alert
         // Create showMessage function
        const showMessage = (type, isSuccess = true) => {
            const messages = {
                formInitYes: {
                    title: "Performance Review Successfully Initiated",
                    description: `
                        <p>The employee appraisal form has been created and an email notification has been sent to the reviewee. </p>
                        <p> Click "Okay" to submit another appraisal form or close the window if you're done. </p>
                    `,
                    icon: '✉️' // Email icon
                },
                formInitNo: {
                    title: "Performance Review Successfully Submitted",
                    description: `
                        <p>Thank you for completing the form.</p>
                        <p>Your submission has been recorded and the reviewee or following supervisor will be notified.</p>
                        <p>You may close this window now.</p>
                    `,
                    icon: '✓' // Checkmark icon
                },
                error: {
                    title: "Error",
                    description: `
                        <p>An error occurred while processing your request.</p>
                        <p>Please try again or contact support if the problem persists.</p>
                    `,
                    icon: '⚠️' // Warning icon
                }
            };

            const message = messages[type];
            
            // Create modal HTML
            const modalHTML = `
                <div class="modal-overlay fade-in">
                    <div class="modal-content">
                        <button class="modal-close">&times;</button>
                        <div class="modal-icon">${message.icon}</div>
                        <h2 class="modal-title">${message.title}</h2>
                        <div class="modal-description">${message.description}</div>
                        <button class="modal-button">Okay</button>
                    </div>
                </div>
            `;

            // Add modal to page
            document.body.insertAdjacentHTML('beforeend', modalHTML);

            // Get modal elements
            const modal = document.querySelector('.modal-overlay');
            const closeBtn = modal.querySelector('.modal-close');
            const actionBtn = modal.querySelector('.modal-button');

            // Handle close actions
            const closeModal = () => {
                modal.remove();
                if (isSuccess) {
                    document.getElementById('appraisalForm').reset();
                    // Uncomment the next line if you want to close the window
                    // window.close();
                }
            };

            closeBtn.addEventListener('click', closeModal);
            actionBtn.addEventListener('click', closeModal);
        };


        // Collect form data
        let formData = new FormData();
        
        // Add the basic form fields
       // formData.append('instanceId', '<?php echo $instanceId; ?>');
        formData.append('recordId', '<?php echo $recordId; ?>');
        formData.append('userType', '<?php echo $userType; ?>');
        formData.append('formType', formType);

       
        // Add the three required fields with their IDs
        const revieweeIds = getFieldValue('REVIEWEE');
        const reviewerId = getFieldValue('REVIEWER');
        const partnerId = getFieldValue('PARTNER');


        if (isForminit === 'yes'){ 
           
            // Option 2: If your backend expects an array
            revieweeIds.forEach(id => {
                formData.append('REVIEWEE[]', id);
            });
        
        }
         formData.append('REVIEWEE', revieweeIds);
         formData.append('REVIEWER', reviewerId);
            formData.append('PARTNER', partnerId);

            // Log the values being sent (for debugging)
            console.log('Submitting with values:', {
                REVIEWEE: revieweeIds,
                REVIEWER: reviewerId,
                PARTNER: partnerId
            });
        // Add allUsersData if needed
        formData.append('allUsersData', JSON.stringify(allUsers));

        // Handle other form inputs
        const inputs = document.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            // Skip the user selection fields as they're handled above
            if (!['REVIEWEE', 'REVIEWER', 'PARTNER', 'REVIEWEE_select', 'REVIEWER_select', 'PARTNER_select'].includes(input.id)) {
                formData.append(input.id, input.value.trim());
            }
        });

       // const urlInit = "https://fusioneta.com.my/massmarket/employee_app/form.php?user=init&forminit=yes&instanceId=cecf95ffb76e174815ac9d44300b2dea";
       
// JavaScript code

  const currentUrl = window.location.href;
    const urlParams = new URLSearchParams(window.location.search);
    //const isForminit = urlParams.get('isForminit');
    
    // Define different target URLs
    // const targetUrls = {
    //     default: "https://helpdesk.fusioneta.com.my/appraizzie/form.php",
    //     alternative: "https://helpdesk.fusioneta.com.my/appraizzie/form.php"
    // };
    const targetUrl = "https://helpdesk.fusioneta.com.my/appraizzie/form.php";
    // Choose target URL based on isForminit condition
   // const targetUrl = (isForminit !== 'yes') ? currentUrl : targetUrls.default;
    
    // Create an object with all current URL parameters
    const currentParams = {};
    for (const [key, value] of urlParams.entries()) {
        currentParams[key] = value;
    }
    
    // Add these parameters to formData
    for (const [key, value] of Object.entries(currentParams)) {
        formData.append(`${key}`, value); // Prefix with 'url_' to distinguish from form data
    }


        // Send to PHP handler
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.error('Server returned non-JSON response:', text);
                    throw new Error('Server returned non-JSON response');
                });
            }
            return response.json();
        })
        .then(data => {
            if(data.success) {
               // alert('Record created and updated successfully!');
                document.getElementById('appraisalForm').reset();
                showMessage(formType, true);
            } else {
                //alert('Error: ' + (data.message || 'Unknown error occurred'));
                showMessage('error', false);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('error', false);
            //alert('Error processing form. Please check the console for details.');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    });

  
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
