<?php
session_start();
include 'includes/connect.php';
include 'includes/session-helpers.php';
include 'includes/form-helpers.php';

// Require admin login
requireRole('admin');

$message = '';
$message_type = '';

// Get lecturers for dropdown
$lecturers = array();
$lecturer_rows = db_fetch_all($data, "SELECT id, username FROM user WHERE usertype = 'lecturer' ORDER BY username");
foreach ($lecturer_rows as $row) {
    $lecturers[$row['id']] = $row['username'];
}

// Process form submission
if(isset($_POST['add_course'])) {
    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $lecturer_id = $_POST['lecturer_id'] !== '' ? (int)$_POST['lecturer_id'] : null;

    // Validate inputs
    if ($code === '' || $name === '' || $description === '') {
        $message = 'Please fill in all required fields';
        $message_type = 'danger';
    } else {
        // Check if course code exists
        $existing = db_fetch_one($data, "SELECT id FROM course WHERE code = ?", [$code]);
        
        if ($existing) {
            $message = 'Course code already exists';
            $message_type = 'warning';
        } else {
            // Insert new course
            $ok = db_execute(
                $data,
                "INSERT INTO course (code, name, description) VALUES (?, ?, ?)",
                [$code, $name, $description]
            );
            
            if($ok) {
                $course_id = mysqli_insert_id($data);

                // If a lecturer was selected, create the relation in lecturer_course table
                if (!empty($lecturer_id)) {
                    $assign_ok = db_execute(
                        $data,
                        "INSERT INTO lecturer_course (lecturer_id, course_id) VALUES (?, ?)",
                        [$lecturer_id, $course_id]
                    );

                    if (!$assign_ok) {
                        $message = 'Course created, but assigning lecturer failed: ' . mysqli_error($data);
                        $message_type = 'warning';
                        return;
                    }
                }

                $message = 'Course added successfully!';
                $message_type = 'success';
                $_POST = array();
            } else {
                $message = 'Error adding course: ' . mysqli_error($data);
                $message_type = 'danger';
            }
        }
    }
}

?>

<?php include 'includes/admin-sidebar.php'; ?>

            <div class="page-title">Add New Course</div>
            <div class="page-subtitle">Create a new course in the system</div>

            <?php if (!empty($message)): ?>
                <?php showAlert($message, $message_type); ?>
            <?php endif; ?>

            <?php startModernForm('add_course.php', 'POST'); ?>
                
                <div class="form-row">
                    <?php formInput('code', 'Course Code', 'text', $_POST['code'] ?? '', true, 'e.g., CS101'); ?>
                    <?php formInput('name', 'Course Name', 'text', $_POST['name'] ?? '', true, 'e.g., Introduction to Programming'); ?>
                </div>

                <div class="form-group">
                    <?php formTextarea('description', 'Course Description', $_POST['description'] ?? '', true, 5); ?>
                </div>

                <div class="form-group">
                    <?php formSelect('lecturer_id', 'Assign Lecturer', $lecturers, $_POST['lecturer_id'] ?? ''); ?>
                </div>

                <input type="hidden" name="add_course" value="1">
                <?php endModernForm('Add Course', 'primary'); ?>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
