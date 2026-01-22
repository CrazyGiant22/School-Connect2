<?php
session_start();
include 'includes/connect.php';
include 'includes/session-helpers.php';

// Require lecturer login
requireRole('lecturer');

$lecturer_name = $_SESSION['username'];

// Get lecturer's courses and students - with error handling
$courses_count = 0;
$students_count = 0;

$lecturer_id = getLecturerId($data);

if ($lecturer_id) {
    $lecturer_id_esc = mysqli_real_escape_string($data, (string)$lecturer_id);

    // Primary mapping: lecturer_course join table (used by assign_course.php, lecturer_courses.php)
    $sql_courses = "SELECT COUNT(DISTINCT lc.course_id) as count
                    FROM lecturer_course lc
                    WHERE lc.lecturer_id='$lecturer_id_esc'";
    $result_courses = mysqli_query($data, $sql_courses);

    if ($result_courses) {
        $row = mysqli_fetch_assoc($result_courses);
        $courses_count = (int)($row['count'] ?? 0);

        // Fallback: direct FK on course.lecturer_id if join table has no rows
        if ($courses_count === 0) {
            $sql_courses_fk = "SELECT COUNT(*) as count FROM course WHERE lecturer_id='$lecturer_id_esc'";
            $result_courses_fk = mysqli_query($data, $sql_courses_fk);
            if ($result_courses_fk) {
                $row_fk = mysqli_fetch_assoc($result_courses_fk);
                $courses_count = (int)($row_fk['count'] ?? 0);
            }
        }
    } else {
        // If lecturer_course table doesn't exist, fallback to direct FK
        $sql_courses_fk = "SELECT COUNT(*) as count FROM course WHERE lecturer_id='$lecturer_id_esc'";
        $result_courses_fk = mysqli_query($data, $sql_courses_fk);
        if ($result_courses_fk) {
            $row_fk = mysqli_fetch_assoc($result_courses_fk);
            $courses_count = (int)($row_fk['count'] ?? 0);
        }
    }

    // Approximate distinct students taught by this lecturer
    // via student_course -> course and/or student_enrollment tables
    $sql_students = "SELECT COUNT(DISTINCT sc.student_id) as count
                     FROM student_course sc
                     JOIN course c ON sc.course_id = c.id
                     WHERE c.id IN (
                         SELECT lc.course_id FROM lecturer_course lc WHERE lc.lecturer_id='$lecturer_id_esc'
                     )";
    $result_students = mysqli_query($data, $sql_students);

    if ($result_students) {
        $row = mysqli_fetch_assoc($result_students);
        $students_count = (int)($row['count'] ?? 0);

        // Fallback: use student_enrollment + course.lecturer_id if needed
        if ($students_count === 0) {
            $sql_students_fb = "SELECT COUNT(DISTINCT se.student_id) as count
                                FROM student_enrollment se
                                JOIN course c ON se.course_id = c.id
                                WHERE c.lecturer_id='$lecturer_id_esc'";
            $result_students_fb = mysqli_query($data, $sql_students_fb);
            if ($result_students_fb) {
                $row_fb = mysqli_fetch_assoc($result_students_fb);
                $students_count = (int)($row_fb['count'] ?? 0);
            }
        }
    }
}
?>

<?php include 'includes/lecturer-sidebar.php'; ?>

            <!-- Dashboard Header -->
            <div class="page-title">Welcome, <?php echo htmlspecialchars($lecturer_name); ?></div>
            <div class="page-subtitle">Here's an overview of your teaching activities.</div>

            <!-- Statistics Cards -->
            <div class="grid-cols-3">
                <div class="stat-card">
                    <div class="stat-card-icon primary">
                        <i class="bi bi-book"></i>
                    </div>
                    <div class="stat-card-content">
                        <h6>My Courses</h6>
                        <h3><?php echo $courses_count; ?></h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-icon success">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stat-card-content">
                        <h6>Students Taught</h6>
                        <h3><?php echo $students_count; ?></h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-icon info">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <div class="stat-card-content">
                        <h6>Status</h6>
                        <h3 style="font-size: 1.25rem;">Active</h3>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div style="margin-top: 3rem;">
                <h4 class="section-title">Quick Actions</h4>
                <div class="btn-group-modern">
                    <a href="lecturer_courses.php" class="btn-modern primary">
                        <i class="bi bi-book"></i> View My Courses
                    </a>
                    <a href="lecturer_profile.php" class="btn-modern secondary">
                        <i class="bi bi-person-circle"></i> My Profile
                    </a>
                </div>
            </div>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
