<?php
session_start();
include 'includes/connect.php';
include 'includes/session-helpers.php';
include 'includes/form-helpers.php';

requireRole('lecturer');

$conn = $data;

$user_id = getCurrentUserId($data);
$lecturerName = $_SESSION['username'];

// Try join-table relationship first; if unavailable, fallback to direct FK.
$errorMsg = '';
$coursesStmt = $conn->prepare('
    SELECT c.code, c.name, c.description
    FROM lecturer_course lc
    JOIN course c ON lc.course_id = c.id
    WHERE lc.lecturer_id = ?
    ORDER BY c.code
');

if ($coursesStmt) {
    $coursesStmt->bind_param('i', $user_id);
    $coursesStmt->execute();
    $coursesResult = $coursesStmt->get_result();
} else {
    // Fallback: direct FK on course.lecturer_id (schema used by add_course)
    $coursesStmt = $conn->prepare('
        SELECT c.code, c.name, c.description
        FROM course c
        WHERE c.lecturer_id = ?
        ORDER BY c.code
    ');
    if ($coursesStmt) {
        $coursesStmt->bind_param('i', $user_id);
        $coursesStmt->execute();
        $coursesResult = $coursesStmt->get_result();
    } else {
        $errorMsg = 'Query prepare failed: ' . $conn->error;
        $coursesResult = false;
    }
}
?>

<?php include 'includes/lecturer-sidebar.php'; ?>

            <div class="page-title">My Courses</div>
            <div class="page-subtitle">View courses you are teaching</div>

            <?php startModernTable('Assigned Courses'); ?>
                <tr>
                    <th>Course Code</th>
                    <th>Course Name</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!$user_id) {
                    echo '<tr><td colspan="3" class="text-center text-danger" style="padding: 2rem;">Unable to determine your user ID. Please log in again.</td></tr>';
                } elseif (!empty($errorMsg)) {
                    echo '<tr><td colspan="3" class="text-center text-danger" style="padding: 2rem;">' . htmlspecialchars($errorMsg) . '</td></tr>';
                } elseif ($coursesResult && $coursesResult->num_rows > 0) {
                    while ($c = $coursesResult->fetch_assoc()) {
                        $code = htmlspecialchars($c['code']);
                        $name = htmlspecialchars($c['name']);
                        $description = htmlspecialchars($c['description'] ?? '—');
                        ?>
                        <tr>
                            <td><strong><?php echo $code; ?></strong></td>
                            <td><?php echo $name; ?></td>
                            <td><?php echo $description; ?></td>
                        </tr>
                        <?php
                    }
                } else {
                    echo '<tr><td colspan="3" class="text-center text-muted" style="padding: 2rem;">You are not assigned to any courses yet.</td></tr>';
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

<?php
if ($coursesStmt) { $coursesStmt->close(); }
mysqli_close($conn);
?>
