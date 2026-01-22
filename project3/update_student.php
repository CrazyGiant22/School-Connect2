<?php
session_start();
include 'includes/connect.php';
include 'includes/session-helpers.php';
include 'includes/form-helpers.php';

requireRole('admin');

$id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$message = '';
$message_type = '';
$enrolled_courses = array();
$courses_error = '';

if ($id <= 0) {
    header("location:view_student.php");
    exit;
}

// Fetch student using prepared statement helper and ensure user is a student
$info = db_fetch_one(
    $data,
    "SELECT * FROM `user` WHERE id = ? AND usertype = 'student'",
    array($id)
);

if (!$info) {
    header("location:view_student.php");
    exit;
}

// Fetch courses this student is enrolled in
$studentIdForCourses = mysqli_real_escape_string($data, $id);

// Try student_course mapping table first
$sql_courses = "SELECT c.code, c.name, c.description
                FROM student_course sc
                JOIN course c ON sc.course_id = c.id
                WHERE sc.student_id = '$studentIdForCourses'
                ORDER BY c.code";
$coursesResult = mysqli_query($data, $sql_courses);

if ($coursesResult) {
    if (mysqli_num_rows($coursesResult) > 0) {
        while ($row = mysqli_fetch_assoc($coursesResult)) {
            $enrolled_courses[] = $row;
        }
    } else {
        // Fallback: alternate mapping table student_enrollment
        $sql_courses_alt = "SELECT c.code, c.name, c.description
                            FROM student_enrollment se
                            JOIN course c ON se.course_id = c.id
                            WHERE se.student_id = '$studentIdForCourses'
                            ORDER BY c.code";
        $coursesResultAlt = mysqli_query($data, $sql_courses_alt);
        if ($coursesResultAlt) {
            if (mysqli_num_rows($coursesResultAlt) > 0) {
                while ($row = mysqli_fetch_assoc($coursesResultAlt)) {
                    $enrolled_courses[] = $row;
                }
            }
        } else {
            $courses_error = 'Error fetching enrolled courses: ' . mysqli_error($data);
        }
    }
} else {
    // If first query failed (e.g., table does not exist), try alternate mapping table
    $sql_courses_alt = "SELECT c.code, c.name, c.description
                        FROM student_enrollment se
                        JOIN course c ON se.course_id = c.id
                        WHERE se.student_id = '$studentIdForCourses'
                        ORDER BY c.code";
    $coursesResultAlt = mysqli_query($data, $sql_courses_alt);
    if ($coursesResultAlt) {
        if (mysqli_num_rows($coursesResultAlt) > 0) {
            while ($row = mysqli_fetch_assoc($coursesResultAlt)) {
                $enrolled_courses[] = $row;
            }
        }
    } else {
        $courses_error = 'Error fetching enrolled courses: ' . mysqli_error($data);
    }
}

if (isset($_POST['update'])) {
    // Validate CSRF token for this form submission
    requireCSRFToken();

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $email === '') {
        $message = 'Please fill in all required fields';
        $message_type = 'danger';
    } else {
        // Build parameterized update; only change password if provided
        $sql    = "UPDATE user SET username = ?, email = ?, phone = ?";
        $params = array($username, $email, $phone);

        if ($password !== '') {
            $hashedPassword = hashPassword($password);
            $sql          .= ", password = ?";
            $params[]      = $hashedPassword;
        }

        $sql    .= " WHERE id = ?";
        $params[] = $id;

        $ok = db_execute($data, $sql, $params);

        if ($ok) {
            $message = "Student updated successfully!";
            $message_type = 'success';
            $_SESSION['message'] = $message;
            header("Location: view_student.php");
            exit();
        } else {
            $message = "Failed to update student";
            $message_type = 'danger';
        }
    }
}

?>

<?php include 'includes/admin-sidebar.php'; ?>

            <div class="page-title">Update Student</div>
            <div class="page-subtitle">Edit student information</div>

            <?php if (!empty($message)): ?>
                <?php showAlert($message, $message_type); ?>
            <?php endif; ?>

<?php startModernForm('update_student.php?student_id='.$id, 'POST'); ?>
                <?php echo csrfTokenField(); ?>
                
                <div class="form-row">
                    <?php formInput('username', 'Username', 'text', $info['username'] ?? '', true); ?>
                    <?php formInput('email', 'Email', 'email', $info['email'] ?? '', true); ?>
                </div>

                <div class="form-row">
                    <?php formInput('phone', 'Phone', 'tel', $info['phone'] ?? '', false); ?>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">New Password (leave blank to keep current)</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
                </div>

                <input type="hidden" name="update" value="1">
                <?php endModernForm('Update Student', 'primary'); ?>

            <!-- Enrolled courses section -->
            <?php startModernTable('Enrolled Courses'); ?>
                <tr>
                    <th>Course Code</th>
                    <th>Course Name</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($courses_error)) {
                    echo '<tr><td colspan="3" class="text-center text-danger" style="padding: 2rem;">' . htmlspecialchars($courses_error) . '</td></tr>';
                } elseif (!empty($enrolled_courses)) {
                    foreach ($enrolled_courses as $c) {
                        $code = htmlspecialchars($c['code']);
                        $name = htmlspecialchars($c['name']);
                        $description = htmlspecialchars($c['description'] ?? '—');
                        echo '<tr>' .
                             '<td><strong>' . $code . '</strong></td>' .
                             '<td>' . $name . '</td>' .
                             '<td>' . $description . '</td>' .
                             '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="3" class="text-center text-muted" style="padding: 2rem;">This student is not enrolled in any courses yet.</td></tr>';
                }
                ?>
            </tbody>
            <?php endModernTable(); ?>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
