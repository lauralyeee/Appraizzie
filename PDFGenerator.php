<?php
require_once(__DIR__ . '/fpdf/fpdf.php');

class PerformanceReport extends FPDF {
    function Header() {
        // Purple header background - increased height for better centering
        $headerHeight = 30; // Adjust height as needed
        $this->SetFillColor(82, 72, 224);
        $this->Rect(0, 0, $this->GetPageWidth(), $headerHeight, 'F');
        
        // White text for the header
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 24);
        $this->SetXY(0, 5); // Adjust vertical positioning of the title as needed
        $this->Cell(0, 20, 'Performance Appraisal Report', 0, 1, 'C');
        
        // Reset text color for rest of the content
        $this->SetTextColor(0);
        $this->Ln(15);
        
     
    }

    function Footer() {
        // Position at 30mm from bottom to have space for logo
        $this->SetY(-30);
        
        // Draw separator line
        $this->SetDrawColor(200, 200, 200);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        
        // Add logo
       // $this->Image('company_logo.png', 10, $this->GetY() + 5, 30); // Adjust the width (30) as needed
        
        // Move to position for text (after the logo)
        $this->SetY($this->GetY() + 10);
        $this->SetX(45); // Adjust X position based on logo width
        
        // Set font for footer text
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(128, 128, 128);
        
        // Powered by text
        	$this->SetX(10);
        $this->Cell(100, 5, 'Powered by FusionETA', 0, 0, 'L');
        
        // Page number on right
        $this->SetX(-50);
        $this->Cell(40, 5, 'Page ' . $this->PageNo(), 0, 0, 'R');
    }
    // Add this method to your PerformanceReport class
    function RoundedRect($x, $y, $w, $h, $r, $style = '') {
        $k = $this->k;
        $hp = $this->h;
        if($style=='F')
            $op='f';
        elseif($style=='FD' || $style=='DF')
            $op='B';
        else
            $op='S';
        $MyArc = 4/3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m',($x+$r)*$k,($hp-$y)*$k ));
        $xc = $x+$w-$r ;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l', $xc*$k,($hp-$y)*$k ));

        $this->_Arc($xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);
        $xc = $x+$w-$r ;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',($x+$w)*$k,($hp-$yc)*$k));
        $this->_Arc($xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);
        $xc = $x+$r ;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',$xc*$k,($hp-($y+$h))*$k));
        $this->_Arc($xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);
        $xc = $x+$r ;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l',($x)*$k,($hp-$yc)*$k ));
        $this->_Arc($xc - $r, $yc - $r*$MyArc, $xc - $r*$MyArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }

    function _Arc($x1, $y1, $x2, $y2, $x3, $y3) {
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', 
            $x1*$this->k,
            ($h-$y1)*$this->k,
            $x2*$this->k,
            ($h-$y2)*$this->k,
            $x3*$this->k,
            ($h-$y3)*$this->k));
    }

    // Replace the ratings section with this modern version
    function addRatingsSection($revieweePercentage, $reviewerPercentage) {
        $this->AddSectionHeader('PERFORMANCE SUMMARY');
        
        // Rating boxes at proper position
        $startY = $this->GetY() + 55;
        $boxWidth = 80;
        $boxHeight = 45;
        $spacing = 18;

        // Rating scale legend first
        $this->SetFont('Arial', '', 14);
        $this->Cell(0, 10, 'Rating Scale Reference:', 0, 1);
        $this->SetFont('Arial', '', 12);
        $scales = [
            '5 - Distinguished Performance',
            '4 - Exceeds Expectation',
            '3 - Meets Expectation',
            '2 - Below Expectation',
            '1 - Not Adequate'
        ];
        foreach($scales as $scale) {
            $this->Cell(0, 5, $scale, 0, 1);
        }
        $this->Ln(10);

        // First column - Self Rating
        $this->SetFillColor(245, 245, 255);
        $this->RoundedRect(10, $startY, $boxWidth, $boxHeight, 3, 'F');
        
        $this->SetXY(10, $startY + 5);
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(82, 72, 224);
        $this->Cell($boxWidth, 8, 'Reviewee Self Rating', 0, 1, 'C');
        
        $this->SetXY(10, $startY + 20);
        $this->SetFont('Arial', 'B', 20);
        $this->Cell($boxWidth, 15, $revieweePercentage . '%', 0, 1, 'C');

        // Second column - Manager Rating
        $this->SetFillColor(245, 245, 255);
        $this->RoundedRect(10 + $boxWidth + $spacing, $startY, $boxWidth, $boxHeight, 3, 'F');
        
        $this->SetXY(10 + $boxWidth + $spacing, $startY + 5);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell($boxWidth, 8, 'Managers Rating', 0, 1, 'C');
        
        $this->SetXY(10 + $boxWidth + $spacing, $startY + 20);
        $this->SetFont('Arial', 'B', 20);
        $this->Cell($boxWidth, 15, $reviewerPercentage . '%', 0, 1, 'C');

        // Reset text color and add space after ratings
        $this->SetTextColor(0);
        $this->SetY($startY + $boxHeight + 25);
    }

    private function cleanText($text) {
        $search = array(
            'â€™',
            'â€œ',
            'â€',
            'â€"'
        );
        $replace = array(
            "'",
            '"',
            "'",
            '-'
        );
        return str_replace($search, $replace, $text);
    }

    function createTable($headers, $data) {
        // Modern table styling
        $this->SetFillColor(82, 72, 224); // Header background - theme purple
        $this->SetTextColor(255, 255, 255);
        $this->SetDrawColor(230, 230, 240); // Light gray for borders
        $this->SetLineWidth(0.3);
        $this->SetFont('Arial', 'B', 10);
    
        // Column widths (adjust as needed)
        $w = array(20, 27, 27, 110);
        
        // Headers - use MultiCell for wrapping text
        $x = $this->GetX();
        $y = $this->GetY();
        for ($i = 0; $i < count($headers); $i++) {
            $this->SetXY($x, $y);
            $this->MultiCell($w[$i], 5, $headers[$i], 1, 'C', true);
            $x += $w[$i];
        }
        $this->Ln(10);
    
        // Data rows
        $this->SetFillColor(245, 245, 255);
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 9);
        
        $fill = false;
        foreach($data as $row) {
            $maxHeight = 6;
            $nb = 0;
            
            // Modified height calculation
            for($i = 0; $i < count($row); $i++) {
                if ($i === 3 && is_array($row[$i])) {
                    // Calculate height for comments
                    $totalLines = 0;
                    for($j = 0; $j < count($row[$i]['labels']); $j++) {
                        // Calculate lines needed for content
                        $label = $row[$i]['labels'][$j] . ' ';
                        $labelWidth = $this->GetStringWidth($label);
                        $remainingWidth = $w[$i] - $labelWidth;
                        $content = $row[$i]['content'][$j];
                        
                        // Get number of lines for this content
                        $lines = $this->NbLines($remainingWidth, $content);
                        $totalLines += $lines + 1; // Add 1 for spacing between comments
                    }
                    $nb = max($nb, $totalLines);
                } else {
                    $nb = max($nb, $this->NbLines($w[$i], $row[$i]));
                }
            }
            $h = 6 * ($nb + 1); // Added extra padding
            
            // Check for page break
            $this->CheckPageBreak($h);
            
            // Print the row
            $x = $this->GetX();
            $y = $this->GetY();
            
            for($i = 0; $i < count($row); $i++) {
                $this->Rect($x, $y, $w[$i], $h, $fill ? 'F' : '');
                
                if ($i === 3 && is_array($row[$i])) {
                    // Handle comments column with proper text wrapping
                    $this->SetXY($x, $y);
                    $startX = $x;
                    
                    for($j = 0; $j < count($row[$i]['labels']); $j++) {
                        $this->SetXY($startX, $this->GetY());
                        
                        // Write label in bold
                        $this->SetFont('Arial', 'B', 9);
                        $label = $row[$i]['labels'][$j] . ' ';
                        $this->Write(6, $label);
                        
                        // Calculate remaining width for content
                        $labelWidth = $this->GetStringWidth($label);
                        $remainingWidth = $w[$i] - $labelWidth - 2; // Subtract 2 for margin
                        
                        // Write content with wrapping
                        $this->SetFont('Arial', '', 9);
                        $this->SetXY($startX + $labelWidth, $this->GetY());
                        $content = $this->cleanText($row[$i]['content'][$j]);
                        $this->MultiCell($remainingWidth, 6, $content, 0, 'L');
                        
                        // Add space between comments
                        if ($j < count($row[$i]['labels']) - 1) {
                            $this->Ln(2);
                        }
                    }
                } else {
                    $this->MultiCell($w[$i], 6, $this->cleanText($row[$i]), 'LR', 'L');
                }
                
                $x += $w[$i];
                $this->SetXY($x, $y);
            }
            
            $this->Ln($h);
            $fill = !$fill;
        }
        
        // Closing line
        $this->Cell(array_sum($w), 0, '', 'T');
    }
    function createTable2($headers, $data) {
        // Modern table styling
        $this->SetFillColor(82, 72, 224); // Header background - theme purple
        $this->SetTextColor(255, 255, 255);
        $this->SetDrawColor(230, 230, 240); // Light gray for borders
        $this->SetLineWidth(0.3);
        $this->SetFont('Arial', 'B', 10);

        // Column widths (adjust as needed)
        $w = array(30, 50, 50, 50);
        
        // Headers - use MultiCell for wrapping text
        $x = $this->GetX();
        $y = $this->GetY();
        for ($i = 0; $i < count($headers); $i++) {
            $this->SetXY($x, $y);
            $this->MultiCell($w[$i], 5, $headers[$i], 1, 'C', true); // Use MultiCell to allow text wrapping
            $x += $w[$i]; // Move X position for the next cell
        }
        $this->Ln(10); // Move down after the header row

        // Data rows
        $this->SetFillColor(245, 245, 255); // Light purple for alternate rows
        $this->SetTextColor(0);
        $this->SetFont('Arial', '', 9);
        
        $fill = false;
        foreach($data as $row) {
            $maxHeight = 6;
            $nb = 0;
            
            // Calculate required height
            for($i = 0; $i < count($row); $i++) {
                $nb = max($nb, $this->NbLines($w[$i], $row[$i]));
            }
            $h = 6 * $nb;
            
            // Check for page break
            $this->CheckPageBreak($h);
            
            // Print the row
            $x = $this->GetX();
            $y = $this->GetY();
            
            for($i = 0; $i < count($row); $i++) {
                $this->Rect($x, $y, $w[$i], $h, $fill ? 'F' : '');
                $this->MultiCell($w[$i], 6, $this->cleanText($row[$i]), 'LR', 'L');
                $x += $w[$i];
                $this->SetXY($x, $y);
            }
            
            $this->Ln($h);
            $fill = !$fill;
        }
        
        // Closing line
        $this->Cell(array_sum($w), 0, '', 'T');
    }

    // Section headers (add this new method)
    function AddSectionHeader($title) {
        $this->Ln(10);
        $this->SetFillColor(82, 72, 224);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, '  ' . $title, 0, 1, 'L', true);
        $this->SetTextColor(0);
        $this->Ln(5);
    }

   

    // Add these helper functions to the PerformanceReport class
    function NbLines($w, $txt) {
        // Compute number of lines a MultiCell of width w will take
        if(!isset($txt) || $txt == '') return 1;
        
        $cw = &$this->CurrentFont['cw'];
        if($w == 0) $w = $this->w - $this->rMargin - $this->x;
        
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', (string)$txt);
        $nb = strlen($s);
        if($nb > 0 && $s[$nb - 1] == "\n") $nb--;
        
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        
        while($i < $nb) {
            $c = $s[$i];
            if($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if($c == ' ') $sep = $i;
            
            $l += $cw[$c] ?? 0;
            if($l > $wmax) {
                if($sep == -1) {
                    if($i == $j) $i++;
                } else $i = $sep + 1;
                
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else $i++;
        }
        return $nl;
    }

    function CheckPageBreak($h) {
        // If the height h would cause an overflow, add a new page immediately
        if($this->GetY() + $h > $this->PageBreakTrigger)
            $this->AddPage($this->CurOrientation);
    }

 
}

function generatePDFReport($formCustomFields) {
    $pdf = new PerformanceReport();
    $pdf->AddPage();
    
       // Employee Information header
       $pdf->SetFont('Arial', 'B', 14);
       $pdf->SetFillColor(245, 245, 255); // Light purple background
       $pdf->Cell(0, 10, 'EMPLOYEE INFORMATION', 0, 1, 'L');
    // Employee Details
    $pdf->SetFont('Arial', '', 12);
    $details = array(
        array('Employee Name', $formCustomFields['REVIEWEE']),
        array('Department', $formCustomFields['TEAM']),
        array('Review Period', $formCustomFields['YEAR']),
        array('Date of Report', date('F d, Y')),
    );
    
    foreach($details as $detail) {
        $pdf->Cell(40, 8, $detail[0], 0);
        $pdf->Cell(60, 8, $detail[1], 0);
        $pdf->Ln();
    }
    
    // Performance Summary Section
    //$pdf->AddSectionHeader('PERFORMANCE SUMMARY');
    

    // Calculate ratings
    $revieweePercentage = calculateOverallRating($formCustomFields, 'REVIEWEE');
    $reviewerPercentage = calculateOverallRating($formCustomFields, 'REVIEWER');

    // Instead of the previous ratings code, just call:
    $pdf->addRatingsSection($revieweePercentage, $reviewerPercentage);

    // Reset text color
    $pdf->SetTextColor(0);
    $pdf->Ln(45);
    
    // Questions Section
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetFillColor(25, 40, 130);
    $pdf->SetTextColor(255);
    $pdf->Cell(0, 8, 'DETAILED ASSESSMENT', 0, 1, 'L', true);
    $pdf->SetTextColor(0);
    $pdf->Ln(5);
    
    // Create questions table
    $headers = array('Question', 'Reviewee Rating', 'Reviewer / Partner Rating', 'Comments');
    $questionData = array();
    $questionNumber = 1;
    while (isset($formCustomFields["QUESTION_{$questionNumber}_REVIEWEE_RATING"]) || 
        isset($formCustomFields["QUESTION_{$questionNumber}_REVIEWER_RATING"]) || 
        isset($formCustomFields["QUESTION_{$questionNumber}_REVIEWEE_COMMENT"]) || 
        isset($formCustomFields["QUESTION_{$questionNumber}_REVIEWER_COMMENT"]) || 
        isset($formCustomFields["QUESTION_{$questionNumber}_PARTNER_COMMENT"])) {
        
         // Store label and content separately to handle formatting in createTable
    $commentArray = array(
        'labels' => array('Reviewee:', 'Reviewer:', 'Partner:'),
        'content' => array(
            ($formCustomFields["QUESTION_{$questionNumber}_REVIEWEE_COMMENT"] ?? '-'),
            ($formCustomFields["QUESTION_{$questionNumber}_REVIEWER_COMMENT"] ?? '-'),
            ($formCustomFields["QUESTION_{$questionNumber}_PARTNER_COMMENT"] ?? '-')
        )
    );
        
    $questionData[] = array(
    "Q$questionNumber",
        $formCustomFields["QUESTION_{$questionNumber}_REVIEWEE_RATING"] ?? '-',
        $formCustomFields["QUESTION_{$questionNumber}_REVIEWER_RATING"] ?? '-',
        array(
            'labels' => array('Reviewee:', 'Reviewer:', 'Partner:'),
            'content' => array(
                ($formCustomFields["QUESTION_{$questionNumber}_REVIEWEE_COMMENT"] ?? '-'),
                ($formCustomFields["QUESTION_{$questionNumber}_REVIEWER_COMMENT"] ?? '-'),
                ($formCustomFields["QUESTION_{$questionNumber}_PARTNER_COMMENT"] ?? '-')
            )
        )
    );
        
        $questionNumber++;
    }

    $pdf->createTable($headers, $questionData);
    
    // Additional Comments Section
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetFillColor(25, 40, 130);
    $pdf->SetTextColor(255);
    $pdf->Cell(0, 8, 'ADDITIONAL COMMENTS', 0, 1, 'L', true);
    $pdf->SetTextColor(0);
    $pdf->Ln(5);
    
    $commentHeaders = array('Section', 'Reviewee', 'Manager', 'Partner');
    $commentData = array(
        array(
            'Goals Review',
            $formCustomFields['GOALS_REVIEW_REVIEWEE_COMMENT'] ?? '',
            $formCustomFields['GOALS_REVIEW_REVIEWER_COMMENT'] ?? '',
            $formCustomFields['GOALS_REVIEW_PARTNER_COMMENT'] ?? ''
        ),
        array(
            'Overall Remarks',
            $formCustomFields['OVERALL_REMARKS_REVIEWEE_COMMENT'] ?? '',
            $formCustomFields['OVERALL_REMARKS_REVIEWER_COMMENT'] ?? '',
            $formCustomFields['OVERALL_REMARKS_PARTNER_COMMENT'] ?? ''
        ),
        array(
            'Development Plans',
            $formCustomFields['DEVELOPMENT_PLANS_REVIEWEE_COMMENT'] ?? '',
            $formCustomFields['DEVELOPMENT_PLANS_REVIEWER_COMMENT'] ?? '',
            $formCustomFields['DEVELOPMENT_PLANS_PARTNER_COMMENT'] ?? ''
        )
    );
    
    $pdf->createTable2($commentHeaders, $commentData);
    

   // Generate PDF content as string
    $pdfContent = $pdf->Output('S');

    // Save to local directory (adjust the path as needed)
    file_put_contents(__DIR__ . '/employee_report.pdf', $pdfContent);

    // Return the PDF content as string
    return $pdfContent;

}

// Helper function to calculate overall rating
function calculateOverallRating($formCustomFields, $role) {
    $totalRating = 0;
    $count = 0;
    $questionNumber = 1;
    
    while (isset($formCustomFields["QUESTION_{$questionNumber}_{$role}_RATING"])) {
        $rating = $formCustomFields["QUESTION_{$questionNumber}_{$role}_RATING"];
        if ($rating !== '') {
            $totalRating += (int)$rating;
            $count++;
        }
        $questionNumber++;
    }
    
    return $count > 0 ? round(($totalRating / ($count * 5)) * 100) : 0;
}


?>