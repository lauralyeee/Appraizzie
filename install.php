<?php
include_once(__DIR__.'/cextrest.php');

$install_result = CRestExt::installApp();
//$installationComplete = $result['install'] ?? false;


//Get email
// CRestExt::setCurrentBitrix24($_REQUEST['member_id']); 
// $resultUser = CRest::call(
//     'user.current',
//     []
// );

 echo "<script>console.log('USER CURRENT DATA:', " . json_encode($resultUser) . ");</script>";

// Define email parameters
$to = "fiuu_mm@fusioneta.com"; // Replace with your email
$subject = "Appraizzie Installation Notification";

// Construct the email message with HTML formatting
$message = "<html><body>";
$message .= "<p><strong>A new installation attempt has been made for the Apprazzie Performance Appraisal App!</strong></p>";
$message .= "<p><strong>Domain:</strong> " . $_REQUEST['DOMAIN'] . "<br>";
$message .= "<strong>Member ID:</strong> " . $_REQUEST['member_id'] . "</p>";

// Add user information from API response
// if ($resultUser['result']) {
//     $message .= "<p><strong>User Information:</strong></p>";
//     $message .= "<table border='1' cellpadding='5' cellspacing='0'>";
//     $message .= "<tr><th>Field</th><th>Value</th></tr>";
    
//     // Common user fields to display
//     $userFields = [
//         'ID' => 'User ID',
//         'EMAIL' => 'Email',
//         'NAME' => 'First Name',
//         'LAST_NAME' => 'Last Name',
//         'PERSONAL_MOBILE' => 'Mobile',
//         'WORK_POSITION' => 'Position',
//         'PERSONAL_PROFESSION' => 'Profession'
//     ];

//     foreach ($userFields as $field => $label) {
//         if (isset($resultUser['result'][$field])) {
//             $message .= "<tr><td><strong>" . $label . "</strong></td><td>" . 
//                       htmlspecialchars($resultUser['result'][$field]) . "</td></tr>";
//         }
//     }
//     $message .= "</table>";
// }

// Add a paragraph with all the request details in a table
$message .= "<p><strong>Additional installation details:</strong></p>";
$message .= "<table border='1' cellpadding='5' cellspacing='0'>";
$message .= "<tr><th>Key</th><th>Value</th></tr>";
foreach ($_REQUEST as $key => $value) {
    $message .= "<tr><td><strong>" . ucfirst($key) . "</strong></td><td>" . htmlspecialchars($value) . "</td></tr>";
}
$message .= "</table>";

// Add raw API response for debugging (optional)
// $message .= "<p><strong>Raw API Response:</strong></p>";
// $message .= "<pre>" . htmlspecialchars(print_r($resultUser, true)) . "</pre>";

$message .= "</body></html>";

// Email headers
$headers  = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8" . "\r\n";
$headers .= "From: fiuu_mm@fusioneta.com\r\n"; // Replace with your desired sender email

// Send email notification
mail($to, $subject, $message, $headers);


//install to helpdesk
$memberId = $_REQUEST['member_id']; // Replace with the actual member ID
$localFile = __DIR__ . "/settings/{$memberId}.json";
// Check if the file exists
if (!file_exists($localFile)) {
    die("File not found: $localFile");
}

$uploadUrl = 'https://helpdesk.fusioneta.com.my/appraizzie/settings/upload.php'; 

// Initialize cURL session
$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $uploadUrl,
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS => [
        'file' => new CURLFile($localFile) // Attach the file
    ],
    CURLOPT_HTTPHEADER => [
        'Content-Type: multipart/form-data' // Set the content type
    ],
]);

// Execute the cURL request
$response = curl_exec($ch);

// Check for errors
if (curl_errno($ch)) {
    echo "cURL error: " . curl_error($ch);
} else {
    echo "Response from server: " . $response;
}

// Close cURL session
curl_close($ch);

?>
<?php if($install_result['rest_only'] === false):?>
<head>
	<script src="//api.bitrix24.com/api/v1/"></script>
	<?php if($install_result['install'] == true):?>
	<script>
		BX24.init(function(){
			BX24.installFinish();
		});
	</script>
	<?php endif;?>
</head>
<body>
<?php if($install_result['install'] == true):?>
<!--echo "<script>console.log('USER CURRENT DATA:', " . json_encode($resultUser) . ");</script>";-->
    installation has been finished
<?php else:?>
    installation error
<?php endif;?>
</body>
<?php endif; ?>