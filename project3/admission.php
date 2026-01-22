<?php
session_start();
include 'includes/connect.php';
include 'includes/session-helpers.php';
include 'includes/form-helpers.php';

requireRole('admin');

// Handle delete requests
if (isset($_POST['delete_admission'])) {
    // CSRF protection for delete action
    requireCSRFToken();

    $admission_id = intval($_POST['admission_id'] ?? 0);
    if ($admission_id > 0) {
        $ok = db_execute(
            $data,
            "DELETE FROM admission WHERE id = ?",
            array($admission_id)
        );
        if ($ok) {
            $_SESSION['message'] = 'Admission deleted successfully.';
        } else {
            $_SESSION['message'] = 'Failed to delete admission.';
        }
    }
    header('Location: admission.php');
    exit;
}

// Handle status updates
if (isset($_POST['update_status'])) {
    // CSRF protection for status update
    requireCSRFToken();

    $admission_id = intval($_POST['admission_id'] ?? 0);
    $new_status = $_POST['status'] ?? 'pending';
    // Normalize status to two values in DB: 'pending' or 'resolved'
    $new_status = ($new_status === 'pending') ? 'pending' : 'resolved';

    if ($admission_id > 0) {
        $status_esc = mysqli_real_escape_string($data, $new_status);
        $sql_update = "UPDATE admission SET status='$status_esc' WHERE id=$admission_id";
        if (mysqli_query($data, $sql_update)) {
            $_SESSION['message'] = 'Admission status updated successfully.';
        } else {
            $_SESSION['message'] = 'Failed to update status: ' . mysqli_error($data);
        }
    }

    header('Location: admission.php');
    exit;
}

$sql = "SELECT * FROM `admission` ORDER BY id DESC";
$result = mysqli_query($data, $sql);
?>

<?php include 'includes/admin-sidebar.php'; ?>

            <div class="page-title">Admissions Management</div>
            <div class="page-subtitle">Review pending admission applications</div>

            <?php startModernTable('Admission Applications'); ?>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard - Admission Requests</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">



    <style>
        body {
            background-color: #f8f9fa;
        }
        .content {
            margin-left: 280px;
            padding: 30px;
        }
        @media (max-width: 768px) {
            .content {
                margin-left: 0;
            }
        }
        .message-col {
            max-width: 300px;
            word-wrap: break-word;
        }
    </style>
</head>
<body>



<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4 text-center text-success">
                    <i class="bi bi-file-earmark-text"></i> Applied For Admission
                </h1>

                <!-- Optional Success Message -->
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_SESSION['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>

                <!-- Responsive Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-bordered align-middle">
                        <thead class="table-success">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th class="message-col">Message</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($info = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($info['name']); ?></td>
                                    <td><?php echo htmlspecialchars($info['email']); ?></td>
                                    <td><?php echo htmlspecialchars($info['phone']); ?></td>
                                    <td class="message-col">
                                        <small><?php echo nl2br(htmlspecialchars($info['message'])); ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $status_raw = $info['status'] ?? 'pending';
                                        ?>
                                        <form method="post" action="admission.php" class="d-flex align-items-center gap-2">
                                            <?php echo csrfTokenField(); ?>
                                            <input type="hidden" name="admission_id" value="<?php echo (int)$info['id']; ?>">
                                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                <option value="pending" <?php echo $status_raw === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="resolved" <?php echo $status_raw !== 'pending' ? 'selected' : ''; ?>>Dealt with</option>
                                            </select>
                                            <input type="hidden" name="update_status" value="1">
                                        </form>
                                    </td>
                                    <td>
                                        <form method="post" action="admission.php" onsubmit="return confirm('Are you sure you want to delete this admission request?');">
                                            <?php echo csrfTokenField(); ?>
                                            <input type="hidden" name="admission_id" value="<?php echo (int)$info['id']; ?>">
                                            <input type="hidden" name="delete_admission" value="1">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            <?php
                            if ($result && mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($row['id']) . '</td>';
                                    echo '<td><strong>' . htmlspecialchars($row['name']) . '</strong></td>';
                                    echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['phone']) . '</td>';
                                    $status_raw = $row['status'] ?? 'pending';
                                    $status_label = ($status_raw === 'pending') ? 'Pending' : 'Dealt with';
                                    $status_type = ($status_raw === 'pending') ? 'warning' : 'success';
                                    echo '<td><span class="badge-modern ' . $status_type . '">' . $status_label . '</span></td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="5" class="text-center text-muted" style="padding: 2rem;">No admissions found.</td></tr>';
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
