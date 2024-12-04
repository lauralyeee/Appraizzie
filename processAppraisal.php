<?php
// File paths for saving data
header("Access-Control-Allow-Origin: https://fusioneta.com.my"); // Allow same origin
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); // Allow POST
header("Access-Control-Allow-Headers: Content-Type"); // Allow specific headers
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$getFilePath = 'get_data.txt';
$postFilePath = 'post_data.txt';

// Determine request method
$requestMethod = $_SERVER['REQUEST_METHOD'];

if ($requestMethod === 'GET') {
    // Retrieve data from GET request
    $data = $_GET;

    // Check if data exists
    if (!empty($data)) {
        // Format and save GET data to a file
        $formattedData = "[" . date('Y-m-d H:i:s') . "] GET Data: " . json_encode($data, JSON_PRETTY_PRINT) . PHP_EOL;
        file_put_contents($getFilePath, $formattedData, FILE_APPEND);

        // Respond to the client
        echo json_encode([
            'success' => true,
            'message' => 'GET data received and stored successfully',
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No GET data received',
        ]);
    }
} elseif ($requestMethod === 'POST') {
    // Retrieve data from POST request
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    // Check if data exists
    if (!empty($data)) {
        // Format and save POST data to a file
        $formattedData = "[" . date('Y-m-d H:i:s') . "] POST Data: " . json_encode($data, JSON_PRETTY_PRINT) . PHP_EOL;
        file_put_contents($postFilePath, $formattedData, FILE_APPEND);

        // Respond to the client
        echo json_encode([
            'success' => true,
            'message' => 'POST data received and stored successfully',
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No POST data received',
        ]);
    }
} else {
    // Handle unsupported request methods
    echo json_encode([
        'success' => false,
        'message' => 'Unsupported request method',
    ]);
}
?>
