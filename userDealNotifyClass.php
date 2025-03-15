<?php
include_once(__DIR__.'/cextrest.php');
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class UserDealTracker {
    private $logFile;
    private $currentUser;
    private $memberId;
    private $domain;
    private $paymentInfo; // New property to store payment information
        // Admin email configuration
        private $adminEmails = [
            'to' => [
                'laura.lai@fusioneta.com',
                'fiuu_mm@fusioneta.com'
            ],
            'cc' => [
                'appraizzie@fusioneta.com.my',
             
            ]
        ];

    public function __construct() {
        if (!isset($_REQUEST['member_id'])) {
            throw new Exception("member_id is required");
        }
        
        
        $this->memberId = $_REQUEST['member_id'];
        $this->domain =$_REQUEST['DOMAIN'];
        CRestExt::setCurrentBitrix24($_REQUEST['member_id']); 
        $this->logFile = INSTANCE_SETTINGS_DIR . '/' . $this->memberId . '/user_deal_activity_log.txt';
        
        // Create instance directory if it doesn't exist
        $instanceDir = dirname($this->logFile);
        if (!is_dir($instanceDir)) {
            mkdir($instanceDir, 0755, true);
        }

        // Initialize payment info with default values
        $this->paymentInfo = [
            'isPaid' => false,
            'isExpired' => false,
            'daysLeft' => 30,
            'expirationDate' => date('Y-m-d', strtotime('+30 days')),
            'installDate' => date('Y-m-d')
        ];
    }

     /**
     * Set payment information for the deal
     * 
     * @param array $paymentInfo Array containing payment information
     * @return UserDealTracker $this for method chaining
     */
    public function setPaymentInfo($paymentInfo) {
       // Check if $paymentInfo is null or not an array
        if ($paymentInfo === null || !is_array($paymentInfo)) {
            // Just use default values, don't try to merge
            return $this;
        }
        
        $this->paymentInfo = array_merge($this->paymentInfo, $paymentInfo);
        return $this;
    }

    public function getCurrentUser() {
        try {
            $result = CRestExt::call('user.current', []);
             // Log the full response, even if it fails
            $this->logInfo("Response from user.current", ['response' => $result]);
            if (isset($result['result'])) {
                $this->currentUser = $result['result'];
                 // Log successful result
            
                $this->logUserActivity();
                $this->sendAdminNotification();
                return $this->currentUser;
            }
            
            throw new Exception("Failed to retrieve user data");
        } catch (Exception $e) {
            $this->logError2("Error occurred in getCurrentUser", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->logError($e->getMessage());
            return null;
        }
    }
    private function logInfo($message, $context = []) {
        // Log informational messages (e.g., using a file logger)
        $logFilePath = INSTANCE_SETTINGS_DIR . '/' . $this->memberId . '/error_user.txt';
        file_put_contents($logFilePath, date('[Y-m-d H:i:s]') . " INFO: " . $message . " " . json_encode($context) . PHP_EOL, FILE_APPEND);
    }
    

    private function logError2($message, $context = []) {
        // Log error messages (e.g., using a file logger)
        $logFilePath = INSTANCE_SETTINGS_DIR . '/' . $this->memberId . '/error_user.txt';
        file_put_contents($logFilePath, date('[Y-m-d H:i:s]') . " ERROR: " . $message . " " . json_encode($context) . PHP_EOL, FILE_APPEND);
    }

    private function hasNotificationBeenSent() {
        if (!file_exists($this->logFile)) {
            return false;
        }
        
        $logs = file_get_contents($this->logFile);
        return strpos($logs, "NOTIFICATION_SENT: {$this->currentUser['ID']}") !== false;
    }

    private function logUserActivity() {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = sprintf(
            "[%s] USER_ACCESS - ID: %s, Name: %s %s, Email: %s\n",
            $timestamp,
            $this->currentUser['ID'] ?? '',         // Default to empty string if 'ID' does not exist
            $this->currentUser['NAME'] ?? '',       // Default to empty string if 'NAME' does not exist
            $this->currentUser['LAST_NAME'] ?? '',  // Default to empty string if 'LAST_NAME' does not exist
            $this->currentUser['EMAIL'] ?? ''       // Default to empty string if 'EMAIL' does not exist
        );
    
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }

    private function sendAdminNotification() {
        // Only send notification if it hasn't been sent before
        if ($this->hasNotificationBeenSent()) {
            return;
        }

        try {
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'mail.fusioneta.com.my'; // Replace with your SMTP server
            $mail->SMTPAuth = true;
            $mail->Username = 'appraizzie@fusioneta.com.my'; // Replace with your SMTP username
            $mail->Password = 'fusionPass123!'; // Replace with your SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

           // From address
           $mail->setFrom('appraizzie@fusioneta.com.my', 'No-Reply Appraizzie');

           // Add all primary recipients
           foreach ($this->adminEmails['to'] as $email) {
               $mail->addAddress($email);
           }

           // Add all CC recipients
           foreach ($this->adminEmails['cc'] as $email) {
               $mail->addCC($email);
           }


            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Apprazzie New User Access Notification';
            $mail->Body = $this->generateAdminNotificationContent();

            $mail->send();
            
            // Log notification sent
            $timestamp = date('Y-m-d H:i:s');
            $logEntry = sprintf(
                "[%s] NOTIFICATION_SENT: %s - Email sent to admin for user %s %s\n",
                $timestamp,
                $this->currentUser['ID'],
                $this->currentUser['NAME'],
                $this->currentUser['LAST_NAME']
            );
            file_put_contents($this->logFile, $logEntry, FILE_APPEND);

              // Create Bitrix24 deal after successful email notification
            $this->createBitrixDeal();
            
        } catch (Exception $e) {
            $this->logError("Notification email failed: " . $e->getMessage());
        }
    }

    private function generateAdminNotificationContent() {
    // Safely handle missing or null values
        $registrationDate = isset($this->currentUser['DATE_REGISTER']) ? new DateTime($this->currentUser['DATE_REGISTER']) : null;
        
        return "
            <html>
            <body>
                <h2>Appraizzie New User Access Notification</h2>
                <p>A new user has accessed your application:</p>
                <p>User access email notification will only trigger once after app is installed; subsequent user access notifications will not be triggered.</p>
                <p>The user listed here is the user that installs the app.</p>
                <ul>
                    <li>Bitrix24 Domain: " . ($this->domain ?? 'N/A') . "</li>
                    <li>Bitrix24 Member ID: " . ($this->memberId ?? 'N/A') . "</li>
                    <li>User ID: " . ($this->currentUser['ID'] ?? 'N/A') . "</li>
                    <li>Name: " . ($this->currentUser['NAME'] ?? '') . " " . ($this->currentUser['LAST_NAME'] ?? '') . "</li>
                    <li>Email: " . ($this->currentUser['EMAIL'] ?? 'N/A') . "</li>
                    <li>Work Phone: " . ($this->currentUser['WORK_PHONE'] ?? 'N/A') . "</li>
                     <li>Personal Phone: " . ($this->currentUser['PERSONAL_PHONE'] ?? 'N/A') . "</li>
                     <li>Company Website: " . ($this->currentUser['WORK_WWW'] ?? 'N/A') . "</li>
                       <li>Profile Pic: " . ($this->currentUser['PERSONAL_PHOTO'] ?? 'N/A') . "</li>
                    <li>Registration Date: " . ($registrationDate ? $registrationDate->format('Y-m-d H:i:s') : 'N/A') . "</li>
                    " . (!empty($this->currentUser['WORK_POSITION']) ? "<li>Position: {$this->currentUser['WORK_POSITION']}</li>" : "") . "
                    " . (!empty($this->currentUser['UF_DEPARTMENT']) ? "<li>Department: " . implode(', ', $this->currentUser['UF_DEPARTMENT']) . "</li>" : "") . "
                </ul>
                <p>This is an automated notification.</p>
            </body>
            </html>
        ";
    }

    /**
     * Creates a deal in Bitrix24 after sending email notification
     * Uses the domain as the title and UF_CRM_1741841548111 field
     * Only creates the deal once per member_id/domain
     */

     private function createBitrixDeal() {
        // Only create deal if it hasn't been created before
        if ($this->hasDealBeenCreated()) {
            return;
        }
    
        try {
            // Step 1: Create the Contact in Bitrix24
            $contactApiUrl = 'https://fusioneta.bitrix24.com/rest/1460/dsm8ajzr894ru9w8/crm.contact.add';
            
            // Prepare contact data, skipping empty fields
            $contactData = [
                'FIELDS' => []
            ];
    
            if (!empty($this->currentUser['NAME'])) {
                $contactData['FIELDS']['NAME'] = $this->currentUser['NAME'];
            }

            if (!empty($this->currentUser['LAST_NAME'])) {
                $contactData['FIELDS']['LAST_NAME'] = $this->currentUser['LAST_NAME'];
            }
    
            if (!empty($this->currentUser['WORK_PHONE'])) {
                $contactData['FIELDS']['PHONE'] = [['VALUE' => $this->currentUser['WORK_PHONE'], 'VALUE_TYPE' => 'WORK']];
            }
    
            if (!empty($this->currentUser['EMAIL'])) {
                $contactData['FIELDS']['EMAIL'] = [['VALUE' => $this->currentUser['EMAIL'], 'VALUE_TYPE' => 'WORK']];
            }
    
            if (!empty($this->currentUser['WORK_WWW'])) {
                $contactData['FIELDS']['WEB'] = [['VALUE' => $this->currentUser['WORK_WWW'], 'VALUE_TYPE' => 'CORPORATE']];
            }
    
            if (!empty($this->currentUser['WORK_POSITION'])) {
                $contactData['FIELDS']['POST'] = $this->currentUser['WORK_POSITION'];
            }
    
            // Hardcoded fields
            $contactData['FIELDS']['SOURCE_ID'] = "UC_8VSKLO";
            $contactData['FIELDS']['ASSIGNED_BY_ID'] = 51;
    
            // Convert to JSON and send POST request
            $contactJson = json_encode($contactData);
            $ch = curl_init($contactApiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $contactJson);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($contactJson)
            ]);
    
            $contactResponse = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
    
            $contactResponseData = json_decode($contactResponse, true);
            $contactId = $contactResponseData['result'] ?? null;
    
            if (!$contactId) {
                $this->logError("Bitrix24 contact creation failed: HTTP $httpCode - $contactResponse");
                return false;
            }
    
            // Step 2: Create the Deal and Attach the Contact
            $dealTitle = "Appraizzie-" . ($this->domain ?? 'unknown');
            $dealApiUrl = 'https://fusioneta.bitrix24.com/rest/1460/dsm8ajzr894ru9w8/crm.deal.add';
    
            $dealData = [
                'FIELDS' => [
                    'TITLE' => $dealTitle,
                    'CATEGORY_ID' => 52,
                    'STAGE_ID' => 'C52:NEW',
                    'UF_CRM_1741841325804' => 2292,
                    'UF_CRM_1741841548111' => $this->domain ?? 'unknown',
                    'UF_CRM_1741841222363' => $this->memberId ?? 'unknown',
                    'UF_CRM_1741944779586' => $this->paymentInfo['isPaid'] ? 2298 : 2296,  
                    'UF_CRM_1741841248046' => $this->paymentInfo['installDate'],
                    'UF_CRM_1741944130879' => $this->paymentInfo['expirationDate'],
                    'ASSIGNED_BY_ID' => 51, //1460
                    'CONTACT_ID' => (int) $contactId // Attach the created contact
                ]
            ];
    
            $dealJson = json_encode($dealData);
            $ch = curl_init($dealApiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dealJson);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($dealJson)
            ]);
    
            $dealResponse = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
    
            $dealResponseData = json_decode($dealResponse, true);
            $dealId = $dealResponseData['result'] ?? null;
    
            if (!$dealId) {
                $this->logError("Bitrix24 deal creation failed: HTTP $httpCode - $dealResponse");
                return false;
            }
    
            // Log deal creation
            $timestamp = date('Y-m-d H:i:s');
            $logEntry = sprintf(
                "[%s] DEAL_CREATED: Domain %s - Deal created in Bitrix24 with ID %s, Linked to Contact ID %s\n",
                $timestamp,
                $this->domain,
                $dealId,
                $contactId
            );
            file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    
            // Mark deal as created with the deal ID
            $this->markDealAsCreated($dealId);
    
            return $dealId;
        } catch (Exception $e) {
            $this->logError("Bitrix24 deal creation failed: " . $e->getMessage());
            return false;
        }
    }
    
    // private function createBitrixDeal() {
    //     // Only create deal if it hasn't been created before
    //     if ($this->hasDealBeenCreated()) {
    //         return;
    //     }
        
    //     try {
    //         // Prepare the deal title - use domain with "Appraizzie-" prefix
    //         $dealTitle = "Appraizzie-" . ($this->domain ?? 'unknown');
            
    //         // Prepare the API request data
    //         $apiUrl = 'https://fusioneta.bitrix24.com/rest/1460/dsm8ajzr894ru9w8/crm.deal.add';
    //         $postData = [
    //             'FIELDS' => [
    //                 'TITLE' => $dealTitle,
    //                 'CATEGORY_ID' => 52,
    //                 'STAGE_ID' => 'C52:NEW',
    //                 'UF_CRM_1741841325804' => 2292,
    //                 'UF_CRM_1741841548111' => $this->domain ?? 'unknown',
    //                 'UF_CRM_1741841222363' => $this->memberId ?? 'unknown',
    //                 'UF_CRM_1741944779586' => $this->paymentInfo['isPaid'] ? 2298 : 2296,  
    //                 'UF_CRM_1741841248046' => $this->paymentInfo['installDate'],
    //                 'UF_CRM_1741944130879' => $this->paymentInfo['expirationDate'],
    //                 'ASSIGNED_BY_ID'=> 1460 //51
    //             ]
    //         ];
            
    //         // Convert data to JSON
    //         $jsonData = json_encode($postData);
            
    //         // Initialize cURL session
    //         $ch = curl_init($apiUrl);
            
    //         // Set cURL options
    //         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //         curl_setopt($ch, CURLOPT_POST, true);
    //         curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    //         curl_setopt($ch, CURLOPT_HTTPHEADER, [
    //             'Content-Type: application/json',
    //             'Content-Length: ' . strlen($jsonData)
    //         ]);
            
    //         // Execute cURL request
    //         $response = curl_exec($ch);
    //         $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
    //         // Close cURL session
    //         curl_close($ch);
            
    //         // Check if request was successful
    //         if ($httpCode >= 200 && $httpCode < 300) {
    //             $responseData = json_decode($response, true);
    //             $dealId = $responseData['result'] ?? 'unknown';
                
    //             // Log deal creation
    //             $timestamp = date('Y-m-d H:i:s');
    //             $logEntry = sprintf(
    //                 "[%s] DEAL_CREATED: Domain %s - Deal created in Bitrix24 with ID %s\n",
    //                 $timestamp,
    //                 $this->domain,
    //                 $dealId
    //             );
    //             file_put_contents($this->logFile, $logEntry, FILE_APPEND);
                
    //             // Mark deal as created with the deal ID
    //             $this->markDealAsCreated($dealId);
                
    //             return $dealId;
    //         } else {
    //             $this->logError("Bitrix24 deal creation failed: HTTP $httpCode - $response");
    //             return false;
    //         }
    //     } catch (Exception $e) {
    //         $this->logError("Bitrix24 deal creation failed: " . $e->getMessage());
    //         return false;
    //     }
    // }


    /**
     * Checks if a deal has already been created for this user/domain
     * @return bool True if a deal has already been created
     */
    private function hasDealBeenCreated() {
        // Use the same directory as the log file
        $instanceDir = dirname($this->logFile);
        $dealFlagFile = $instanceDir . '/deal_created.flag';
        
        // Check if the flag file exists
        return file_exists($dealFlagFile);
    }

    /**
     * Marks a deal as created to prevent duplicate creation
     * @param int|string $dealId The ID of the created deal
     */
    private function markDealAsCreated($dealId = 'unknown') {
        // Use the same directory as the log file
        $instanceDir = dirname($this->logFile);
        $dealFlagFile = $instanceDir . '/deal_created.flag';
        
        // Create the flag file with timestamp and deal ID
        $timestamp = date('Y-m-d H:i:s');
        $content = "Deal created at: $timestamp\nDomain: $this->domain\nDeal ID: $dealId\n";
        file_put_contents($dealFlagFile, $content);
    }

    private function logError($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = sprintf("[%s] ERROR: %s\n", $timestamp, $message);
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }
}