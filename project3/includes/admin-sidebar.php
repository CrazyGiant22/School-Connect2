<?php
// Get current page to set active link
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard</title>
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
            <i class="bi bi-shield-check"></i><br>
            Admin Panel
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="adminhome.php" class="<?php echo $currentPage == 'adminhome.php' ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="admission.php" class="<?php echo $currentPage == 'admission.php' ? 'active' : ''; ?>">
                    <i class="bi bi-clipboard-check"></i> Admissions
                </a>
            </li>
            <li>
                <a href="add_student.php" class="<?php echo $currentPage == 'add_student.php' ? 'active' : ''; ?>">
                    <i class="bi bi-person-plus"></i> Add Student
                </a>
            </li>
            <li>
                <a href="view_student.php" class="<?php echo $currentPage == 'view_student.php' ? 'active' : ''; ?>">
                    <i class="bi bi-people"></i> View Students
                </a>
            </li>
            <li>
                <a href="admin_add_lecturer.php" class="<?php echo $currentPage == 'admin_add_lecturer.php' ? 'active' : ''; ?>">
                    <i class="bi bi-briefcase"></i> Add Lecturer
                </a>
            </li>
            <li>
                <a href="admin_view_lecturer.php" class="<?php echo $currentPage == 'admin_view_lecturer.php' ? 'active' : ''; ?>">
                    <i class="bi bi-collection"></i> View Lecturers
                </a>
            </li>
            <li>
                <a href="add_course.php" class="<?php echo $currentPage == 'add_course.php' ? 'active' : ''; ?>">
                    <i class="bi bi-plus-circle"></i> Add Course
                </a>
            </li>
            <li>
                <a href="assign_course.php" class="<?php echo $currentPage == 'assign_course.php' ? 'active' : ''; ?>">
                    <i class="bi bi-link-45deg"></i> Assign Course
                </a>
            </li>
            <li>
                <a href="enroll_student.php" class="<?php echo $currentPage == 'enroll_student.php' ? 'active' : ''; ?>">
                    <i class="bi bi-bookmark"></i> Enroll Student
                </a>
            </li>
            <li>
                <a href="admin_terms.php" class="<?php echo $currentPage == 'admin_terms.php' ? 'active' : ''; ?>">
                    <i class="bi bi-calendar3"></i> Academic Terms
                </a>
            </li>
            <li>
                <a href="assign_student_term.php" class="<?php echo $currentPage == 'assign_student_term.php' ? 'active' : ''; ?>">
                    <i class="bi bi-people-fill"></i> Assign Student Terms
                </a>
            </li>
            <li>
                <a href="admin_reports.php" class="<?php echo $currentPage == 'admin_reports.php' ? 'active' : ''; ?>">
                    <i class="bi bi-bar-chart-line"></i> Reports &amp; Analytics
                </a>
            </li>
        </ul>
    </aside>

    <!-- NAVBAR & CONTENT WRAPPER -->
    <div style="flex: 1; display: flex; flex-direction: column;">
        <?php include 'navbar.php'; ?>
        
        <!-- MAIN CONTENT -->
        <main class="main-content">
