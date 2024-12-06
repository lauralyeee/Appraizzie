<?php
// install.php
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/cextrest.php');
require_once(__DIR__ . '/BitrixSPAManager.php');
require_once(__DIR__ . '/userNotifyClass.php');
//require_once(__DIR__ . '/analytic_dashboard.php');
//require_once(__DIR__ . '/email/EmailComponent.php');
       
session_start();
$installationResult = null;
$isInstalled = false;  // for to check whether this bitrix ady install
$installedSpaInfo = null;

// Retrieve the member ID from $_REQUEST
$memberId = $_REQUEST['member_id'] ?? null;


    // Store the memberId in the session
    $_SESSION['member_id'] = $memberId;
   // echo "Member ID successfully set!";

// Check if SPA is already installed for this instance
if (!empty($_REQUEST['member_id'])) {
    $settingsPath = INSTANCE_SETTINGS_DIR . '/' . $_REQUEST['member_id'] . '/spa_settings.json';
    if (file_exists($settingsPath)) {
        $settings = json_decode(file_get_contents($settingsPath), true);
        if ($settings && !empty($settings['spas'])) {
            $isInstalled = true;
            // Get the first (and should be only) SPA's information
            $installedSpaInfo = reset($settings['spas']);
            $installedSpaId = key($settings['spas']);
        }
    }
}

//when click install
if (!$isInstalled && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install_submitted'])) {
    CRestExt::setCurrentBitrix24($_REQUEST['member_id']); 
    // error_log('POST data received: ' . print_r($_POST, true));
    // error_log('REQUEST data: ' . print_r($_REQUEST, true));
    
    try {
        if (!file_exists(INSTANCE_SETTINGS_DIR)) {
            mkdir(INSTANCE_SETTINGS_DIR, 0755, true);
        }
       
        $instanceId = $_REQUEST['member_id'];
        $domain = $_REQUEST['DOMAIN'];

        $installationLogs = [];  // Store logs to pass to JavaScript
      
    
        $spaManager = new BitrixSPAManager($instanceId); //constructor here 1. sanitize instance id 2. loadSettingss() create setting strucutre

        // Step 1: Create SPA
        $spaResult = $spaManager->createSPA($instanceId, $domain);
        $spaId = $spaResult['result']['id'];
        $installationLogs['createSPA'] = $spaResult;

        //createSPAStageSetup
        $setupStages = $spaManager->setupSPAStages($spaResult['result']['id']);
        $installationLogs['stageSetup'] = $setupStages;

       // Field extraction from JSON configuration
        $fields = [
      
            // Basic Profile Fields
            'REVIEWEE' => 'Reviewee Name',
            'REVIEWER' => 'Reviewer Selection',
            'PARTNER' => 'Partner Selection', 
            'YEAR' => 'Year Selection',
            'APPRAISAL_TYPE' => 'Performance Appraisal',
            'TEAM' => 'Team Selection',
            'ROLE' => 'Role Selection',
  
            // Question 1 - Communication Skills
            'QUESTION_1_REVIEWEE_RATING' => 'Question 1 Rating (Reviewee)',
            'QUESTION_1_REVIEWEE_COMMENT' => 'Question 1 Comment (Reviewee)',
            'QUESTION_1_REVIEWER_RATING' => 'Question 1 Rating (Reviewer)',
            'QUESTION_1_REVIEWER_COMMENT' => 'Question 1 Comment (Reviewer)',
            'QUESTION_1_PARTNER_COMMENT' => 'Question 1 Comment (Partner)',

            // Question 2 - Passion for Excellence
            'QUESTION_2_REVIEWEE_RATING' => 'Question 2 Rating (Reviewee)',
            'QUESTION_2_REVIEWEE_COMMENT' => 'Question 2 Comment (Reviewee)',
            'QUESTION_2_REVIEWER_RATING' => 'Question 2 Rating (Reviewer)',
            'QUESTION_2_REVIEWER_COMMENT' => 'Question 2 Comment (Reviewer)',
            'QUESTION_2_PARTNER_COMMENT' => 'Question 2 Comment (Partner)',

            // Question 3 - Accountability
            'QUESTION_3_REVIEWEE_RATING' => 'Question 3 Rating (Reviewee)',
            'QUESTION_3_REVIEWEE_COMMENT' => 'Question 3 Comment (Reviewee)',
            'QUESTION_3_REVIEWER_RATING' => 'Question 3 Rating (Reviewer)',
            'QUESTION_3_REVIEWER_COMMENT' => 'Question 3 Comment (Reviewer)',
            'QUESTION_3_PARTNER_COMMENT' => 'Question 3 Comment (Partner)',

            // Question 4 - Analytical Skills
            'QUESTION_4_REVIEWEE_RATING' => 'Question 4 Rating (Reviewee)',
            'QUESTION_4_REVIEWEE_COMMENT' => 'Question 4 Comment (Reviewee)',
            'QUESTION_4_REVIEWER_RATING' => 'Question 4 Rating (Reviewer)',
            'QUESTION_4_REVIEWER_COMMENT' => 'Question 4 Comment (Reviewer)',
            'QUESTION_4_PARTNER_COMMENT' => 'Question 4 Comment (Partner)',

            // Question 5 - Quality of Work
            'QUESTION_5_REVIEWEE_RATING' => 'Question 5 Rating (Reviewee)',
            'QUESTION_5_REVIEWEE_COMMENT' => 'Question 5 Comment (Reviewee)',
            'QUESTION_5_REVIEWER_RATING' => 'Question 5 Rating (Reviewer)',
            'QUESTION_5_REVIEWER_COMMENT' => 'Question 5 Comment (Reviewer)',
            'QUESTION_5_PARTNER_COMMENT' => 'Question 5 Comment (Partner)',

            // Question 6 - Client Service
            'QUESTION_6_REVIEWEE_RATING' => 'Question 6 Rating (Reviewee)',
            'QUESTION_6_REVIEWEE_COMMENT' => 'Question 6 Comment (Reviewee)',
            'QUESTION_6_REVIEWER_RATING' => 'Question 6 Rating (Reviewer)',
            'QUESTION_6_REVIEWER_COMMENT' => 'Question 6 Comment (Reviewer)',
            'QUESTION_6_PARTNER_COMMENT' => 'Question 6 Comment (Partner)',

            // Question 7 - Delivery and Completion
            'QUESTION_7_REVIEWEE_RATING' => 'Question 7 Rating (Reviewee)',
            'QUESTION_7_REVIEWEE_COMMENT' => 'Question 7 Comment (Reviewee)',
            'QUESTION_7_REVIEWER_RATING' => 'Question 7 Rating (Reviewer)',
            'QUESTION_7_REVIEWER_COMMENT' => 'Question 7 Comment (Reviewer)',
            'QUESTION_7_PARTNER_COMMENT' => 'Question 7 Comment (Partner)',

            // Question 8 - Technical Knowledge
            'QUESTION_8_REVIEWEE_RATING' => 'Question 8 Rating (Reviewee)',
            'QUESTION_8_REVIEWEE_COMMENT' => 'Question 8 Comment (Reviewee)',
            'QUESTION_8_REVIEWER_RATING' => 'Question 8 Rating (Reviewer)',
            'QUESTION_8_REVIEWER_COMMENT' => 'Question 8 Comment (Reviewer)',
            'QUESTION_8_PARTNER_COMMENT' => 'Question 8 Comment (Partner)',

            // Question 9 - Commitment and Attitude
            'QUESTION_9_REVIEWEE_RATING' => 'Question 9 Rating (Reviewee)',
            'QUESTION_9_REVIEWEE_COMMENT' => 'Question 9 Comment (Reviewee)',
            'QUESTION_9_REVIEWER_RATING' => 'Question 9 Rating (Reviewer)',
            'QUESTION_9_REVIEWER_COMMENT' => 'Question 9 Comment (Reviewer)',
            'QUESTION_9_PARTNER_COMMENT' => 'Question 9 Comment (Partner)',

            // Question 9 - Commitment and Attitude
            'QUESTION_10_REVIEWEE_RATING' => 'Question 10 Rating (Reviewee)',
            'QUESTION_10_REVIEWEE_COMMENT' => 'Question 10 Comment (Reviewee)',
            'QUESTION_10_REVIEWER_RATING' => 'Question 10 Rating (Reviewer)',
            'QUESTION_10_REVIEWER_COMMENT' => 'Question 10 Comment (Reviewer)',
            'QUESTION_10_PARTNER_COMMENT' => 'Question 10 Comment (Partner)',
            
            // Goals Review
            'GOALS_REVIEW_REVIEWEE_COMMENT' => 'Goals Review Comment (Reviewee)',
            'GOALS_REVIEW_REVIEWER_COMMENT' => 'Goals Review Comment (Reviewer)',
            'GOALS_REVIEW_PARTNER_COMMENT' => 'Goals Review Comment (Partner)',
            
            // Overall Remarks
            'OVERALL_REMARKS_REVIEWEE_COMMENT' => 'Overall Remarks (Reviewee)',
            'OVERALL_REMARKS_REVIEWER_COMMENT' => 'Overall Remarks (Reviewer)',
            'OVERALL_REMARKS_PARTNER_COMMENT' => 'Overall Remarks (Partner)',
            
            // Development Plans
            'DEVELOPMENT_PLANS_REVIEWEE_COMMENT' => 'Development Plans (Reviewee)',
            'DEVELOPMENT_PLANS_REVIEWER_COMMENT' => 'Development Plans (Reviewer)',
            'DEVELOPMENT_PLANS_PARTNER_COMMENT' => 'Development Plans (Partner)',
            
            //Rating
            'REVIEWEE_RATING_SCORE' => 'Reviewee Self Rating Score Percentage',
            'REVIEWER_RATING_SCORE' => 'Reviewer Rating Score Percentage',
            'TOTAL_AVERAGE_RATING_SCORE' => 'Total Rating Score Percentage',
            'REFERENCE_NO'=> 'Reference No'
            
        ];
        
        // get the custom field from json and insert it / all of them are just text string
        $fieldResults = [];
        foreach ($fields as $fieldName => $displayName) {
            $fieldResults[$fieldName] = $spaManager->createCustomField($spaId, $fieldName);
        }

        $installationLogs['createFields'] = $fieldResults;


        // here trigger createStage...
        $installationResult = [
            'success' => true,
            'spaId' => $spaId,
            'logs' => $installationLogs
        ];

        // Send a JSON response instead of HTML redirect
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'redirect' => 'index.php?' . http_build_query([
                'member_id' => $instanceId,
                'DOMAIN' => $domain,
                'state' => 'forminit'
            ])
        ]);
        exit;
        

    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}


//send email notification to admin about new user or admin accessing the page
$userTracker = new UserTracker();
// Get current user (this will trigger the notification to all configured recipients)
$currentUser = $userTracker->getCurrentUser();

// Simple state management for installed app
$state = isset($_REQUEST['state']) ? $_REQUEST['state'] : 'forminit';
//$state = isset($_REQUEST['state']) ? $_REQUEST['state'] : 'analytic-dashboard';
// Define available pages and their titles
$pages = [
    'forminit' => 'Performance Review',
    'analytic-dashboard' => 'Analytics Dashboard'
   // You can add more pages here
];

$website_url = "https://fusioneta.com.my/";
$facebook_url = "https://www.facebook.com/Fusioneta/";
$linkedin_url = "https://www.linkedin.com/company/fusioneta-sdn-bhd/?original_referer=https%3A%2F%2Ffusioneta.com%2F";

?>

<!DOCTYPE html>
<html lang="en">
<head>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Appraisal System Installation</title>
    <?php if (!empty($_REQUEST['DOMAIN'])): ?>
        <script src="//api.bitrix24.com/api/v1/"></script>
    <?php endif; ?>
   <style>
        body {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        background-color: #f8fafc;
        margin: 0;
        padding: 20px;
    }
    /* .container {
        max-width: 800px;
        margin: 20px auto;
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        overflow: hidden;
    } */
    .card-header {
        background: linear-gradient(135deg, #4a6bff 0%, #2541b2 100%);
        padding: 2rem 1.5rem;
        border-radius: 12px;
        text-align: center;
        position: relative;
    }

    /* .card-header::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(90deg, #4a6bff, #2541b2, #4a6bff);
        background-size: 200% 100%;
        animation: gradient-slide 3s linear infinite;
    } */

    @keyframes gradient-slide {
        0% { background-position: 100% 0; }
        100% { background-position: -100% 0; }
    }

    .card-header h2 {
        color: white;
        font-size: 1.75rem;
        font-weight: 600;
        margin: 0 0 0.5rem 0;
        text-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }

    .subtitle {
        color: rgba(255,255,255,0.9);
        font-size: 1rem;
        margin: 0;
        opacity: 0.9;
    }

    /* Form Section */
    form {
        padding: 1.5rem;
    }

    .btn-primary { 
        background: linear-gradient(135deg, #4a6bff 0%, #2541b2 100%);
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 6px;
        font-weight: 500;
        color: white;
        width: 100%;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 1rem;
    }

    .btn-primary:hover { 
        background: linear-gradient(135deg, #5c7aff 0%, #2f4ccc 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(74, 107, 255, 0.2);
    }

    /* Messages */
    .success-message,
    .error-message,
    .warning-message {
        margin: 1rem 1.5rem;
        padding: 1rem;
        border-radius: 6px;
        font-size: 0.875rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .success-message {
        background-color: #ecfdf5;
        border: 1px solid #10b981;
        color: #047857;
    }

    .error-message {
        background-color: #fef2f2;
        border: 1px solid #ef4444;
        color: #b91c1c;
    }

    .warning-message {
        background-color: #fff7ed;
        border: 1px solid #fdba74;
        color: #c2410c;
    }

    /* Installation Progress */
    .installation-progress {
        padding: 1.5rem;
        text-align: center;
    }

    .progress-bar {
        width: 100%;
        height: 4px;
        background-color: #e5e7eb;
        border-radius: 2px;
        overflow: hidden;
        margin: 1rem 0;
    }

    .progress-bar-fill {
        height: 100%;
        background-color: #4a6bff;
        width: 0%;
        transition: width 0.4s ease;
    }

    .status-text {
        color: #6b7280;
        font-size: 0.875rem;
    }

    /* Navigation */
    .nav-menu {
        border-radius: 8px;
        padding: 1rem 1.5rem;
        background: #f8f9fa;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        gap: 0.5rem;
        overflow-x: auto;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .nav-item {
        padding: 0.5rem 1rem;
        background: transparent;
        border: 1px solid #e5e7eb;
        border-radius: 4px;
        color: #4b5563;
        text-decoration: none;
        font-size: 1rem;
        font-weight: 500;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .nav-item:hover {
        background: #f3f4f6;
        color: #2563eb;
    }

    .nav-item.active {
        background: #2563eb;
        color: white;
        border-color: #2563eb;
    }

    /* Content Pages */
    .content-page {
        display: none;
        padding: 1.5rem;
    }

    .content-page.active {
        display: block;
    }

    /* Installed Info */
    .installed-info {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 1.5rem;
        margin: 1.5rem;
    }

    .info-grid {
        display: grid;
        grid-template-columns: auto 1fr;
        gap: 0.75rem;
        margin-top: 1rem;
    }

    .info-label {
        font-weight: 500;
        color: #475569;
        font-size: 0.875rem;
    }

    .info-value {
        color: #1e293b;
        font-size: 0.875rem;
    }


    /* here is the dashboard */


    body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-init-container {
            padding: 2rem;
            max-width: 900px;
            width: 100%;
            margin: 2rem auto;
        }

        .form-init-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 24px rgba(74, 107, 255, 0.12);
            overflow: hidden;
        }

        .card-header-dash {
            background: linear-gradient(135deg, #4a6bff 0%, #2541b2 100%);
            padding: 3rem 2rem;
            text-align: center;
            position: relative;
        }

        .card-header-dash::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #4a6bff, #2541b2, #4a6bff);
            background-size: 200% 100%;
            animation: gradient-slide 3s linear infinite;
        }

        @keyframes gradient-slide {
            0% { background-position: 100% 0; }
            100% { background-position: -100% 0; }
        }

        .card-header-dash h2 {
            color: white;
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .subtitle {
            color: rgba(255,255,255,0.9);
            font-size: 1.2rem;
            margin-bottom: 0;
        }

        .card-content-dash {
            padding: 3rem 2rem;
            text-align: center;
        }

        .features-grid-dash {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
            padding: 0 1rem;
        }

        .feature-item-dash {
            padding: 1.5rem;
            border-radius: 12px;
            background: #f8f9ff;
            transition: all 0.3s ease;
            position: relative;
            opacity: 0.85;
        }

        .feature-item-dash:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(74, 107, 255, 0.08);
        }

        .feature-icon {
            font-size: 2rem;
            color: #4a6bff;
            margin-bottom: 1rem;
        }

        .feature-title {
            color: #2541b2;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .feature-description {
            color: #566a7f;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 0;
        }

        .init-button-dash {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #4a6bff 0%, #2541b2 100%);
            color: white;
            padding: 1rem 2.5rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            margin-top: 2rem;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 4px 12px rgba(74, 107, 255, 0.2);
            opacity: 0.7;
            cursor: not-allowed;
        }

        .website-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: white;
            color: #4a6bff;
            padding: 1rem 2.5rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            margin-top: 1rem;
            transition: all 0.3s ease;
            border: 2px solid #4a6bff;
            box-shadow: 0 4px 12px rgba(74, 107, 255, 0.1);
        }

        .website-button:hover {
            background: #f8f9ff;
            color: #2541b2;
            border-color: #2541b2;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(74, 107, 255, 0.15);
            text-decoration: none;
        }

        .init-button-dash:hover {
            color: white;
            text-decoration: none;
        }

        .info-text {
            color: #566a7f;
            font-size: 1.1rem;
            line-height: 1.6;
            max-width: 600px;
            margin: 0 auto 2rem;
        }

        .coming-soon-badge {
            background: #FFD700;
            color: #2541b2;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            position: absolute;
            top: 1rem;
            right: 1rem;
            box-shadow: 0 2px 8px rgba(255, 215, 0, 0.3);
        }

        .lock-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
        }

        .lock-icon {
            font-size: 1.5rem;
            color: #4a6bff;
            opacity: 0.5;
        }

        .launch-date {
            color: #4a6bff;
            font-weight: 600;
            font-size: 1.1rem;
            margin-top: 2rem;
        }

        .stay-tuned-section {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 2px solid rgba(74, 107, 255, 0.1);
        }

        .stay-tuned-title {
            color: #2541b2;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .social-icons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1.5rem;
        }

        .social-icon {
            color: #4a6bff;
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }

        .social-icon:hover {
            color: #2541b2;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
<div class="container">
    <?php if ($isInstalled): ?>
            <!-- Application UI for installed state -->
            <div class="nav-menu">
            <?php foreach ($pages as $pageId => $pageTitle): ?>
                    <a class="nav-item <?php echo $state === $pageId ? 'active' : ''; ?>" 
                       href="#<?php echo $pageId; ?>"
                       onclick="return switchPage('<?php echo $pageId; ?>')">
                        <?php echo $pageTitle; ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Content container -->
            <div class="content-area">
                <!-- Dashboard Page -->
                <div id="forminit-content" class="content-page <?php echo $state === 'forminit' ? 'active' : ''; ?>">
                    <div class="forminit-content">
                        
                        <!-- Add your dashboard content here -->
                        <?php 
                        if ($state === 'forminit') {
                            include 'forminit.php';
                        }
                        ?>
                    </div>
                </div>

                <!-- Settings Page -->
                <div id="analytic-dashboard-content" class="content-page <?php echo $state === 'analytic-dashboard' ? 'active' : ''; ?>">
                 
                    <div class="analytic-dashboard-content">
                        <!-- Add your settings content here -->
                   
                            <div class="form-init-card">
                                <div class="card-header-dash">
                                    <h2>Bitrix24 Analytics Dashboard</h2>
                                    <p class="subtitle">Powerful Insights Coming Soon</p>
                                </div>
                                
                                <div class="card-content-dash">
                                    <p class="info-text">Get ready for an enhanced analytics experience. Our new Bitrix24 dashboard will provide comprehensive insights into performance metrics, trends, and actionable data.</p>
                                    
                                    <div class="features-grid-dash">
                                        <div class="feature-item-dash">
                                            <span class="coming-soon-badge">Coming Soon</span>
                                            <div class="lock-overlay">
                                                <i class="fas fa-lock lock-icon"></i>
                                            </div>
                                            <i class="fas fa-chart-line feature-icon"></i>
                                            <h3 class="feature-title">Performance Metrics</h3>
                                            <p class="feature-description">Track key performance indicators and metrics in real-time</p>
                                        </div>
                                        
                                        <div class="feature-item-dash">
                                            <span class="coming-soon-badge">Coming Soon</span>
                                            <div class="lock-overlay">
                                                <i class="fas fa-lock lock-icon"></i>
                                            </div>
                                            <i class="fas fa-chart-bar feature-icon"></i>
                                            <h3 class="feature-title">Data Visualization</h3>
                                            <p class="feature-description">Interactive charts and graphs for better data understanding</p>
                                        </div>
                                        
                                    </div>
                                    
                                    
                                    <a href="#" class="init-button-dash" onclick="return false;">
                                        <i class="fas fa-clock"></i>
                                        Dashboard Coming Soon
                                    </a>

                                    <div class="stay-tuned-section">
                                        <h3 class="stay-tuned-title">Stay Updated</h3>
                                        <p class="info-text">Don't miss out on our latest features and updates. Visit our website to learn more about upcoming releases and announcements.</p>
                                        
                                        <a href="<?php echo $website_url; ?>" class="website-button">
                                            <i class="fas fa-globe me-2"></i>
                                            Visit Our Website
                                        </a>
                                        
                                        <div class="social-icons">
                                            <a href="<?php echo $facebook_url; ?>" class="social-icon" target="_blank">
                                                <i class="fab fa-facebook"></i>
                                            </a>
                                            <a href="<?php echo $linkedin_url; ?>" class="social-icon" target="_blank">
                                                <i class="fab fa-linkedin"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                   
                    </div>
                </div>

            </div>

        <?php else: ?>
            <!-- Installation UI -->
            <div class="card-header">
                <h2>Employee Appraisal System</h1>
                <p class="subtitle">Installation Required</p>
            </div>

            <?php if ($installationResult !== null): ?>
                <?php if ($installationResult['success']): ?>
                    <div class="success-message">
                        Installation completed successfully!<br>
                        SPA ID: <?php echo htmlspecialchars($installationResult['spaId']); ?>
                    </div>       
                <?php else: ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($installationResult['message']); ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <form id="installForm" method="POST" onsubmit="startInstallation(event)">
                <input type="hidden" name="install_submitted" value="1">
                <input type="hidden" name="member_id" value="<?php echo htmlspecialchars($_REQUEST['member_id'] ?? ''); ?>">
                <input type="hidden" name="DOMAIN" value="<?php echo htmlspecialchars($_REQUEST['DOMAIN'] ?? ''); ?>">
                
                <!-- <div style="margin-bottom: 20px;">
                    <label style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="createDemo" checked>
                        Create demo data
                    </label>
                </div> -->

                <button type="submit" class="btn btn-primary" id="installButton">Start Installation</button>
            </form>

            <div id="installationProgress" class="installation-progress" style="display: none;">
                <div class="progress-bar">
                    <div class="progress-bar-fill" id="progressBarFill"></div>
                </div>
                <div class="status-text" id="statusText">Preparing installation...</div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
        // Installation form handling
        const installForm = document.getElementById('installForm');
        const progress = document.getElementById('installationProgress');
        const progressBar = document.getElementById('progressBarFill');
        const statusText = document.getElementById('statusText');

        if (installForm) {
            // Remove the inline onsubmit handler from HTML and handle it here
            installForm.addEventListener('submit', function(event) {
                event.preventDefault(); // Prevent default form submission
                
                // Show progress UI
                installForm.style.display = 'none';
                progress.style.display = 'block';
                
                // Log form data for debugging
                console.log('Form data:', {
                    member_id: this.elements['member_id'].value,
                    domain: this.elements['DOMAIN'].value,
                    install_submitted: this.elements['install_submitted'].value
                });

                // Installation steps simulation
                const steps = [
                    { progress: 20, text: 'Creating SPA structure...' },
                    { progress: 40, text: 'Creating custom fields...' },
                    { progress: 60, text: 'Creating initial record...' },
                    { progress: 80, text: 'Setting up demo data...' },
                    { progress: 100, text: 'Finalizing installation...' }
                ];

                let currentStep = 0;
                
                // Show initial progress state
                progressBar.style.width = '0%';
                statusText.textContent = 'Starting installation...';

                // Progress animation
                const interval = setInterval(() => {
                    if (currentStep < steps.length) {
                        const step = steps[currentStep];
                        progressBar.style.width = step.progress + '%';
                        statusText.textContent = step.text;
                        console.log(`Installation step ${currentStep + 1}:`, step.text);
                        currentStep++;
                    } else {
                        clearInterval(interval);
                        // Actually submit the form after animation
                        submitInstallationForm(this);
                    }
                }, 800);
            });
        }

        // Function to handle actual form submission
        function submitInstallationForm(form) {
            const formData = new FormData(form);
        
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())  // Changed from response.text()
            .then(data => {
                if (data.success) {
                    // Perform the redirect
                    window.location.href = data.redirect;
                } else {
                    throw new Error(data.message || 'Installation failed');
                }
            })
            .catch(error => {
                console.error('Installation error:', error);
                statusText.textContent = 'Installation failed. Please try again.';
                progressBar.style.backgroundColor = '#ef4444';
                
                // Show the form again after a brief delay
                setTimeout(() => {
                    progress.style.display = 'none';
                    installForm.style.display = 'block';
                }, 2000);
            });
        }

        // Navigation handling
        function switchPage(newState) {
            // Update URL without reload
            const url = new URL(window.location.href);
            url.searchParams.set('state', newState);
            window.history.pushState({}, '', url);
            
            // Update UI
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });
            document.querySelector(`[href="#${newState}"]`)?.classList.add('active');
            
            // Update content visibility
            document.querySelectorAll('.content-page').forEach(page => {
                page.classList.remove('active');
            });
            document.getElementById(`${newState}-content`)?.classList.add('active');

            return false;
        }

        // Make switchPage available globally
        window.switchPage = switchPage;

        // Function to reinitialize scripts after content update
        function initializeScripts() {
            // Re-attach event listeners or initialize other functionality
            const newInstallForm = document.getElementById('installForm');
            if (newInstallForm) {
                newInstallForm.addEventListener('submit', handleInstallation);
            }
        }
    });

    // // Add a helper function to check if we need to reload after installation
    // function checkInstallationStatus() {
    //     const successMessage = document.querySelector('.success-message');
    //     if (successMessage) {
    //         // If we find a success message, reload after 2 seconds
    //         setTimeout(() => {
    //             const baseUrl = window.location.href.split('?')[0];
    //             window.location.href = baseUrl;
    //         }, 2000);
    //     }
    // }

    // // Run the check when the page loads
    // document.addEventListener('DOMContentLoaded', checkInstallationStatus);
    </script>
</body>
</html>