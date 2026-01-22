<?php
session_start();
include 'includes/connect.php';
include 'includes/session-helpers.php';
include 'includes/form-helpers.php';

requireRole('admin');

$message = '';
$message_type = '';
$lecturer_info = array();
$assigned_courses = array();
$courses_error = '';

// Get lecturer info from user table
if (isset($_GET['lecturer_id'])) {
    $lecturer_id = intval($_GET['lecturer_id']);
    $lecturer_info = db_fetch_one(
        $data,
        "SELECT * FROM user WHERE id = ? AND usertype = 'lecturer'",
        [$lecturer_id]
    );

    if (!$lecturer_info) {
        $message = 'Lecturer not found';
        $message_type = 'danger';
    }
}

// Process form submission
if (isset($_POST['update_lecturer'])) {
    // Validate CSRF token for this form submission
    requireCSRFToken();

    $lecturer_id = intval($_POST['lecturer_id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate inputs
    if ($username === '' || $email === '') {
        $message = 'Please fill in all required fields';
        $message_type = 'danger';
    } else {
        if ($password !== '') {
            $hashedPassword = hashPassword($password);
            $ok = db_execute(
                $data,
                "UPDATE user SET username = ?, email = ?, phone = ?, password = ? WHERE id = ?",
                array($username, $email, $phone, $hashedPassword, $lecturer_id)
            );
        } else {
            $ok = db_execute(
                $data,
                "UPDATE user SET username = ?, email = ?, phone = ? WHERE id = ?",
                array($username, $email, $phone, $lecturer_id)
            );
        }
        
        if ($ok) {
            $message = 'Lecturer updated successfully!';
            $message_type = 'success';
            // Refresh lecturer info
            $lecturer_info = db_fetch_one($data, "SELECT * FROM user WHERE id = ?", array($lecturer_id));
        } else {
            $message = 'Error updating lecturer: ' . mysqli_error($data);
            $message_type = 'danger';
        }
    }
}

// Fetch courses assigned to this lecturer (if lecturer info is available)
if (!empty($lecturer_info)) {
    $lecturerIdForCourses = (int)$lecturer_info['id'];

    // Try using lecturer_course join table first
    $assigned_courses = db_fetch_all(
        $data,
        "SELECT c.code, c.name, c.description
         FROM lecturer_course lc
         JOIN course c ON lc.course_id = c.id
         WHERE lc.lecturer_id = ?
         ORDER BY c.code",
        [$lecturerIdForCourses]
    );

    if (empty($assigned_courses)) {
        // Fallback: direct FK on course.lecturer_id (legacy schema)
        $assigned_courses = db_fetch_all(
            $data,
            "SELECT c.code, c.name, c.description
             FROM course c
             WHERE c.lecturer_id = ?
             ORDER BY c.code",
            [$lecturerIdForCourses]
        );
    }
}
?>

<?php include 'includes/admin-sidebar.php'; ?>
            <div class="page-title">Update Lecturer</div>
            <div class="page-subtitle">Update lecturer account information</div>

            <?php if (!empty($message)): ?>
                <?php showAlert($message, $message_type); ?>
            <?php endif; ?>

            <?php if (!empty($lecturer_info)): ?>
            <?php startModernForm('admin_update_lecturer.php', 'POST'); ?>
                    <?php echo csrfTokenField(); ?>
                    
                    <div class="form-row">
                        <?php formInput('username', 'Username', 'text', $lecturer_info['username'], true, 'Enter username'); ?>
                        <?php formInput('email', 'Email Address', 'email', $lecturer_info['email'], true, 'example@email.com'); ?>
                    </div>

                    <div class="form-row">
                        <?php formInput('phone', 'Phone Number', 'tel', $lecturer_info['phone'] ?? '', false, '+1 (555) 000-0000'); ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password (leave blank to keep current)</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
                    </div>

                    <input type="hidden" name="lecturer_id" value="<?php echo $lecturer_info['id']; ?>">
                    <input type="hidden" name="update_lecturer" value="1">
                    <?php endModernForm('Update Lecturer', 'primary'); ?>

                <!-- Assigned courses section -->
                <?php startModernTable('Assigned Courses'); ?>
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
                    } elseif (!empty($assigned_courses)) {
                        foreach ($assigned_courses as $c) {
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
                        echo '<tr><td colspan="3" class="text-center text-muted" style="padding: 2rem;">This lecturer is not assigned to any courses yet.</td></tr>';
                    }
                    ?>
                </tbody>
                <?php endModernTable(); ?>
            <?php else: ?>
                <div class="alert-box warning">
                    <p>No lecturer information found. Please go back and select a lecturer to update.</p>
                </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
