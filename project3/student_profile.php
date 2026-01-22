<?php
session_start();
include 'includes/connect.php';
include 'includes/session-helpers.php';
include 'includes/form-helpers.php';

requireRole('student');

$message = '';
$message_type = '';
$info = array();

// Get current student info
$user_id = getCurrentUserId($data);
if ($user_id) {
    $info = db_fetch_one(
        $data,
        "SELECT * FROM user WHERE id = ? AND usertype = 'student'",
        array($user_id)
    );
}

if (isset($_POST['update_profile'])) {
    // Validate CSRF token for this form submission
    requireCSRFToken();

    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($email === '') {
        $message = 'Email is required';
        $message_type = 'danger';
    } else {
        if ($password !== '') {
            $hashedPassword = hashPassword($password);
            $ok = db_execute(
                $data,
                "UPDATE user SET email = ?, phone = ?, password = ? WHERE id = ?",
                array($email, $phone, $hashedPassword, $user_id)
            );
        } else {
            $ok = db_execute(
                $data,
                "UPDATE user SET email = ?, phone = ? WHERE id = ?",
                array($email, $phone, $user_id)
            );
        }
        
        if ($ok) {
            $message = 'Profile updated successfully!';
            $message_type = 'success';
            // Refresh info
            $info = db_fetch_one(
                $data,
                "SELECT * FROM user WHERE id = ? AND usertype = 'student'",
                array($user_id)
            );
        } else {
            $message = 'Failed to update profile';
            $message_type = 'danger';
        }
    }
}
?>

<?php include 'includes/student-sidebar.php'; ?>

            <div class="page-title">My Profile</div>
            <div class="page-subtitle">Update your account information</div>

            <?php if (!empty($message)): ?>
                <?php showAlert($message, $message_type); ?>
            <?php endif; ?>

            <?php if (!empty($info)): ?>
                <?php startModernForm('student_profile.php', 'POST'); ?>
                    <?php echo csrfTokenField(); ?>
                    
                    <div class="form-row">
                        <?php formInput('email', 'Email Address', 'email', $info['email'], true, 'example@email.com'); ?>
                        <?php formInput('phone', 'Phone Number', 'tel', $info['phone'] ?? '', false, '+1 (555) 000-0000'); ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">New Password (leave blank to keep current)</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Leave blank to keep current">
                    </div>

                    <input type="hidden" name="update_profile" value="1">
                    <?php endModernForm('Update Profile', 'primary'); ?>
            <?php else: ?>
                <div class="alert-box danger">
                    <p>Unable to load profile information. Please try logging in again.</p>
                </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
