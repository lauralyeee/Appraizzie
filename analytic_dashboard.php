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
            position: relative;
            opacity: 0.85;
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

        .init-button:hover {
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
    <div class="form-init-container">
        <div class="form-init-card">
            <div class="card-header">
                <h2>Analytics Dashboard</h2>
                <p class="subtitle">Powerful Insights Coming Soon</p>
            </div>
            
            <div class="card-content">
                <p class="info-text">Get ready for an enhanced analytics experience. Our new dashboard will provide comprehensive insights into performance metrics, trends, and actionable data.</p>
                
                <div class="features-grid">
                    <div class="feature-item">
                        <span class="coming-soon-badge">Coming Soon</span>
                        <div class="lock-overlay">
                            <i class="fas fa-lock lock-icon"></i>
                        </div>
                        <i class="fas fa-chart-line feature-icon"></i>
                        <h3 class="feature-title">Performance Metrics</h3>
                        <p class="feature-description">Track key performance indicators and metrics in real-time</p>
                    </div>
                    
                    <div class="feature-item">
                        <span class="coming-soon-badge">Coming Soon</span>
                        <div class="lock-overlay">
                            <i class="fas fa-lock lock-icon"></i>
                        </div>
                        <i class="fas fa-chart-bar feature-icon"></i>
                        <h3 class="feature-title">Data Visualization</h3>
                        <p class="feature-description">Interactive charts and graphs for better data understanding</p>
                    </div>
                    
                    <div class="feature-item">
                        <span class="coming-soon-badge">Coming Soon</span>
                        <div class="lock-overlay">
                            <i class="fas fa-lock lock-icon"></i>
                        </div>
                        <i class="fas fa-lightbulb feature-icon"></i>
                        <h3 class="feature-title">Smart Insights</h3>
                        <p class="feature-description">AI-powered recommendations and trend analysis</p>
                    </div>
                </div>
                
                <p class="launch-date">Expected Launch: Q1 2024</p>
                
                <a href="#" class="init-button" onclick="return false;">
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
                        <a href="<?php echo $twitter_url; ?>" class="social-icon" target="_blank">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="<?php echo $linkedin_url; ?>" class="social-icon" target="_blank">
                            <i class="fab fa-linkedin"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>