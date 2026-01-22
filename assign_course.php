<?php
session_start();
include 'includes/connect.php';
include 'includes/session-helpers.php';
include 'includes/form-helpers.php';

requireRole('admin');

$message = '';
$message_type = '';

// Get lecturers for dropdown
$lecturers = array();
$lect_rows = db_fetch_all($data, "SELECT id, username FROM user WHERE usertype = 'lecturer' ORDER BY username");
foreach ($lect_rows as $row) {
    $lecturers[$row['id']] = $row['username'];
}

// Get courses for dropdown
$courses = array();
$course_rows = db_fetch_all($data, "SELECT id, code, name FROM course ORDER BY code");
foreach ($course_rows as $row) {
    $courses[$row['id']] = $row['code'] . ' - ' . $row['name'];
}

if (isset($_POST['submit'])) {
    $lecturer_id = (int)($_POST['lecturer_id'] ?? 0);
    $course_id   = (int)($_POST['course_id'] ?? 0);

    if (!$lecturer_id || !$course_id) {
        $message = 'Please select both lecturer and course';
        $message_type = 'danger';
    } else {
        // Prevent duplicate assignment
        $existing = db_fetch_one(
            $data,
            "SELECT id FROM lecturer_course WHERE lecturer_id = ? AND course_id = ?",
            [$lecturer_id, $course_id]
        );

        if ($existing) {
            $message = 'This course is already assigned to this lecturer!';
            $message_type = 'warning';
        } else {
            $ok = db_execute(
                $data,
                "INSERT INTO lecturer_course (lecturer_id, course_id) VALUES (?, ?)",
                [$lecturer_id, $course_id]
            );
            
            if ($ok) {
                $message = 'Course assigned to lecturer successfully!';
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
            <div class="page-title">Assign Course to Lecturer</div>
            <div class="page-subtitle">Assign courses to lecturers in the system</div>

            <?php if (!empty($message)): ?>
                <?php showAlert($message, $message_type); ?>
            <?php endif; ?>

            <?php startModernForm('assign_course.php', 'POST'); ?>
                
                <div class="form-row">
                    <?php formSelect('lecturer_id', 'Select Lecturer', $lecturers, $_POST['lecturer_id'] ?? '', true); ?>
                    <?php formSelect('course_id', 'Select Course', $courses, $_POST['course_id'] ?? '', true); ?>
                </div>

                <input type="hidden" name="submit" value="1">
                <?php endModernForm('Assign Course', 'success'); ?>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
