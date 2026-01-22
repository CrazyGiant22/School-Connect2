<?php
error_reporting(0);
session_start();
include 'includes/connect.php';
include 'includes/session-helpers.php';
include 'includes/form-helpers.php';

// Require admin login
requireRole('admin');

$result = db_fetch_all($data, "SELECT * FROM user WHERE usertype = 'lecturer' ORDER BY username ASC");

?>

<?php include 'includes/admin-sidebar.php'; ?>

            <div class="page-title">Lecturers Management</div>
            <div class="page-subtitle">View and manage all lecturers in the system</div>

            <!-- Add Button -->
            <div class="btn-group-modern" style="margin-bottom: 2rem;">
                <a href="admin_add_lecturer.php" class="btn-modern primary">
                    <i class="bi bi-briefcase"></i> Add New Lecturer
                </a>
            </div>

            <!-- Lecturers Table -->
            <?php startModernTable('All Lecturers'); ?>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
<?php
                if (!empty($result)) {
                    foreach ($result as $row) {
                        $id = htmlspecialchars($row['id']);
                        $username = htmlspecialchars($row['username']);
                        $email = htmlspecialchars($row['email']);
                        $phone = htmlspecialchars($row['phone'] ?? 'N/A');
                        ?>
                        <tr>
                            <td><?php echo $id; ?></td>
                            <td><strong><?php echo $username; ?></strong></td>
                            <td><?php echo $email; ?></td>
                            <td><?php echo $phone; ?></td>
                            <td>
                                <span class="badge-modern success">Active</span>
                            </td>
                            <td>
                                <?php tableActionButtons(
                                    $id,
                                    'admin_update_lecturer.php?lecturer_id={id}',
                                    'delete.php'
                                ); ?>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    echo '<tr><td colspan="6" class="text-center text-muted" style="padding: 2rem;">No lecturers found.</td></tr>';
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
