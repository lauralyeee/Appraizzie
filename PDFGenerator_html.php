<?php

require_once(__DIR__ . '/html2pdf/html2pdf.class.php');
class PerformanceReportHTML {
    private $template;
    private $formFields;

    public function __construct($formCustomFields) {
        $this->formFields = $formCustomFields;
        $this->template = '';
    }

    private function generateHeader() {
        return '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Performance Review Report</title>
            <style>
                :root {
                    --primary-color: rgb(82, 72, 224);
                    --light-purple: rgb(245, 245, 255);
                    --gray-border: #e6e6f0;
                }
                
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    margin: 0;
                    padding: 20px;
                    color: #333;
                }

                .report-header {
                    background: var(--primary-color);
                    color: white;
                    padding: 20px;
                    text-align: center;
                    margin-bottom: 30px;
                }

                .section {
                    margin-bottom: 30px;
                }

                .section-header {
                    background: var(--primary-color);
                    color: white;
                    padding: 10px;
                    margin-bottom: 15px;
                }

                .employee-info {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                    gap: 20px;
                    padding: 20px;
                    background: var(--light-purple);
                    border-radius: 8px;
                }

                .rating-boxes {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 20px;
                    margin: 20px 0;
                }

                .rating-box {
                    background: var(--light-purple);
                    padding: 20px;
                    border-radius: 8px;
                    text-align: center;
                }

                .rating-value {
                    font-size: 2em;
                    color: var(--primary-color);
                    font-weight: bold;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                }

                th {
                    background: var(--primary-color);
                    color: white;
                    padding: 12px;
                    text-align: left;
                }

                td {
                    padding: 12px;
                    border: 1px solid var(--gray-border);
                }

                tr:nth-child(even) {
                    background: var(--light-purple);
                }

                .comments-section {
                    background: white;
                    padding: 20px;
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }

                @media print {
                    body {
                        padding: 0;
                    }
                    
                    .report-header {
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                    
                    .section {
                        break-inside: avoid;
                    }
                }
            </style>
        </head>
        <body>
            <div class="report-header">
                <h1>Performance Review Report</h1>
            </div>';
    }

    private function generateEmployeeInfo() {
        return '
        <div class="section">
            <h2>Employee Information</h2>
            <div class="employee-info">
                <div>
                    <strong>Employee Name:</strong> ' . htmlspecialchars($this->formFields['REVIEWEE']) . '
                </div>
                <div>
                    <strong>Department:</strong> ' . htmlspecialchars($this->formFields['TEAM']) . '
                </div>
                <div>
                    <strong>Review Period:</strong> ' . htmlspecialchars($this->formFields['YEAR']) . '
                </div>
                <div>
                    <strong>Date of Report:</strong> ' . date('F d, Y') . '
                </div>
            </div>
        </div>';
    }

    private function generateRatingsSection() {
        $revieweePercentage = $this->calculateOverallRating('REVIEWEE');
        $reviewerPercentage = $this->calculateOverallRating('REVIEWER');

        return '
        <div class="section">
            <h2 class="section-header">Performance Summary</h2>
            <div class="rating-boxes">
                <div class="rating-box">
                    <h3>Reviewee Self Rating</h3>
                    <div class="rating-value">' . $revieweePercentage . '%</div>
                </div>
                <div class="rating-box">
                    <h3>Managers Rating</h3>
                    <div class="rating-value">' . $reviewerPercentage . '%</div>
                </div>
            </div>
            <div class="rating-scale">
                <h3>Rating Scale Reference:</h3>
                <ul>
                    <li>5 - Distinguished Performance</li>
                    <li>4 - Exceeds Expectation</li>
                    <li>3 - Meets Expectation</li>
                    <li>2 - Below Expectation</li>
                    <li>1 - Not Adequate</li>
                </ul>
            </div>
        </div>';
    }

    private function generateQuestionsTable() {
        $html = '
        <div class="section">
            <h2 class="section-header">Detailed Assessment</h2>
            <table>
                <thead>
                    <tr>
                        <th>Question</th>
                        <th>Reviewee Rating</th>
                        <th>Reviewer Rating</th>
                        <th>Comments</th>
                    </tr>
                </thead>
                <tbody>';

        $questionNumber = 1;
        while (isset($this->formFields["QUESTION_{$questionNumber}_REVIEWEE_RATING"]) || 
               isset($this->formFields["QUESTION_{$questionNumber}_REVIEWER_RATING"])) {
            
            $comments = "Reviewee: " . htmlspecialchars($this->formFields["QUESTION_{$questionNumber}_REVIEWEE_COMMENT"] ?? '-') . "<br>";
            $comments .= "Reviewer: " . htmlspecialchars($this->formFields["QUESTION_{$questionNumber}_REVIEWER_COMMENT"] ?? '-') . "<br>";
            $comments .= "Partner: " . htmlspecialchars($this->formFields["QUESTION_{$questionNumber}_PARTNER_COMMENT"] ?? '-');

            $html .= "
                <tr>
                    <td>Q{$questionNumber}</td>
                    <td>" . htmlspecialchars($this->formFields["QUESTION_{$questionNumber}_REVIEWEE_RATING"] ?? '-') . "</td>
                    <td>" . htmlspecialchars($this->formFields["QUESTION_{$questionNumber}_REVIEWER_RATING"] ?? '-') . "</td>
                    <td>{$comments}</td>
                </tr>";
            
            $questionNumber++;
        }

        $html .= '
                </tbody>
            </table>
        </div>';

        return $html;
    }

    private function calculateOverallRating($role) {
        $totalRating = 0;
        $count = 0;
        $questionNumber = 1;
        
        while (isset($this->formFields["QUESTION_{$questionNumber}_{$role}_RATING"])) {
            $rating = $this->formFields["QUESTION_{$questionNumber}_{$role}_RATING"];
            if ($rating !== '') {
                $totalRating += (int)$rating;
                $count++;
            }
            $questionNumber++;
        }
        
        return $count > 0 ? round(($totalRating / ($count * 5)) * 100) : 0;
    }

    private function generateAdditionalComments() {
        return '
        <div class="section">
            <h2 class="section-header">Additional Comments</h2>
            <table>
                <thead>
                    <tr>
                        <th>Section</th>
                        <th>Reviewee</th>
                        <th>Manager</th>
                        <th>Partner</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Goals Review</td>
                        <td>' . htmlspecialchars($this->formFields['GOALS_REVIEW_REVIEWEE_COMMENT'] ?? '') . '</td>
                        <td>' . htmlspecialchars($this->formFields['GOALS_REVIEW_REVIEWER_COMMENT'] ?? '') . '</td>
                        <td>' . htmlspecialchars($this->formFields['GOALS_REVIEW_PARTNER_COMMENT'] ?? '') . '</td>
                    </tr>
                    <tr>
                        <td>Overall Remarks</td>
                        <td>' . htmlspecialchars($this->formFields['OVERALL_REMARKS_REVIEWEE_COMMENT'] ?? '') . '</td>
                        <td>' . htmlspecialchars($this->formFields['OVERALL_REMARKS_REVIEWER_COMMENT'] ?? '') . '</td>
                        <td>' . htmlspecialchars($this->formFields['OVERALL_REMARKS_PARTNER_COMMENT'] ?? '') . '</td>
                    </tr>
                    <tr>
                        <td>Development Plans</td>
                        <td>' . htmlspecialchars($this->formFields['DEVELOPMENT_PLANS_REVIEWEE_COMMENT'] ?? '') . '</td>
                        <td>' . htmlspecialchars($this->formFields['DEVELOPMENT_PLANS_REVIEWER_COMMENT'] ?? '') . '</td>
                        <td>' . htmlspecialchars($this->formFields['DEVELOPMENT_PLANS_PARTNER_COMMENT'] ?? '') . '</td>
                    </tr>
                </tbody>
            </table>
        </div>';
    }

    public function generateReport() {
        $this->template .= $this->generateHeader();
        $this->template .= $this->generateEmployeeInfo();
        $this->template .= $this->generateRatingsSection();
        $this->template .= $this->generateQuestionsTable();
        $this->template .= $this->generateAdditionalComments();
        $this->template .= '</body></html>';
        
        return $this->template;
    }
}

// Keep the original function name but update implementation
function generatePDFReport($formCustomFields) {
    // Create instance of HTML report generator
    $report = new PerformanceReportHTML($formCustomFields);
    
    // Generate HTML content
    $htmlContent = $report->generateReport();
    
    try {
        // Initialize HTML2PDF
        $pdf = new HTML2PDF('P', 'A4', 'en');
        
        // Set default font
        $pdf->setDefaultFont('Arial');
        
        // Write HTML content
        $pdf->writeHTML($htmlContent);
        
        // Generate PDF content as string
        $pdfContent = $pdf->Output('', true);
        
        // Optionally save locally
        file_put_contents(__DIR__ . '/employee_report.pdf', $pdfContent);
        
        return $pdfContent;
    } catch (HTML2PDF_exception $e) {
        // Handle any errors
        error_log('PDF Generation Error: ' . $e->getMessage());
        throw new Exception('Error generating PDF report');
    }
}