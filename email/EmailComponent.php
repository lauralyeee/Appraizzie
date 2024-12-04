<?php

// Include PHPMailer files manually
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../BitrixSPAManager.php';
require_once __DIR__ . '/../cextrest.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function logMessageE($message) {
    $timestamp = date('Y-m-d H:i:s');
    $filePath = __DIR__ . '/app_log.txt';
    if (file_put_contents($filePath, "[$timestamp] $message\n", FILE_APPEND) === false) {
        error_log("Failed to write to log file: $filePath");
    }
}
$logoPath = __DIR__ . '/../company_logo.png';  // Logo is in the same directory as this PHP file
$base64Logo = base64_encode(file_get_contents($logoPath));

class EmailTemplates {

    

    public static function getEmailContent($userType) {

        global $base64Logo;
        
        $templates = [
            'reviewee' => [
                'subject' => 'Employee Self-Assessment Form',
                'html' => <<<HTML
                <!DOCTYPE html>
                <html>
                <head>
                    <style>
                       /* Reset styles */
                       body, div, p, h1, h2, h3, ul, li {
                            margin: 0;
                            padding: 0;
                        }
                        
                        body {
                            font-family: Arial, sans-serif;
                            line-height: 1.6;
                            color: #333333;
                            background-color: #f5f5f5;
                        }
                        
                        /* Container styles */
                        .container {
                            max-width: 600px;
                            margin: 0 auto;
                            padding: 20px;
                            background-color: #ffffff;
                        }
                        
                        /* Header styles */
                        .header {
                            background-color: #1a73e8;
                            padding: 24px;
                            text-align: center;
                            border-radius: 8px 8px 0 0;
                        }
                        
                        .header h2 {
                            color: #ffffff;
                            font-size: 24px;
                            margin: 0;
                        }
                        
                        /* Content styles */
                        .content {
                            padding: 32px 24px;
                            background-color: #ffffff;
                            border-left: 1px solid #e0e0e0;
                            border-right: 1px solid #e0e0e0;
                        }
                        
                        .content p {
                            margin-bottom: 16px;
                            color: #333333;
                        }
                        
                        /* Button styles */
                        .button {
                            display: inline-block;
                            padding: 12px 24px;
                            background-color: #1a73e8;
                            color: #ffffff !important;
                            text-decoration: none;
                            border-radius: 4px;
                            font-weight: bold;
                            margin: 16px 0;
                            transition: background-color 0.3s ease;
                        }
                        
                        .button:hover {
                            background-color: #1557b0;
                        }
                        
                        /* List styles */
                        ul {
                            margin: 16px 0;
                            padding-left: 24px;
                        }
                        
                        li {
                            margin-bottom: 8px;
                            color: #555555;
                        }
                        
                        /* Important notes section */
                        .important-notes {
                            background-color: #f8f9fa;
                            padding: 16px;
                            border-radius: 4px;
                            margin: 24px 0;
                            border-left: 4px solid #1a73e8;
                        }
                        
                        .important-notes h3 {
                            color: #1a73e8;
                            margin-bottom: 12px;
                        }
                        
                        /* Form URL styles */
                        .form-url {
                            background-color: #f8f9fa;
                            padding: 12px;
                            border-radius: 4px;
                            word-break: break-all;
                            font-family: monospace;
                            font-size: 14px;
                            margin: 16px 0;
                            border: 1px solid #e0e0e0;
                        }
                         /* New logo styles */
                        .footer-logo {
                            margin-bottom: 16px;
                            text-align: center;
                        }
                        
                        .footer-logo img {
                            max-width: 150px;
                            height: auto;
                        }
                        
                      /* Updated footer styles */
                            .footer {
                                padding: 24px;
                                text-align: center;
                                background-color: #f8f9fa;
                                border-radius: 0 0 8px 8px;
                                border: 1px solid #e0e0e0;
                            }
                            
                            .footer p {
                                color: #666666;
                                font-size: 14px;
                                margin-bottom: 8px;
                            }
                        
                         /* Responsive styles */
                            @media only screen and (max-width: 600px) {
                                .container {
                                    padding: 10px;
                                }
                                
                                .footer-logo img {
                                    max-width: 120px;
                                }
                            }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h2>Employee Self-Assessment Form</h2>
                        </div>
                        <div class="content">
                            <p>Dear Employee,</p>
                            <p>It's time for your performance self-assessment. Please complete the form by following the link below.</p>
                            <p style="text-align: center;">
                                <a href="{formUrl}" class="button">Access Self-Assessment Form</a>
                            </p>
                            <p>If the button doesn't work, you can copy and paste this link into your browser:</p>
                            <p style="word-break: break-all;">{formUrl}</p>
                            <p><strong>Important Notes:</strong></p>
                            <ul>
                                <li>This link is unique to you and should not be shared</li>
                                <li>Please complete your self-assessment before the deadline</li>
                                <li>Take time to reflect on your achievements and areas for improvement</li>
                            </ul>
                        </div>
                        <div class="footer">
                            <div class="footer-logo">
                            <img src="data:image/png;base64,{$base64Logo}" alt="FusionETA Logo" />
                            </div>
                            <p>This is an automated message. Please do not reply to this email.</p>
                            <p>&copy; 2024 FusionETA. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
                HTML,
                'text' => <<<TEXT
                Employee Self-Assessment Form

                Dear Employee,

                It's time for your performance self-assessment. Please complete the form by following the link below.

                Access your self-assessment form here:
                {formUrl}

                Important Notes:
                - This link is unique to you and should not be shared
                - Please complete your self-assessment before the deadline
                - Take time to reflect on your achievements and areas for improvement

                This is an automated message. Please do not reply to this email.
                TEXT
            ],
            'reviewer' => [
                'subject' => 'Employee Performance Review Request',
                'html' => <<<HTML
                <!DOCTYPE html>
                <html>
                <head>
                    <style>
                         /* Reset styles */
                         body, div, p, h1, h2, h3, ul, li {
                            margin: 0;
                            padding: 0;
                        }
                        
                        body {
                            font-family: Arial, sans-serif;
                            line-height: 1.6;
                            color: #333333;
                            background-color: #f5f5f5;
                        }
                        
                        /* Container styles */
                        .container {
                            max-width: 600px;
                            margin: 0 auto;
                            padding: 20px;
                            background-color: #ffffff;
                        }
                        
                        /* Header styles */
                        .header {
                            background-color: #1a73e8;
                            padding: 24px;
                            text-align: center;
                            border-radius: 8px 8px 0 0;
                        }
                        
                        .header h2 {
                            color: #ffffff;
                            font-size: 24px;
                            margin: 0;
                        }
                        
                        /* Content styles */
                        .content {
                            padding: 32px 24px;
                            background-color: #ffffff;
                            border-left: 1px solid #e0e0e0;
                            border-right: 1px solid #e0e0e0;
                        }
                        
                        .content p {
                            margin-bottom: 16px;
                            color: #333333;
                        }
                        
                        /* Button styles */
                        .button {
                            display: inline-block;
                            padding: 12px 24px;
                            background-color: #1a73e8;
                            color: #ffffff !important;
                            text-decoration: none;
                            border-radius: 4px;
                            font-weight: bold;
                            margin: 16px 0;
                            transition: background-color 0.3s ease;
                        }
                        
                        .button:hover {
                            background-color: #1557b0;
                        }
                        
                        /* List styles */
                        ul {
                            margin: 16px 0;
                            padding-left: 24px;
                        }
                        
                        li {
                            margin-bottom: 8px;
                            color: #555555;
                        }
                        
                        /* Important notes section */
                        .important-notes {
                            background-color: #f8f9fa;
                            padding: 16px;
                            border-radius: 4px;
                            margin: 24px 0;
                            border-left: 4px solid #1a73e8;
                        }
                        
                        .important-notes h3 {
                            color: #1a73e8;
                            margin-bottom: 12px;
                        }
                        
                        /* Form URL styles */
                        .form-url {
                            background-color: #f8f9fa;
                            padding: 12px;
                            border-radius: 4px;
                            word-break: break-all;
                            font-family: monospace;
                            font-size: 14px;
                            margin: 16px 0;
                            border: 1px solid #e0e0e0;
                        }
                        
                        /* New logo styles */
                        .footer-logo {
                            margin-bottom: 16px;
                            text-align: center;
                        }
                        
                        .footer-logo img {
                            max-width: 150px;
                            height: auto;
                        }
                        
                      /* Updated footer styles */
                            .footer {
                                padding: 24px;
                                text-align: center;
                                background-color: #f8f9fa;
                                border-radius: 0 0 8px 8px;
                                border: 1px solid #e0e0e0;
                            }
                            
                            .footer p {
                                color: #666666;
                                font-size: 14px;
                                margin-bottom: 8px;
                            }
                        
                         /* Responsive styles */
                            @media only screen and (max-width: 600px) {
                                .container {
                                    padding: 10px;
                                }
                                
                                .footer-logo img {
                                    max-width: 120px;
                                }
                            }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h2>Employee Performance Review</h2>
                        </div>
                        <div class="content">
                            <p>Dear Reviewer,</p>
                            <p>An employee has submitted their self-assessment and you have been assigned to review their performance.</p>
                          
                            <p>Please click the button below to access the performance review form:</p>
                            <p style="text-align: center;">
                                <a href="{formUrl}" class="button">Access Performance Review Form</a>
                            </p>
                            <p>If the button doesn't work, you can copy and paste this link into your browser:</p>
                            <p style="word-break: break-all;">{formUrl}</p>
                            <p><strong>Important Notes:</strong></p>
                            <ul>
                                <li>This link is unique to you and should not be shared</li>
                                <li>Please complete the review at your earliest convenience</li>
                                <li>Ensure to provide comprehensive feedback</li>
                            </ul>
                        </div>
                        <div class="footer">
                         <div class="footer-logo">
                                <img src="data:image/png;base64,{$base64Logo}" alt="FusionETA Logo" />
                            </div>
                            <p>This is an automated message. Please do not reply to this email.</p>
                            <p>&copy; 2024 FusionETA. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
                HTML,
                'text' => <<<TEXT
                Employee Performance Review

                Dear Reviewer,

                An employee has submitted their self-assessment and you have been assigned to review their performance.


                Please access the review form using the following link:
                {formUrl}

                Important Notes:
                - This link is unique to you and should not be shared
                - Please complete the review at your earliest convenience
                - Ensure to provide comprehensive feedback

                This is an automated message. Please do not reply to this email.
                TEXT
            ],
            'partner' => [
                'subject' => 'Performance Review Final Approval',
                'html' => <<<HTML
                <!DOCTYPE html>
                <html>
                <head>
                    <style>
                        /* Reset styles */
                        body, div, p, h1, h2, h3, ul, li {
                            margin: 0;
                            padding: 0;
                        }
                        
                        body {
                            font-family: Arial, sans-serif;
                            line-height: 1.6;
                            color: #333333;
                            background-color: #f5f5f5;
                        }
                        
                        /* Container styles */
                        .container {
                            max-width: 600px;
                            margin: 0 auto;
                            padding: 20px;
                            background-color: #ffffff;
                        }
                        
                        /* Header styles */
                        .header {
                            background-color: #1a73e8;
                            padding: 24px;
                            text-align: center;
                            border-radius: 8px 8px 0 0;
                        }
                        
                        .header h2 {
                            color: #ffffff;
                            font-size: 24px;
                            margin: 0;
                        }
                        
                        /* Content styles */
                        .content {
                            padding: 32px 24px;
                            background-color: #ffffff;
                            border-left: 1px solid #e0e0e0;
                            border-right: 1px solid #e0e0e0;
                        }
                        
                        .content p {
                            margin-bottom: 16px;
                            color: #333333;
                        }
                        
                        /* Button styles */
                        .button {
                            display: inline-block;
                            padding: 12px 24px;
                            background-color: #1a73e8;
                            color: #ffffff !important;
                            text-decoration: none;
                            border-radius: 4px;
                            font-weight: bold;
                            margin: 16px 0;
                            transition: background-color 0.3s ease;
                        }
                        
                        .button:hover {
                            background-color: #1557b0;
                        }
                        
                        /* List styles */
                        ul {
                            margin: 16px 0;
                            padding-left: 24px;
                        }
                        
                        li {
                            margin-bottom: 8px;
                            color: #555555;
                        }
                        
                        /* Important notes section */
                        .important-notes {
                            background-color: #f8f9fa;
                            padding: 16px;
                            border-radius: 4px;
                            margin: 24px 0;
                            border-left: 4px solid #1a73e8;
                        }
                        
                        .important-notes h3 {
                            color: #1a73e8;
                            margin-bottom: 12px;
                        }
                        
                        /* Form URL styles */
                        .form-url {
                            background-color: #f8f9fa;
                            padding: 12px;
                            border-radius: 4px;
                            word-break: break-all;
                            font-family: monospace;
                            font-size: 14px;
                            margin: 16px 0;
                            border: 1px solid #e0e0e0;
                        }
                        
                          /* New logo styles */
                          .footer-logo {
                            margin-bottom: 16px;
                            text-align: center;
                        }
                        
                        .footer-logo img {
                            max-width: 150px;
                            height: auto;
                        }
                        
                      /* Updated footer styles */
                            .footer {
                                padding: 24px;
                                text-align: center;
                                background-color: #f8f9fa;
                                border-radius: 0 0 8px 8px;
                                border: 1px solid #e0e0e0;
                            }
                            
                            .footer p {
                                color: #666666;
                                font-size: 14px;
                                margin-bottom: 8px;
                            }
                        
                         /* Responsive styles */
                            @media only screen and (max-width: 600px) {
                                .container {
                                    padding: 10px;
                                }
                                
                                .footer-logo img {
                                    max-width: 120px;
                                }
                            }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h2>Performance Review Final Approval</h2>
                        </div>
                        <div class="content">
                            <p>Dear Partner,</p>
                            <p>A performance review has been completed and requires your final approval.</p>
        
                            <p>Please click the button below to access the performance review form:</p>
                            <p style="text-align: center;">
                                <a href="{formUrl}" class="button">Access Peformance Review Form</a>
                            </p>
                            <p>If the button doesn't work, you can copy and paste this link into your browser:</p>
                            <p style="word-break: break-all;">{formUrl}</p>
                            <p><strong>Important Notes:</strong></p>
                            <ul>
                                <li>This link is unique to you and should not be shared</li>
                                <li>Please review all feedback before approval</li>
                                <li>Your approval will finalize the review process</li>
                            </ul>
                        </div>
                        <div class="footer">
                            <div class="footer-logo">
                                <img src="data:image/png;base64,{$base64Logo}" alt="FusionETA Logo" />
                            </div>
                            <p>This is an automated message. Please do not reply to this email.</p>
                            <p>&copy; 2024 FusionETA. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
                HTML,
                'text' => <<<TEXT
                Performance Review Final Approval

                Dear Partner,

                A performance review has been completed and requires your final approval.

              

                Please access the approval form using the following link:
                {formUrl}

                Important Notes:
                - This link is unique to you and should not be shared
                - Please review all feedback before approval
                - Your approval will finalize the review process

                This is an automated message. Please do not reply to this email.
                TEXT
            ],
            'view-only' => [
                'subject' => 'Your Performance Review Results',
                'html' => <<<HTML
                <!DOCTYPE html>
                <html>
                <head>
                    <style>
                         /* Reset styles */
                         body, div, p, h1, h2, h3, ul, li {
                            margin: 0;
                            padding: 0;
                        }
                        
                        body {
                            font-family: Arial, sans-serif;
                            line-height: 1.6;
                            color: #333333;
                            background-color: #f5f5f5;
                        }
                        
                        /* Container styles */
                        .container {
                            max-width: 600px;
                            margin: 0 auto;
                            padding: 20px;
                            background-color: #ffffff;
                        }
                        
                        /* Header styles */
                        .header {
                            background-color: #1a73e8;
                            padding: 24px;
                            text-align: center;
                            border-radius: 8px 8px 0 0;
                        }
                        
                        .header h2 {
                            color: #ffffff;
                            font-size: 24px;
                            margin: 0;
                        }
                        
                        /* Content styles */
                        .content {
                            padding: 32px 24px;
                            background-color: #ffffff;
                            border-left: 1px solid #e0e0e0;
                            border-right: 1px solid #e0e0e0;
                        }
                        
                        .content p {
                            margin-bottom: 16px;
                            color: #333333;
                        }
                        
                        /* Button styles */
                        .button {
                            display: inline-block;
                            padding: 12px 24px;
                            background-color: #1a73e8;
                            color: #ffffff !important;
                            text-decoration: none;
                            border-radius: 4px;
                            font-weight: bold;
                            margin: 16px 0;
                            transition: background-color 0.3s ease;
                        }
                        
                        .button:hover {
                            background-color: #1557b0;
                        }
                        
                        /* List styles */
                        ul {
                            margin: 16px 0;
                            padding-left: 24px;
                        }
                        
                        li {
                            margin-bottom: 8px;
                            color: #555555;
                        }
                        
                        /* Important notes section */
                        .important-notes {
                            background-color: #f8f9fa;
                            padding: 16px;
                            border-radius: 4px;
                            margin: 24px 0;
                            border-left: 4px solid #1a73e8;
                        }
                        
                        .important-notes h3 {
                            color: #1a73e8;
                            margin-bottom: 12px;
                        }
                        
                        /* Form URL styles */
                        .form-url {
                            background-color: #f8f9fa;
                            padding: 12px;
                            border-radius: 4px;
                            word-break: break-all;
                            font-family: monospace;
                            font-size: 14px;
                            margin: 16px 0;
                            border: 1px solid #e0e0e0;
                        }
                        
                          /* New logo styles */
                          .footer-logo {
                            margin-bottom: 16px;
                            text-align: center;
                        }
                        
                        .footer-logo img {
                            max-width: 150px;
                            height: auto;
                        }
                        
                      /* Updated footer styles */
                            .footer {
                                padding: 24px;
                                text-align: center;
                                background-color: #f8f9fa;
                                border-radius: 0 0 8px 8px;
                                border: 1px solid #e0e0e0;
                            }
                            
                            .footer p {
                                color: #666666;
                                font-size: 14px;
                                margin-bottom: 8px;
                            }
                        
                         /* Responsive styles */
                            @media only screen and (max-width: 600px) {
                                .container {
                                    padding: 10px;
                                }
                                
                                .footer-logo img {
                                    max-width: 120px;
                                }
                            }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h2>Performance Review Results</h2>
                        </div>
                        <div class="content">
                            <p>Dear Employee,</p>
                            <p>Your performance review process has been completed and approved! You can now access the final reviewed performance appraisal.</p>
                            
                            <p>Please click the button below to view your performance review:</p>
                            <p style="text-align: center;">
                                <a href="{formUrl}" class="button">View Completed Performance Review</a>
                            </p>
                            
                            <p>If the button doesn't work, you can copy and paste this link into your browser:</p>
                            <p style="word-break: break-all;">{formUrl}</p>
                            <p><b>You may also download and view the performance appraisal report (with scoring) attached in this email.</b></p>
                            <p><strong>Important Notes:</strong></p>
                            <ul>
                                <li>This link is unique to you and should not be shared</li>
                                <li>Take time to review the feedback provided</li>
                                <li>Consider scheduling a follow-up discussion with your supervisor if needed</li>
                                <li>Save or download a copy of your review for your records</li>
                            </ul>
                        </div>
                        <div class="footer">
                        <div class="footer-logo">
                                <img src="data:image/png;base64,{$base64Logo}" alt="FusionETA Logo" />
                            </div>
                            <p>This is an automated message. Please do not reply to this email.</p>
                            <p>&copy; 2024 FusionETA. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
                HTML,
                'text' => <<<TEXT
                Performance Review Results

                Dear Employee,

                Your performance review process has been completed and approved. You can now access the final review results.

                Please access your performance review using the following link:
                {formUrl}

                Important Notes:
                - This link is unique to you and should not be shared
                - Take time to review the feedback provided
                - Consider scheduling a follow-up discussion with your supervisor if needed
                - Save or download a copy of your review for your records

                This is an automated message. Please do not reply to this email.
                TEXT
            ]
                
        ];

        return $templates[$userType] ?? null;
    }
}

class EmailRouter {
    public function routeAdditionalEmails($userType, $recordId, $instanceId, $spaId, EmailComponent $emailComponent, $recipientEmail, $pdfPath=null) {
        try {
            if ($recipientEmail && $userType) {
                logMessageE("Routing additional email for userType: $userType to: $recipientEmail");
                
                // Get the next user type
                //$nextUserType = ($userType === 'reviewee') ? 'reviewer' : 'partner';
                if ($userType === 'init') {
                    $nextUserType = 'reviewee';
                } elseif ($userType === 'reviewee') {
                    $nextUserType = 'reviewer';
                } elseif ($userType === 'reviewer') {
                    $nextUserType = 'partner';
                } elseif ($userType === 'partner') {
                    $nextUserType = 'view-only';
                } else {
                    $nextUserType = 'view-only'; // or handle other cases as needed
                }
                
                // Construct the form URL
                $formUrl = sprintf(
                    "https://fusioneta.com.my/massmarket/employee_app/form.php?recordId=%s&instanceId=%s&spaId=%s&user=%s",
                    urlencode($recordId),
                    urlencode($instanceId),
                    urlencode($spaId),
                    urlencode($nextUserType)
                );

                // Get email template for the next user
                $emailTemplate = EmailTemplates::getEmailContent($nextUserType);
                
                if ($emailTemplate ) {
                    // Send email with appropriate template
                    $this->sendTemplatedEmail(
                        $emailComponent,
                        $recipientEmail,
                        $formUrl,
                        $emailTemplate,
                        $pdfPath// pdf report here passed
                    );
                    logMessageE("Additional form email sent successfully to: $recipientEmail");
                } else {
                    logMessageE("No email template found for user type: $nextUserType");
                }
            }
        } catch (Exception $e) {
            logMessageE("Error routing additional email: " . $e->getMessage());
        }
    }

    private function sendTemplatedEmail($emailComponent, $recipientEmail, $formUrl, $template, $pdfPath=null) {
    
        
        // Get mailer using the getter method
        $mailer = $emailComponent->getMailer();
        
        // Clear any previous recipients
        $mailer->clearAddresses();
        
        // Replace placeholders in templates
        $htmlBody = str_replace(
            '{formUrl}',
            $formUrl,
            $template['html']
        );
        
        $textBody = str_replace(
            '{formUrl}',
            $formUrl,
            $template['text']
        );

        // Log email details before sending
    logMessageE("Preparing to send email to: $recipientEmail with subject: {$template['subject']}");

        // Use the email component's mailer to send the email
        try {
            $mailer->isSMTP();
            $mailer->Host = 'mail.fusioneta.com.my';
            $mailer->SMTPAuth = true;
            $mailer->Username = 'appraizzie@fusioneta.com.my';
           $mailer->Password = 'fusionPass123!';
            $mailer->SMTPSecure = 'tls';
            $mailer->Port = 587;
            $mailer->SMTPDebug = 2; // Set to 2 for detailed debugging output
            $mailer->setFrom('appraizzie@fusioneta.com.my', 'No-Reply Appraizzie');
            $mailer->addAddress($recipientEmail);
           $mailer->isHTML(true);
            $mailer->Subject = $template['subject'];
            $mailer->Body = $htmlBody;
           $mailer->AltBody = $textBody;

        //    if (file_exists($pdfPath)) {
        //             $mailer->addAttachment($pdfPath);
        //             logMessageE("PDF attached: $pdfPath");
        //         } else {
        //             logMessageE("PDF file not found: $pdfPath");
        //         }

        //$mailer->addStringAttachment($pdfPath, 'Performance_Appraisal_Report.pdf');

         // Only attach PDF if pdfPath is provided
         if ($pdfPath !== null && $pdfPath !== '') {
            try {
                $mailer->addStringAttachment($pdfPath, 'Performance_Appraisal_Report.pdf');
                logMessageE("PDF attached successfully");
            } catch (Exception $e) {
                logMessageE("Failed to attach PDF, continuing without attachment: " . $e->getMessage());
                // Continue sending email even if PDF attachment fails
            }
        }
        

            $mailer->send();

              // Log success message
        logMessageE("Email successfully sent to: $recipientEmail");
          
        } catch (Exception $e) {
            logMessageE("Failed to send email to: $recipientEmail. Error: " . $mailer->ErrorInfo);
        
            throw new Exception('Email could not be sent. Mailer Error: ' .$mailer->ErrorInfo);
        }
    }
}

class EmailComponent {
   
    private $spaManager;
    private  $mailer;
    private $memberId;
    public function __construct( ) {

        $this->mailer = new PHPMailer(true);

    }

    // Add this getter method to access the mailer
    public function getMailer() {
        return $this->mailer;
    }

    function getSpaIdFromSettings($instanceId) {
    
    logMessageE("Inside Get spaid from settings.");
            
        $settingsPath = INSTANCE_SETTINGS_DIR . '/' . $instanceId . '/spa_settings.json';
        logMessageE("this is isntance id: $instanceId");
        logMessageE("this is settingspath: $settingsPath");
        
        if (!file_exists($settingsPath)) {
        logMessageE("Settings file not found for instance: $instanceId");
            throw new Exception("Settings file not found for instance: " . $instanceId);
             
        }
        
        $settings = json_decode(file_get_contents($settingsPath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
             logMessageE("Invalid JSON in settings file");
            throw new Exception("Invalid JSON in settings file");
       
        }
        
        // Log the parsed settings
    logMessageE("Parsed settings: " . print_r($settings, true));
    
        // Get the first spa ID from the spas array
        if (isset($settings['spas']) && !empty($settings['spas'])) {
            // Get the first key from the spas array
            $spaIds = array_keys($settings['spas']);
          logMessageE("Retrieved INSIDE SPA ID: {$spaIds[0]}"); // Log before returning
            return $spaIds[0];
            
        }
        logMessageE("No SPA ID found in settings");
        throw new Exception("No SPA ID found in settings");
         
    }

    public function handleFormGeneration($email, $instanceId, $userType, $customfields) {

        try {
            CRestExt::setCurrentBitrix24($instanceId);
            
            logMessageE("Handling form generation for email: $email");
            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email address');
            }

            
            if ($instanceId === null) {
                logMessageE("Instance ID is not set");
                throw new Exception("Member ID is not set in the current session.");
            }
            $spaManager = new BitrixSPAManager($instanceId);

            // $instanceId = $this->$memberId;
            $spaId = $this->getSpaIdFromSettings($instanceId);
            logMessageE("Retrieved SPA ID: $spaId");
            
            // Create SPA record using your existing class method
            $recordId = $spaManager->createSPARecord($spaId, $customfields);
            $recordId = $recordId['result']['id'];
            logMessageE("Created SPA record with ID: $recordId");

            if ($userType && $email) {
                $emailRouter = new EmailRouter();
                $emailRouter->routeAdditionalEmails(
                    $userType,
                    $recordId,
                    $instanceId,
                    $spaId,
                    $this,
                    $email
                );
            }
    
            $response = [
                'success' => true,
                'message' => 'Form generated and sent successfully',
                'recordId' => $recordId,
            ];

            // Send email
            // $this->sendFormEmail($email, $formUrl, $customMessage);
            // logMessageE("Form email sent successfully to: $email");
         
            logMessageE("Sending JSON response: " . json_encode($response));
            file_put_contents(__DIR__ . '/debug-log.txt', "Sending JSON response: " . json_encode($response) . "\n", FILE_APPEND);

        } catch (Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
      
    }

  
}

// Instantiate the class and call the method
//$registry = BitrixMemberRegistry::getInstance();
// $emailComponent = new EmailComponent();
// $emailComponent->handleFormGeneration();
?>
