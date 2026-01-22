<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Dashboard</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Modern CSS -->
    <link rel="stylesheet" href="assets/style-modern.css">
</head>
<body>

<div class="dashboard-container">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <i class="bi bi-backpack"></i><br>
            Student
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="studenthome.php" class="<?php echo $currentPage == 'studenthome.php' ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="student_courses.php" class="<?php echo $currentPage == 'student_courses.php' ? 'active' : ''; ?>">
                    <i class="bi bi-book-half"></i> My Courses
                </a>
            </li>
            <li>
                <a href="student_results.php" class="<?php echo $currentPage == 'student_results.php' ? 'active' : ''; ?>">
                    <i class="bi bi-bar-chart-line"></i> My Results
                </a>
            </li>
            <li>
                <a href="student_transcript.php" class="<?php echo $currentPage == 'student_transcript.php' ? 'active' : ''; ?>">
                    <i class="bi bi-mortarboard"></i> My Transcript
                </a>
            </li>
            <li>
                <a href="student_profile.php" class="<?php echo $currentPage == 'student_profile.php' ? 'active' : ''; ?>">
                    <i class="bi bi-person-circle"></i> My Profile
                </a>
            </li>
        </ul>
    </aside>

    <!-- NAVBAR & CONTENT WRAPPER -->
    <div style="flex: 1; display: flex; flex-direction: column;">
        <?php include 'navbar.php'; ?>
        
        <!-- MAIN CONTENT -->
        <main class="main-content">
