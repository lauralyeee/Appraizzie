<?php
include_once(__DIR__.'/cextrest.php');
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class UserTracker {
    private $logFile;
    private $currentUser;
    private $memberId;
    private $domain;
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
        $this->logFile = INSTANCE_SETTINGS_DIR . '/' . $this->memberId . '/user_activity_log.txt';
        
        // Create instance directory if it doesn't exist
        $instanceDir = dirname($this->logFile);
        if (!is_dir($instanceDir)) {
            mkdir($instanceDir, 0755, true);
        }
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
                <h2>FiuuPay New User Access Notification</h2>
                <p>A new user has accessed your application:</p>
                <p>User access email notification will only trigger once after app is installed; subsequent user access notifications will not be triggered.</p>
                <p>The user listed here is the user that install the app.</p>
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

    private function logError($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = sprintf("[%s] ERROR: %s\n", $timestamp, $message);
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }
}