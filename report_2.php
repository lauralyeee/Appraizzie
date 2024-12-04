<?php

session_start();
define('INSTANCE_SETTINGS_DIR', __DIR__ . '/instance_settings');
require_once(__DIR__ . '/cextrest.php');

$instanceId = isset($_GET['instanceId']) ? $_GET['instanceId'] : '';

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
        $spaIDs = array_keys($settings['spas']);
        $spaID = $spaIDs[0];
        $entityTypeID = $settings['spas'][$spaID]['entityTypeId'];
        $restMemberID = $settings['spas'][$spaID]['rest_member_id'];
        
        return [
            'spaID' => $spaID,
            'entityTypeID' => $entityTypeID,
            'restMemberID' => $restMemberID
        ];
    }
    
    throw new Exception("No SPA ID found in settings");
}

try {
    CRestExt::setCurrentBitrix24($instanceId);
    $spaData = getSpaIdFromSettingsForm($instanceId);

    // Access the SPA ID and entityTypeID
    $spaID = $spaData['spaID'];
    $entityTypeID = $spaData['entityTypeID'];
    $restMemberID = $spaData['restMemberID'];
    
    //echo "SPA ID: " . $spaID . "<br>";
    //echo "Entity Type ID: " . $entityTypeID . "<br>";
    //echo "Rest Member ID: " . $restMemberID . "<br>";

    $YearField = "ufCrm".$spaID."Year";
    $RevieweeField = "ufCrm".$spaID."RevieweeRatingScore";
    $ReviewerField = "ufCrm".$spaID."ReviewerRatingScore";
    $TotalAverageField = "ufCrm".$spaID."TotalAverageRatingScore";
    $RevieweeIDField = "ufCrm".$spaID."Reviewee";
    $TeamField = "ufCrm".$spaID."Team";
    $RoleField = "ufCrm".$spaID."Role";


        
    $result =  CRestExt::call(
                'crm.item.list',
                    [
                        'entityTypeId' => $entityTypeID ,// From request
                        'select' => ['id', 'stageId', $YearField, $RevieweeIDField, $TeamField, $RoleField, $RevieweeField, $ReviewerField, $TotalAverageField]
                    ]
                );
        
    //echo "crm.item.list Response:<br>";
    //echo nl2br(json_encode($result, JSON_PRETTY_PRINT));


} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}


//data process
function processApiData($result, $spaID) {
    $data = [
        'ratings' => [
            'reviewee' => ['sum' => 0, 'count' => 0],
            'reviewer' => ['sum' => 0, 'count' => 0],
            'total' => ['sum' => 0, 'count' => 0]
        ],
        'status_counts' => [
            'Initialised' => 0,
            'Reviewee Pending' => 0,
            'Reviewer Pending' => 0,
            'Partner Pending' => 0,
            'Submitted' => 0
        ]
    ];

    // Define field names using $spaID
    $revieweeRatingField = "ufCrm{$spaID}RevieweeRatingScore";
    $reviewerRatingField = "ufCrm{$spaID}ReviewerRatingScore";
    $totalAverageField = "ufCrm{$spaID}TotalAverageRatingScore";

    // Process each item from the API response
    foreach ($result['result']['items'] as $item) {
        // Count statuses based on stageId
        $stageId = strtoupper($item['stageId']); // Convert to uppercase for consistent comparison
        
        if (strpos($stageId, 'SUBMITTED') !== false) {
            $data['status_counts']['Submitted']++;
        }
        elseif (strpos($stageId, 'INITIALIZED') !== false) {
            $data['status_counts']['Initialised']++;
        }
        elseif (strpos($stageId, 'REVIEWEEPENDING') !== false) {
            $data['status_counts']['Reviewee Pending']++;
        }
        elseif (strpos($stageId, 'REVIEWERPENDING') !== false) {
            $data['status_counts']['Reviewer Pending']++;
        }
        elseif (strpos($stageId, 'PARTNERPENDING') !== false) {
            $data['status_counts']['Partner Pending']++;
        }

        // Calculate Reviewee Rating
        if (!empty($item[$revieweeRatingField])) {
            $data['ratings']['reviewee']['sum'] += floatval($item[$revieweeRatingField]);
            $data['ratings']['reviewee']['count']++;
        }
        
        // Calculate Reviewer Rating
        if (!empty($item[$reviewerRatingField])) {
            $data['ratings']['reviewer']['sum'] += floatval($item[$reviewerRatingField]);
            $data['ratings']['reviewer']['count']++;
        }
        
        // Calculate Total/Partner Rating
        if (!empty($item[$totalAverageField])) {
            $data['ratings']['total']['sum'] += floatval($item[$totalAverageField]);
            $data['ratings']['total']['count']++;
        }
    }

    // Calculate final averages
    $data['final_averages'] = [
        'reviewee' => $data['ratings']['reviewee']['count'] > 0 
            ? number_format($data['ratings']['reviewee']['sum'] / $data['ratings']['reviewee']['count'], 2, '.', '') 
            : '0.00',
        'reviewer' => $data['ratings']['reviewer']['count'] > 0 
            ? number_format($data['ratings']['reviewer']['sum'] / $data['ratings']['reviewer']['count'], 2, '.', '') 
            : '0.00',
        'partner' => $data['ratings']['total']['count'] > 0 
            ? number_format($data['ratings']['total']['sum'] / $data['ratings']['total']['count'], 2, '.', '') 
            : '0.00'
    ];

    return $data;
}

// Example usage with both ratings and status cards:
$processedData = processApiData($result, $spaID);

function getTopAndBottomPerformers($result, $spaID) {
    $performers = [];
    $totalAverageField = "ufCrm{$spaID}TotalAverageRatingScore";
    $revieweeIDField = "ufCrm{$spaID}Reviewee";
    
    // Collect all valid entries
    foreach ($result['result']['items'] as $item) {
        
        //name
        $RevieweeName =  CRestExt::call(
                     'user.get',
                    [
                        'ID' => $item[$revieweeIDField]
                    ]
                );
        echo "hh";
        echo nl2br(json_encode($RevieweeName, JSON_PRETTY_PRINT));
        //name
        
        if (!empty($item[$totalAverageField]) && !empty($item[$revieweeIDField])) {
            $performers[] = [
                'name' => $item[$revieweeIDField],
                'score' => $item[$totalAverageField] // Keep original value without converting to float
            ];
        }
    }
    
    // Sort by score (descending for top performers)
    usort($performers, function($a, $b) {
        return $b['score'] <=> $a['score'];
    });
    
    // Get top 5 and bottom 5
    $topFive = array_slice($performers, 0, 5);
    $bottomFive = array_slice($performers, -5);
    // Reverse bottom five to show lowest first
    $bottomFive = array_reverse($bottomFive);
    
    return [
        'top' => $topFive,
        'bottom' => $bottomFive
    ];
}

// Get the rankings
$rankings = getTopAndBottomPerformers($result, $spaID);
//data process

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Appraisal Report</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            color: #1a1a1a;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .header {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            padding: 3rem 2rem;
            border-radius: 16px;
            margin-bottom: 2.5rem;
            text-align: center;
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.1);
        }
        
        .header h1 {
            font-size: 2.5rem;
            color: white;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }
        
        .header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .filter-section {
            display: flex;
            gap: 1rem;
            margin: 20px 0;
        }
        
        .filter-select {
            padding: 8px 12px;
            font-size: 14px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #fff;
            color: #666;
            min-width: 200px;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 12px;
            padding-right: 32px;
        }

        .filter-select:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 1px rgba(99, 102, 241, 0.1);
        }
        
        .status-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .card {
            padding: 1.5rem;
            border-radius: 16px;
            text-align: center;
            color: white;
            transition: transform 0.2s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .card p {
            font-size: 1rem;
            font-weight: 500;
            opacity: 0.9;
        }
        
        /* Updated gradient colors */
        .status-cards .card:nth-child(1) { 
            background: linear-gradient(135deg, #2563eb, #1e40af); /* Purple gradient for Initialised */
        }
        .status-cards .card:nth-child(2) { 
            background: linear-gradient(135deg, #6f42c1, #3a006f); /* Violet-purple gradient for Reviewee Pending */
        }
        .status-cards .card:nth-child(3) { 
            background: linear-gradient(135deg, #00CC66, #009933); /* Green gradient for Reviewer Pending */
        }
        .status-cards .card:nth-child(4) { 
            background: linear-gradient(135deg, #FFA500, #FF4500); /* Orange gradient for Partner Pending */
        }
        .status-cards .card:nth-child(5) { 
            background: linear-gradient(135deg, #FF3366, #CC0033); /* Pink-red gradient for Submitted */
        }
        
        .ratings {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .rating-item {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            text-align: center;
        }
        
        .rating-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1rem;
        }
        
        .rating-score {
            font-size: 3.5rem;
            font-weight: 700;
            line-height: 1;
        }
        
        .rating-max {
            font-size: 2rem;
            color: #9ca3af;
            font-weight: 500;
        }
        
        .reviewee-score { color: #6C5CE7; }  /* Purple */
        .reviewer-score { color: #27AE60; }  /* Green */
        .partner-score { color: #F39C12; }   /* Orange */
        
        .rating-item:hover {
            transform: translateY(-5px);
        }
        
        .rating-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            gap: 1rem;
        }
        
        .rating-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .rating-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
        }
        
        .rating-stats {
            display: grid;
            gap: 1rem;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: #f8fafc;
            border-radius: 8px;
        }
        
        .stat-label {
            font-weight: 500;
            color: #4b5563;
        }
        
        .stat-value {
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .blue { color: #2563eb; }
        .purple { color: #7c3aed; }
        .green { color: #059669; }
        
        .top-bottom-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }
        
        .employee-list {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .employee-list h2 {
            font-size: 1.5rem;
            color: #1f2937;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        
        .employee-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            background: #f8fafc;
        }
        
        .employee-item span {
            font-weight: 500;
        }
        
        .employee-item .score {
            margin-left: auto;
            font-weight: 600;
            color: #2563eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Performance Review Portal</h1>
            <p>Empower Growth Through Meaningful Feedback</p>
        </div>

        <div class="filter-section">
        <select class="filter-select" name="team" id="team">
            <option value="" disabled selected>Select Team</option>
            <option value="it-team">IT & Operation Team</option>
            <option value="marketing">Marketing Team</option>
            <option value="sales">Sales Team</option>
        </select>
        <select class="filter-select" name="role" id="role">
            <option value="" disabled selected>Select Role</option>
            <option value="manager">Manager</option>
            <option value="developer">Developer</option>
            <option value="designer">Designer</option>
        </select>
        </div>

        <div class="status-cards">
            <div class="card red-gradient">
                <h3><?php echo $processedData['status_counts']['Initialised']; ?></h3>
                <p>Initialised</p>
            </div>
            <div class="card orange-gradient">
                <h3><?php echo $processedData['status_counts']['Reviewee Pending']; ?></h3>
                <p>Reviewee Pending</p>
            </div>
            <div class="card yellow-gradient">
                <h3><?php echo $processedData['status_counts']['Reviewer Pending']; ?></h3>
                <p>Reviewer Pending</p>
            </div>
            <div class="card blue-gradient">
                <h3><?php echo $processedData['status_counts']['Partner Pending']; ?></h3>
                <p>Partner Pending</p>
            </div>
            <div class="card green-gradient">
                <h3><?php echo $processedData['status_counts']['Submitted']; ?></h3>
                <p>Submitted</p>
            </div>
        </div>

        <div class="ratings">
            <div class="rating-item">
                <div class="rating-title">Reviewee Rating</div>
                <div class="rating-score">
                    <span class="reviewee-score"><?php echo $processedData['final_averages']['reviewee']; ?></span>
                    <span class="rating-max">/5</span>
                </div>
            </div>

            <div class="rating-item">
                <div class="rating-title">Reviewer Rating</div>
                <div class="rating-score">
                    <span class="reviewer-score"><?php echo $processedData['final_averages']['reviewer']; ?></span>
                    <span class="rating-max">/5</span>
                </div>
            </div>

            <div class="rating-item">
                <div class="rating-title">Average Rating</div>
                <div class="rating-score">
                    <span class="partner-score"><?php echo $processedData['final_averages']['partner']; ?></span>
                    <span class="rating-max">/5</span>
                </div>
            </div>
        </div>

        <div class="top-bottom-section">
            <!-- Top 5 Employees Section -->
<div class="employee-list">
    <h2>Top 5 Employees</h2>
    <?php foreach ($rankings['top'] as $employee): ?>
    <div class="employee-item">
        <span><?php echo htmlspecialchars($employee['name']); ?></span>
        <span class="score"><?php echo number_format($employee['score'], 2, '.', ''); ?></span>
    </div>
    <?php endforeach; ?>
</div>

<!-- Bottom 5 Employees Section -->
<div class="employee-list">
    <h2>Bottom 5 Employees</h2>
    <?php foreach ($rankings['bottom'] as $employee): ?>
    <div class="employee-item">
        <span><?php echo htmlspecialchars($employee['name']); ?></span>
        <span class="score"><?php echo number_format($employee['score'], 2, '.', ''); ?></span>
    </div>
    <?php endforeach; ?>
</div>
        </div>
    </div>
</body>
</html>
