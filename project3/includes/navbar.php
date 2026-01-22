<?php
// Navbar Component - Used across all dashboards
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Safe session variable retrieval
$usertype = $_SESSION['usertype'] ?? 'unknown';
$username = $_SESSION['username'] ?? 'User';
$username = is_string($username) ? $username : 'User';
$initials = strtoupper(substr($username, 0, 1));
?>

<nav class="navbar-top">
    <div class="navbar-top-left">
        <button class="btn-toggle" id="sidebarToggle" type="button">
            <i class="bi bi-list"></i>
        </button>
        <div class="app-title">
            <i class="bi bi-mortarboard"></i> School-Connect
        </div>
    </div>

    <div class="navbar-top-right">
        <div class="user-info">
            <div class="user-avatar"><?php echo $initials; ?></div>
            <span><?php echo htmlspecialchars($username); ?></span>
        </div>
        <a href="logout.php" class="btn-logout">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</nav>

<script>
    document.getElementById('sidebarToggle')?.addEventListener('click', function() {
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        sidebar.classList.toggle('show');
        mainContent.classList.toggle('expanded');
    });
</script>
