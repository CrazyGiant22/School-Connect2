<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lecturer Dashboard</title>
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
            <i class="bi bi-book"></i><br>
            Lecturer
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="lecturerhome.php" class="<?php echo $currentPage == 'lecturerhome.php' ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="lecturer_courses.php" class="<?php echo $currentPage == 'lecturer_courses.php' ? 'active' : ''; ?>">
                    <i class="bi bi-calendar-range"></i> My Courses
                </a>
            </li>
            <li>
                <a href="lecturer_ca.php" class="<?php echo $currentPage == 'lecturer_ca.php' ? 'active' : ''; ?>">
                    <i class="bi bi-clipboard2-check"></i> Continuous Assessment
                </a>
            </li>
            <li>
                <a href="lecturer_reports.php" class="<?php echo $currentPage == 'lecturer_reports.php' ? 'active' : ''; ?>">
                    <i class="bi bi-bar-chart-line"></i> Analytics
                </a>
            </li>
            <li>
                <a href="lecturer_profile.php" class="<?php echo $currentPage == 'lecturer_profile.php' ? 'active' : ''; ?>">
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
