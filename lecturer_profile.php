<?php
session_start();
include 'includes/connect.php';
include 'includes/session-helpers.php';
include 'includes/form-helpers.php';

requireRole('lecturer');

$message = '';
$message_type = '';
$info = array();

// Get current lecturer info
$user_id = getCurrentUserId($data);
if ($user_id) {
    $info = db_fetch_one(
        $data,
        "SELECT * FROM user WHERE id = ? AND usertype = 'lecturer'",
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
                "SELECT * FROM user WHERE id = ? AND usertype = 'lecturer'",
                array($user_id)
            );
        } else {
            $message = 'Failed to update profile';
            $message_type = 'danger';
        }
    }
}
?>

<?php include 'includes/lecturer-sidebar.php'; ?>
            <div class="page-title">My Profile</div>
            <div class="page-subtitle">View and update your account information</div>

            <?php if (!empty($message)): ?>
                <?php showAlert($message, $message_type); ?>
            <?php endif; ?>

            <?php if (!empty($info)): ?>
                <!-- Profile Information Display -->
                <div class="card-modern" style="margin-bottom: 2rem;">
                    <div class="card-modern-header">
                        <h5><i class="bi bi-person-circle"></i> Profile Information</h5>
                    </div>
                    <div class="card-modern-body">
                        <div class="profile-info-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                            <div class="info-item">
                                <label class="form-label" style="color: #6c757d; font-size: 0.875rem; margin-bottom: 0.25rem;">
                                    <i class="bi bi-person"></i> Username
                                </label>
                                <div style="font-size: 1rem; font-weight: 500; color: #2c3e50;">
                                    <?php echo htmlspecialchars($info['username']); ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <label class="form-label" style="color: #6c757d; font-size: 0.875rem; margin-bottom: 0.25rem;">
                                    <i class="bi bi-envelope"></i> Email Address
                                </label>
                                <div style="font-size: 1rem; font-weight: 500; color: #2c3e50;">
                                    <?php echo htmlspecialchars($info['email']); ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <label class="form-label" style="color: #6c757d; font-size: 0.875rem; margin-bottom: 0.25rem;">
                                    <i class="bi bi-telephone"></i> Phone Number
                                </label>
                                <div style="font-size: 1rem; font-weight: 500; color: #2c3e50;">
                                    <?php echo htmlspecialchars($info['phone'] ?? 'Not provided'); ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <label class="form-label" style="color: #6c757d; font-size: 0.875rem; margin-bottom: 0.25rem;">
                                    <i class="bi bi-briefcase"></i> Account Type
                                </label>
                                <div style="font-size: 1rem; font-weight: 500; color: #2c3e50;">
                                    <span class="badge-modern success">Lecturer</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Update Profile Form -->
                <div class="card-modern">
                    <div class="card-modern-header">
                        <h5><i class="bi bi-pencil-square"></i> Update Profile</h5>
                    </div>
                    <form action="lecturer_profile.php" method="POST">
                        <?php echo csrfTokenField(); ?>
                        <div class="card-modern-body">
                    
                    <div class="form-row">
                        <?php formInput('email', 'Email Address', 'email', $info['email'], true, 'example@email.com'); ?>
                        <?php formInput('phone', 'Phone Number', 'tel', $info['phone'] ?? '', false, '+1 (555) 000-0000'); ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">New Password (leave blank to keep current)</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Leave blank to keep current">
                    </div>

                    <input type="hidden" name="update_profile" value="1">
                    <div class="form-group" style="margin-top: 2rem; margin-bottom: 0;">
                        <button type="submit" class="btn-modern success">
                            <i class="bi bi-check"></i> Update Profile
                        </button>
                        <a href="javascript:history.back()" class="btn-modern secondary" style="background: #6c757d;">
                            <i class="bi bi-x"></i> Cancel
                        </a>
                    </div>
                        </div>
                    </form>
                </div>
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
