<?php
session_start();
include 'includes/connect.php';
include 'includes/session-helpers.php';
include 'includes/form-helpers.php';

// Require admin login
requireRole('admin');

$message = '';
$message_type = '';

// Process form submission
if(isset($_POST['add_lecturer'])) {
    // CSRF protection
    requireCSRFToken();

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $usertype = "lecturer";

    // Validate inputs
    if ($username === '' || $password === '' || $email === '') {
        $message = 'Please fill in all required fields';
        $message_type = 'danger';
    } else {
        // Check if username exists
        $existing = db_fetch_one($data, "SELECT id FROM user WHERE username = ?", [$username]);
        
        if ($existing) {
            $message = 'Username already exists. Please choose a different username.';
            $message_type = 'warning';
        } else {
            // Insert new lecturer (store hashed password)
            $hashedPassword = hashPassword($password);
            $ok = db_execute(
                $data,
                "INSERT INTO user (username, password, email, phone, usertype) VALUES (?, ?, ?, ?, ?)",
                [$username, $hashedPassword, $email, $phone, $usertype]
            );
            
            if($ok) {
    $message = 'Lecturer added successfully!';
    $message_type = 'success';
    
    // Send welcome email
    sendLecturerWelcomeEmail($email, $username, $password);
    
    $_POST = array();
} else {
                $message = 'Error adding lecturer: ' . mysqli_error($data);
                $message_type = 'danger';
            }
        }
    }
}

?>

<?php include 'includes/admin-sidebar.php'; ?>
            <div class="page-title">Add New Lecturer</div>
            <div class="page-subtitle">Create a new lecturer account in the system</div>

            <?php if (!empty($message)): ?>
                <?php showAlert($message, $message_type); ?>
            <?php endif; ?>

            <?php startModernForm('admin_add_lecturer.php', 'POST'); ?>
                <?php echo csrfTokenField(); ?>
                
                <div class="form-row">
                    <?php formInput('username', 'Username', 'text', $_POST['username'] ?? '', true, 'Enter username'); ?>
                    <?php formInput('email', 'Email Address', 'email', $_POST['email'] ?? '', true, 'example@email.com'); ?>
                </div>

                <div class="form-row">
                    <?php formInput('password', 'Password', 'password', '', true, 'Enter password'); ?>
                    <?php formInput('phone', 'Phone Number', 'tel', $_POST['phone'] ?? '', false, '+1 (555) 000-0000'); ?>
                </div>

                <div class="form-group">
                    <?php formInput('department', 'Department', 'text', $_POST['department'] ?? '', false, 'e.g., Computer Science'); ?>
                </div>

                <input type="hidden" name="add_lecturer" value="1">
                <?php endModernForm('Add Lecturer', 'success'); ?>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
