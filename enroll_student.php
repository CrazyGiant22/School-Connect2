<?php
session_start();
include 'includes/connect.php';
include 'includes/session-helpers.php';
include 'includes/form-helpers.php';

requireRole('admin');

$message = '';
$message_type = '';

// Get students for dropdown
$students = array();
$student_rows = db_fetch_all($data, "SELECT id, username FROM user WHERE usertype = 'student' ORDER BY username");
foreach ($student_rows as $row) {
    $students[$row['id']] = $row['username'];
}

// Get courses for dropdown
$courses = array();
$course_rows = db_fetch_all($data, "SELECT id, code, name FROM course ORDER BY code");
foreach ($course_rows as $row) {
    $courses[$row['id']] = $row['code'] . ' - ' . $row['name'];
}

if (isset($_POST['submit'])) {
    $student_id = (int)($_POST['student_id'] ?? 0);
    $course_id  = (int)($_POST['course_id'] ?? 0);

    if (!$student_id || !$course_id) {
        $message = 'Please select both student and course';
        $message_type = 'danger';
    } else {
        // Prevent duplicate enrollment
        $existing = db_fetch_one(
            $data,
            "SELECT id FROM student_course WHERE student_id = ? AND course_id = ?",
            [$student_id, $course_id]
        );

        if ($existing) {
            $message = 'This student is already enrolled in this course!';
            $message_type = 'warning';
        } else {
            $ok = db_execute(
                $data,
                "INSERT INTO student_course (student_id, course_id) VALUES (?, ?)",
                [$student_id, $course_id]
            );
            
            if ($ok) {
                $message = 'Student enrolled successfully!';
                $message_type = 'success';
                $_POST = array();
            } else {
                $message = 'Error: ' . mysqli_error($data);
                $message_type = 'danger';
            }
        }
    }
}
?>

<?php include 'includes/admin-sidebar.php'; ?>
            <div class="page-title">Enroll Student in Course</div>
            <div class="page-subtitle">Enroll students into courses</div>

            <?php if (!empty($message)): ?>
                <?php showAlert($message, $message_type); ?>
            <?php endif; ?>

            <?php startModernForm('enroll_student.php', 'POST'); ?>
                
                <div class="form-row">
                    <?php formSelect('student_id', 'Select Student', $students, $_POST['student_id'] ?? '', true); ?>
                    <?php formSelect('course_id', 'Select Course', $courses, $_POST['course_id'] ?? '', true); ?>
                </div>

                <input type="hidden" name="submit" value="1">
                <?php endModernForm('Enroll Student', 'primary'); ?>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
