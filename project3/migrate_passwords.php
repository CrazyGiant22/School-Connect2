<?php
/**
 * Password Migration Utility
 * Converts all plain text passwords to bcrypt hashes
 * 
 * IMPORTANT: Run this ONCE to migrate existing passwords
 * Save as: migrate_passwords.php
 * 
 * HOW TO USE:
 * 1. Upload this file to your project root
 * 2. Access it once via browser: http://localhost/project3/migrate_passwords.php
 * 3. Delete this file after successful migration
 */

// Prevent accidental re-runs
if (file_exists(__DIR__ . '/.passwords_migrated')) {
    die('Passwords have already been migrated. Delete .passwords_migrated file to run again.');
}

session_start();
include 'includes/connect.php';
include 'includes/security-helpers.php';

// Admin-only access (comment out for first run if no admin has hashed password yet)
// if (!isset($_SESSION['usertype']) || $_SESSION['usertype'] !== 'admin') {
//     die('Admin access required');
// }

if (!$data) {
    die("Database connection failed");
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Password Migration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 2rem; background: #f5f6fa; }
        .container { max-width: 800px; }
        .card { border: none; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); }
        .log-entry { padding: 0.5rem; margin: 0.25rem 0; border-left: 3px solid #0d6efd; background: #f8f9fa; }
        .log-entry.success { border-left-color: #198754; }
        .log-entry.error { border-left-color: #dc3545; }
        .log-entry.warning { border-left-color: #ffc107; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0"><i class="bi bi-shield-lock"></i> Password Migration Tool</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <strong>⚠️ Warning:</strong> This will convert all plain text passwords to secure bcrypt hashes. This process is irreversible.
            </div>
            
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_migration'])): ?>
                
                <h5>Migration Log:</h5>
                <div class="log-output">
                
                <?php
                // Fetch all users
                $users = db_fetch_all($data, "SELECT id, username, password, usertype FROM user");
                
                $total = count($users);
                $migrated = 0;
                $skipped = 0;
                $errors = 0;
                
                echo "<div class='log-entry'><strong>Found $total users to process...</strong></div>";
                
                foreach ($users as $user) {
                    $userId = $user['id'];
                    $username = $user['username'];
                    $currentPassword = $user['password'];
                    
                    // Check if already hashed
                    if (strpos($currentPassword, '$2y$') === 0) {
                        echo "<div class='log-entry warning'>⚠️ Skipped: $username (already hashed)</div>";
                        $skipped++;
                        continue;
                    }
                    
                    // Hash the password
                    $hashedPassword = hashPassword($currentPassword);
                    
                    // Update database
                    $success = db_execute(
                        $data,
                        "UPDATE user SET password = ? WHERE id = ?",
                        [$hashedPassword, $userId]
                    );
                    
                    if ($success) {
                        echo "<div class='log-entry success'>✅ Migrated: $username ({$user['usertype']})</div>";
                        $migrated++;
                    } else {
                        echo "<div class='log-entry error'>❌ Failed: $username - " . mysqli_error($data) . "</div>";
                        $errors++;
                    }
                    
                    // Flush output for real-time display
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }
                
                echo "<div class='log-entry'><strong>Migration Complete!</strong></div>";
                echo "<div class='log-entry success'><strong>✅ Successfully migrated: $migrated</strong></div>";
                echo "<div class='log-entry warning'><strong>⚠️ Skipped (already hashed): $skipped</strong></div>";
                echo "<div class='log-entry error'><strong>❌ Errors: $errors</strong></div>";
                
                // Create marker file to prevent re-runs
                if ($errors === 0) {
                    file_put_contents(__DIR__ . '/.passwords_migrated', date('Y-m-d H:i:s'));
                    echo "<div class='alert alert-success mt-3'>";
                    echo "<strong>✅ Migration completed successfully!</strong><br>";
                    echo "All passwords have been securely hashed.<br>";
                    echo "<strong>⚠️ IMPORTANT:</strong> Delete this file (migrate_passwords.php) now!";
                    echo "</div>";
                } else {
                    echo "<div class='alert alert-danger mt-3'>";
                    echo "<strong>⚠️ Migration completed with errors.</strong><br>";
                    echo "Please review the errors above and try again.";
                    echo "</div>";
                }
                ?>
                
                </div>
                
                <div class="mt-3">
                    <a href="login.php" class="btn btn-primary">Go to Login</a>
                </div>
                
            <?php else: ?>
                
                <h5>Migration Preview:</h5>
                <?php
                $users = db_fetch_all($data, "SELECT id, username, usertype, password FROM user LIMIT 5");
                
                if (!empty($users)) {
                    echo "<table class='table table-sm'>";
                    echo "<thead><tr><th>Username</th><th>Type</th><th>Status</th></tr></thead>";
                    echo "<tbody>";
                    
                    foreach ($users as $user) {
                        $status = (strpos($user['password'], '$2y$') === 0) 
                            ? '<span class="badge bg-success">Already Hashed</span>' 
                            : '<span class="badge bg-warning">Needs Migration</span>';
                        
                        echo "<tr>";
                        echo "<td>{$user['username']}</td>";
                        echo "<td>{$user['usertype']}</td>";
                        echo "<td>$status</td>";
                        echo "</tr>";
                    }
                    
                    echo "</tbody></table>";
                    
                    $total = db_fetch_one($data, "SELECT COUNT(*) as count FROM user")['count'];
                    echo "<p class='text-muted'>Showing 5 of $total users...</p>";
                }
                ?>
                
                <form method="POST" class="mt-4">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="confirm" name="confirm" required>
                        <label class="form-check-label" for="confirm">
                            I understand this will modify all user passwords in the database
                        </label>
                    </div>
                    
                    <button type="submit" name="confirm_migration" class="btn btn-danger btn-lg">
                        <i class="bi bi-shield-lock"></i> Start Migration
                    </button>
                    <a href="adminhome.php" class="btn btn-secondary btn-lg">Cancel</a>
                </form>
                
            <?php endif; ?>
            
        </div>
    </div>
    
    <div class="mt-4 text-center text-muted">
        <small>
            <strong>Security Note:</strong> After migration, users will log in with the same passwords, 
            but they will be stored securely as bcrypt hashes. Delete this file after successful migration.
        </small>
    </div>
</div>
</body>
</html>