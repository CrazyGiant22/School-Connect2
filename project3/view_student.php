<?php
error_reporting(0);
session_start();
include 'includes/connect.php';
include 'includes/session-helpers.php';
include 'includes/form-helpers.php';

requireRole('admin');


$sql = "SELECT * FROM `user` WHERE usertype='student' ORDER BY username ASC";
$result = mysqli_query($data, $sql);
?>

<?php include 'includes/admin-sidebar.php'; ?>

            <div class="page-title">Students Management</div>
            <div class="page-subtitle">View and manage all students in the system</div>

            <div class="btn-group-modern" style="margin-bottom: 2rem;">
                <a href="add_student.php" class="btn-modern primary">
                    <i class="bi bi-person-plus"></i> Add New Student
                </a>
            </div>

            <?php startModernTable('All Students'); ?>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
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
                                <?php tableActionButtons(
                                    $id,
                                    'update_student.php?student_id={id}',
                                    'delete.php'
                                ); ?>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    echo '<tr><td colspan="5" class="text-center text-muted" style="padding: 2rem;">No students found.</td></tr>';
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
