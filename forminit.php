
<?php
//https://fusioneta.com.my/massmarket/employee_app/form.php?record=%s&instanceId=%s&spaId=%s&user=reviewee&team=General&role=General
// Ensure member_id is available - you might get this from your session or database
//$member_id = isset($_SESSION['member_id']) ? $_SESSION['member_id'] : '';

// form initialize = Yes
$form_url = "https://fusioneta.com.my/massmarket/employee_app/form.php?user=init&forminit=yes"; // Replace with your actual form page URL

// form initialize = Yes
$form_url2 = "fusioneta.com.my/massmarket/employee_app/form.php?user=reviewee&team=General&role=General"; // Replace with your actual form page URL
$member_id = $_REQUEST['member_id']; //dummy //demo bitrix member id
// Initialize form function
if (isset($_POST['initialize'])) {
    // You can add any additional initialization logic here
    //$redirect_url = $form_url . '?member_id=' . urlencode($member_id);
    header("Location: " . $form_url);
    exit();
}
    
?>

<!DOCTYPE html>
<html>
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /*.form-init-container {*/
        /*    padding: 2rem;*/
        /*    max-width: 900px;*/
        /*    width: 100%;*/
        /*    margin: 2rem auto;*/
        /*}*/

        .form-init-card-begin {
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 24px rgba(74, 107, 255, 0.12);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #4a6bff 0%, #2541b2 100%);
            padding: 3rem 2rem;
            text-align: center;
            position: relative;
        }

        .card-header::after {
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

        .card-header h2 {
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

        .card-content {
            padding: 3rem 2rem;
            text-align: center;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
            padding: 0 1rem;
        }

        .feature-item {
            padding: 1.5rem;
            border-radius: 12px;
            background: #f8f9ff;
            transition: all 0.3s ease;
        }

        .feature-item:hover {
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

        .init-button {
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
        }

        .init-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(74, 107, 255, 0.3);
            color: white;
        }

        .init-button i {
            margin-right: 0.75rem;
            font-size: 1.2rem;
        }

        .info-text {
            color: #566a7f;
            font-size: 1.1rem;
            line-height: 1.6;
            max-width: 600px;
            margin: 0 auto 2rem;
        }
    </style>
</head>
<body>

        <div class="form-init-card-begin">
            <div class="card-header">
                <h2>Performance Review Portal</h2>
                <p class="subtitle">Empower Growth Through Meaningful Feedback</p>
            </div>
            
            <div class="card-content">
                <p class="info-text">Welcome to your performance review platform. Start your evaluation process to track progress, set goals, and enhance professional development.</p>
                
                <div class="features-grid">
                    <!--<div class="feature-item">-->
                    <!--    <i class="fas fa-chart-line feature-icon"></i>-->
                    <!--    <h3 class="feature-title">Track Progress</h3>-->
                    <!--    <p class="feature-description">Monitor your professional growth and achievements throughout the year</p>-->
                    <!--</div>-->
                    
                    <div class="feature-item">
                        <i class="fas fa-bullseye feature-icon"></i>
                        <h3 class="feature-title">Set Goals</h3>
                        <p class="feature-description">Define clear objectives and milestones for your career development</p>
                    </div>
                    
                    <div class="feature-item">
                        <i class="fas fa-comments feature-icon"></i>
                        <h3 class="feature-title">Receive Feedback</h3>
                        <p class="feature-description">Get valuable insights from your supervisors and team leaders</p>
                    </div>
                </div>
                
                <a href="<?php echo $form_url . '&instanceId=' . urlencode($member_id); ?>" 
                   class="init-button">
                    <i class="fas fa-clipboard-check"></i>
                    Begin Performance Review
                </a>
            </div>
        </div>

</body>
</html>