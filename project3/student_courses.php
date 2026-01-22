<?php
session_start();
include 'includes/connect.php';
include 'includes/session-helpers.php';
include 'includes/form-helpers.php';

requireRole('student');

$conn = $data;

$user_id = getCurrentUserId();
$studentName = $_SESSION['username'];

$coursesStmt = $conn->prepare('
    SELECT c.code, c.name, c.description
    FROM student_course sc
    JOIN course c ON sc.course_id = c.id
    WHERE sc.student_id = ?
    ORDER BY c.code
');
$coursesStmt->bind_param('i', $user_id);
$coursesStmt->execute();
$coursesResult = $coursesStmt->get_result();
?>

<?php include 'includes/student-sidebar.php'; ?>

            <div class="page-title">My Courses</div>
            <div class="page-subtitle">View your enrolled courses</div>

            <?php startModernTable('Enrolled Courses'); ?>
                <tr>
                    <th>Course Code</th>
                    <th>Course Name</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($coursesResult && $coursesResult->num_rows > 0) {
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
                    echo '<tr><td colspan="3" class="text-center text-muted" style="padding: 2rem;">You are not enrolled in any courses yet.</td></tr>';
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
$coursesStmt->close();
mysqli_close($conn);
?>
