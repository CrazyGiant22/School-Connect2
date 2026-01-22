<?php
session_start();
include 'includes/connect.php';
include 'includes/session-helpers.php';

// Require admin login
requireRole('admin');

// Get statistics - with error handling
$students_count = 0;
$lecturers_count = 0;
$courses_count = 0;
$admissions_count = 0;

$sql_students = "SELECT COUNT(*) as count FROM user WHERE usertype='student'";
$result_students = mysqli_query($data, $sql_students);
if ($result_students) {
    $row = mysqli_fetch_assoc($result_students);
    $students_count = $row['count'] ?? 0;
}

$sql_lecturers = "SELECT COUNT(*) as count FROM user WHERE usertype='lecturer'";
$result_lecturers = mysqli_query($data, $sql_lecturers);
if ($result_lecturers) {
    $row = mysqli_fetch_assoc($result_lecturers);
    $lecturers_count = $row['count'] ?? 0;
}

$sql_courses = "SELECT COUNT(*) as count FROM course";
$result_courses = mysqli_query($data, $sql_courses);
if ($result_courses) {
    $row = mysqli_fetch_assoc($result_courses);
    $courses_count = $row['count'] ?? 0;
}

// Only count pending admissions (status = 'pending')
$sql_admissions = "SELECT COUNT(*) as count FROM admission WHERE status='pending'";
$result_admissions = mysqli_query($data, $sql_admissions);
if ($result_admissions) {
    $row = mysqli_fetch_assoc($result_admissions);
    $admissions_count = $row['count'] ?? 0;
}
?>

<?php include 'includes/admin-sidebar.php'; ?>

            <!-- Dashboard Header -->
            <div class="page-title">Dashboard</div>
            <div class="page-subtitle">Welcome to your admin panel. Here's an overview of your system.</div>

            <!-- Statistics Cards -->
            <div class="grid-cols-4">
                <div class="stat-card">
                    <div class="stat-card-icon primary">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stat-card-content">
                        <h6>Total Students</h6>
                        <h3><?php echo $students_count; ?></h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-icon success">
                        <i class="bi bi-person-badge"></i>
                    </div>
                    <div class="stat-card-content">
                        <h6>Total Lecturers</h6>
                        <h3><?php echo $lecturers_count; ?></h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-icon warning">
                        <i class="bi bi-book"></i>
                    </div>
                    <div class="stat-card-content">
                        <h6>Total Courses</h6>
                        <h3><?php echo $courses_count; ?></h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-icon danger">
                        <i class="bi bi-clipboard-check"></i>
                    </div>
                    <div class="stat-card-content">
                        <h6>Pending Admissions</h6>
                        <h3><?php echo $admissions_count; ?></h3>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div style="margin-top: 3rem;">
                <h4 class="section-title">Quick Actions</h4>
                <div class="btn-group-modern">
                    <a href="add_student.php" class="btn-modern primary">
                        <i class="bi bi-person-plus"></i> Add Student
                    </a>
                    <a href="admin_add_lecturer.php" class="btn-modern success">
                        <i class="bi bi-briefcase"></i> Add Lecturer
                    </a>
                    <a href="add_course.php" class="btn-modern warning" style="color: purple;">
                        <i class="bi bi-plus-circle"></i> Add Course
                    </a>
                    <a href="view_student.php" class="btn-modern secondary">
                        <i class="bi bi-eye"></i> View Students
                    </a>
                </div>
            </div>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
