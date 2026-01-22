<?php
session_start();
include 'includes/connect.php';
include 'includes/session-helpers.php';

// Require student login
requireRole('student');

$student_name = $_SESSION['username'];
// Get enrolled courses - with error handling
$courses_count = 0;
$student_id = getStudentId($data);
$recent_new_assessments = 0;
$recent_ca_changes = 0;

if ($student_id) {
    // Primary mapping: student_course (used by enroll_student.php, student_courses.php)
    $student_id_esc = mysqli_real_escape_string($data, (string)$student_id);
    $sql_courses = "SELECT COUNT(*) as count FROM student_course WHERE student_id='$student_id_esc'";
    $result_courses = mysqli_query($data, $sql_courses);

    if ($result_courses) {
        $row = mysqli_fetch_assoc($result_courses);
        $courses_count = (int)($row['count'] ?? 0);

        // If zero, try legacy mapping table student_enrollment as fallback
        if ($courses_count === 0) {
            $sql_courses_fallback = "SELECT COUNT(*) as count FROM student_enrollment WHERE student_id='$student_id_esc'";
            $result_courses_fb = mysqli_query($data, $sql_courses_fallback);
            if ($result_courses_fb) {
                $row_fb = mysqli_fetch_assoc($result_courses_fb);
                $courses_count = (int)($row_fb['count'] ?? 0);
            }
        }
    } else {
        // If student_course table doesn't exist, fallback to student_enrollment directly
        $sql_courses_fallback = "SELECT COUNT(*) as count FROM student_enrollment WHERE student_id='$student_id_esc'";
        $result_courses_fb = mysqli_query($data, $sql_courses_fallback);
        if ($result_courses_fb) {
            $row_fb = mysqli_fetch_assoc($result_courses_fb);
            $courses_count = (int)($row_fb['count'] ?? 0);
        }
    }

    // Compute recent CA activity for notifications (last 7 days)
    $student_id_esc = mysqli_real_escape_string($data, (string)$student_id);

    // New assessments (assignments/work) created for student's courses
    $sql_new_assess = "SELECT COUNT(DISTINCT ca.id) AS c
                       FROM ca_assessments ca
                       JOIN student_course sc ON sc.course_id = ca.course_id
                       WHERE sc.student_id = '$student_id_esc'
                         AND ca.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $res_new = mysqli_query($data, $sql_new_assess);
    if ($res_new) {
        $row_new = mysqli_fetch_assoc($res_new);
        $recent_new_assessments = (int)($row_new['c'] ?? 0);
    }

    // CA score changes for this student (new or updated scores)
    $sql_ca_changes = "SELECT COUNT(*) AS c
                       FROM ca_scores cs
                       JOIN ca_assessments ca ON ca.id = cs.assessment_id
                       WHERE cs.student_id = '$student_id_esc'
                         AND (cs.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                              OR cs.updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY))";
    $res_changes = mysqli_query($data, $sql_ca_changes);
    if ($res_changes) {
        $row_ch = mysqli_fetch_assoc($res_changes);
        $recent_ca_changes = (int)($row_ch['c'] ?? 0);
    }
}
?>

<?php include 'includes/student-sidebar.php'; ?>

            <!-- Dashboard Header -->
           <div class="page-title">Welcome, <?php echo htmlspecialchars($student_name); ?></div>
            <div class="page-subtitle">Track your courses and academic progress.</div>

            <!-- Statistics Cards -->
            <div class="grid-cols-2">
                <div class="stat-card">
                    <div class="stat-card-icon primary">
                        <i class="bi bi-book-half"></i>
                    </div>
                    <div class="stat-card-content">
                        <h6>Enrolled Courses</h6>
                        <h3><?php echo $courses_count; ?></h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-icon success">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="stat-card-content">
                        <h6>Status</h6>
                        <h3 style="font-size: 1.25rem;">Active</h3>
                    </div>
                </div>
            </div>

            <!-- Recent CA / Assignment Notifications -->
            <div style="margin-top: 2rem;">
                <?php if ($recent_new_assessments > 0 || $recent_ca_changes > 0): ?>
                    <div class="alert-modern info" role="alert" style="margin-bottom: 0;">
                        <i class="bi bi-bell"></i>
                        <?php if ($recent_new_assessments > 0): ?>
                            You have <strong><?php echo $recent_new_assessments; ?></strong> new assessment(s) or assignment(s) published in the last 7 days.
                        <?php endif; ?>
                        <?php if ($recent_ca_changes > 0): ?>
                            <?php if ($recent_new_assessments > 0): ?> &middot; <?php endif; ?>
                            Your Continuous Assessment scores have been updated <strong><?php echo $recent_ca_changes; ?></strong> time(s) in the last 7 days.
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="alert-modern success" role="alert" style="margin-bottom: 0;">
                        <i class="bi bi-bell-slash"></i>
                        No new CA changes or assignments in the last 7 days.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div style="margin-top: 3rem;">
                <h4 class="section-title">Quick Actions</h4>
                <div class="btn-group-modern">
                    <a href="student_courses.php" class="btn-modern primary">
                        <i class="bi bi-book-half"></i> View My Courses
                    </a>
                    <a href="student_profile.php" class="btn-modern secondary">
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
