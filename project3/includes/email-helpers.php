<?php
/**
 * Email Notification System
 * Save as: includes/email-helpers.php
 * 
 * Features:
 * - Send welcome emails to new users
 * - Notify students of new assessments
 * - Send grade notifications
 * - Password reset emails (future)
 */

/**
 * Email configuration
 * IMPORTANT: Configure these settings for your mail server
 */
define('MAIL_FROM', 'noreply@school-connect.edu');
define('MAIL_FROM_NAME', 'School-Connect System');
define('MAIL_REPLY_TO', 'support@school-connect.edu');

/**
 * Send email using PHP mail() function
 * For production, consider using PHPMailer or similar library
 */
function sendEmail($to, $subject, $htmlBody, $plainTextBody = '') {
    // Fallback to HTML if no plain text provided
    if (empty($plainTextBody)) {
        $plainTextBody = strip_tags($htmlBody);
    }
    
    // Email headers
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>',
        'Reply-To: ' . MAIL_REPLY_TO,
        'X-Mailer: PHP/' . phpversion()
    ];
    
    $headerString = implode("\r\n", $headers);
    
    // Send email
    $success = mail($to, $subject, $htmlBody, $headerString);
    
    // Log email attempt
    logEmailAttempt($to, $subject, $success);
    
    return $success;
}

/**
 * Email template wrapper
 */
function getEmailTemplate($title, $content, $footerText = '') {
    $year = date('Y');
    
    if (empty($footerText)) {
        $footerText = 'This is an automated message from School-Connect. Please do not reply to this email.';
    }
    
    return <<<HTML
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #0d6efd, #0b5ed7); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #ffffff; padding: 30px; border: 1px solid #dee2e6; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #6c757d; border-radius: 0 0 8px 8px; }
            .button { display: inline-block; padding: 12px 24px; background: #0d6efd; color: white; text-decoration: none; border-radius: 6px; margin: 10px 0; }
            .highlight { background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 15px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1 style="margin: 0;">🎓 School-Connect</h1>
                <p style="margin: 10px 0 0 0;">$title</p>
            </div>
            <div class="content">
                $content
            </div>
            <div class="footer">
                <p>$footerText</p>
                <p>&copy; $year School-Connect. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    HTML;
}

/**
 * Send welcome email to new student
 */
function sendStudentWelcomeEmail($email, $username, $password = '') {
    $subject = 'Welcome to School-Connect!';
    
    $passwordInfo = $password 
        ? "<div class='highlight'><strong>Your Temporary Password:</strong> $password<br><small>Please change this after your first login.</small></div>" 
        : '';
    
    $content = <<<HTML
    <h2>Welcome, $username!</h2>
    <p>Your student account has been successfully created at School-Connect.</p>
    
    $passwordInfo
    
    <p><strong>Your Login Details:</strong></p>
    <ul>
        <li>Username: <strong>$username</strong></li>
        <li>Login URL: <a href="http://localhost/project3/login.php">Student Portal</a></li>
    </ul>
    
    <p>You can now:</p>
    <ul>
        <li>View your enrolled courses</li>
        <li>Check your assessment results</li>
        <li>Update your profile</li>
        <li>View your academic transcript</li>
    </ul>
    
    <a href="http://localhost/project3/login.php" class="button">Login to Your Account</a>
    
    <p>If you have any questions, please contact your administrator.</p>
    HTML;
    
    $html = getEmailTemplate('Welcome to School-Connect', $content);
    
    return sendEmail($email, $subject, $html);
}

/**
 * Send welcome email to new lecturer
 */
function sendLecturerWelcomeEmail($email, $username, $password = '') {
    $subject = 'Welcome to School-Connect - Lecturer Account';
    
    $passwordInfo = $password 
        ? "<div class='highlight'><strong>Your Temporary Password:</strong> $password<br><small>Please change this after your first login.</small></div>" 
        : '';
    
    $content = <<<HTML
    <h2>Welcome, $username!</h2>
    <p>Your lecturer account has been successfully created at School-Connect.</p>
    
    $passwordInfo
    
    <p><strong>Your Login Details:</strong></p>
    <ul>
        <li>Username: <strong>$username</strong></li>
        <li>Login URL: <a href="http://localhost/project3/login.php">Lecturer Portal</a></li>
    </ul>
    
    <p>As a lecturer, you can:</p>
    <ul>
        <li>View your assigned courses</li>
        <li>Create and manage assessments</li>
        <li>Enter student grades</li>
        <li>View course analytics</li>
    </ul>
    
    <a href="http://localhost/project3/login.php" class="button">Login to Your Account</a>
    
    <p>If you have any questions, please contact the administrator.</p>
    HTML;
    
    $html = getEmailTemplate('Welcome to School-Connect', $content);
    
    return sendEmail($email, $subject, $html);
}

/**
 * Notify student of new assessment
 */
function sendNewAssessmentNotification($email, $studentName, $courseName, $assessmentTitle, $dueDate = '') {
    $subject = "New Assessment: $assessmentTitle";
    
    $dueDateInfo = $dueDate 
        ? "<p><strong>Due Date:</strong> $dueDate</p>" 
        : '';
    
    $content = <<<HTML
    <h2>New Assessment Available</h2>
    <p>Hello $studentName,</p>
    <p>A new assessment has been posted for your course:</p>
    
    <div class="highlight">
        <p><strong>Course:</strong> $courseName</p>
        <p><strong>Assessment:</strong> $assessmentTitle</p>
        $dueDateInfo
    </div>
    
    <p>Please log in to your student portal to view the details.</p>
    
    <a href="http://localhost/project3/student_results.php" class="button">View Assessment</a>
    HTML;
    
    $html = getEmailTemplate('New Assessment Posted', $content);
    
    return sendEmail($email, $subject, $html);
}

/**
 * Notify student of grade update
 */
function sendGradeUpdateNotification($email, $studentName, $courseName, $assessmentTitle, $score, $maxScore, $percentage) {
    $subject = "Grade Posted: $assessmentTitle";
    
    // Determine grade color
    if ($percentage >= 70) {
        $gradeColor = '#198754'; // Green
    } elseif ($percentage >= 50) {
        $gradeColor = '#ffc107'; // Yellow
    } else {
        $gradeColor = '#dc3545'; // Red
    }
    
    $content = <<<HTML
    <h2>Grade Posted</h2>
    <p>Hello $studentName,</p>
    <p>Your grade has been posted for:</p>
    
    <div class="highlight">
        <p><strong>Course:</strong> $courseName</p>
        <p><strong>Assessment:</strong> $assessmentTitle</p>
        <p><strong>Score:</strong> <span style="color: $gradeColor; font-size: 1.2em; font-weight: bold;">$score / $maxScore ($percentage%)</span></p>
    </div>
    
    <p>Log in to view your complete results and transcript.</p>
    
    <a href="http://localhost/project3/student_results.php" class="button">View Full Results</a>
    HTML;
    
    $html = getEmailTemplate('Grade Update', $content);
    
    return sendEmail($email, $subject, $html);
}

/**
 * Send at-risk student notification
 */
function sendAtRiskNotification($email, $studentName, $courseName, $currentPercentage) {
    $subject = "Academic Alert: $courseName";
    
    $content = <<<HTML
    <h2>Academic Performance Alert</h2>
    <p>Hello $studentName,</p>
    <p>This is a friendly reminder about your current performance in:</p>
    
    <div class="highlight">
        <p><strong>Course:</strong> $courseName</p>
        <p><strong>Current CA Score:</strong> <span style="color: #dc3545; font-weight: bold;">{$currentPercentage}%</span></p>
    </div>
    
    <p>Your current score is below the passing threshold. We encourage you to:</p>
    <ul>
        <li>Review course materials</li>
        <li>Attend study sessions</li>
        <li>Speak with your lecturer</li>
        <li>Visit the academic support center</li>
    </ul>
    
    <p>Remember, there's still time to improve your grade!</p>
    
    <a href="http://localhost/project3/student_results.php" class="button">View Your Results</a>
    HTML;
    
    $html = getEmailTemplate('Academic Performance Alert', $content);
    
    return sendEmail($email, $subject, $html);
}

/**
 * Log email attempts
 */
function logEmailAttempt($to, $subject, $success) {
    $logFile = __DIR__ . '/../logs/email.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'to' => $to,
        'subject' => $subject,
        'status' => $success ? 'sent' : 'failed'
    ];
    
    file_put_contents($logFile, json_encode($entry) . PHP_EOL, FILE_APPEND);
}

/**
 * Test email configuration
 */
function testEmailConfiguration($testEmail = 'test@example.com') {
    $subject = 'School-Connect Email Test';
    $content = '<h2>Email Configuration Test</h2><p>If you receive this email, your email configuration is working correctly!</p>';
    $html = getEmailTemplate('Email Test', $content);
    
    return sendEmail($testEmail, $subject, $html);
}