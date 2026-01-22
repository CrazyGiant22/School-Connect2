<?php
session_start();
include 'includes/connect.php';
include 'includes/session-helpers.php';
include 'includes/form-helpers.php';

requireRole('admin');

$conn = $data;
$message = '';
$message_type = '';

// Load students
$students = [];
$resS = mysqli_query($conn, "SELECT id, username FROM user WHERE usertype='student' ORDER BY username");
if ($resS) {
    while ($row = mysqli_fetch_assoc($resS)) {
        $students[$row['id']] = $row['username'];
    }
}

// Load terms
$termOptions = [];
$resT = mysqli_query($conn, "SELECT id, name, academic_year, is_current FROM academic_terms ORDER BY academic_year DESC, id ASC");
if ($resT) {
    while ($row = mysqli_fetch_assoc($resT)) {
        $label = $row['academic_year'] . ' - ' . $row['name'];
        if ($row['is_current']) {
            $label .= ' (Current)';
        }
        $termOptions[$row['id']] = $label;
    }
}

if (isset($_POST['assign_term'])) {
    $student_id = intval($_POST['student_id'] ?? 0);
    $term_id = intval($_POST['term_id'] ?? 0);

    if (!$student_id || !$term_id) {
        $message = 'Please select both student and term.';
        $message_type = 'danger';
    } else {
        $stmt = $conn->prepare("INSERT INTO student_terms (student_id, term_id) VALUES (?,?) ON DUPLICATE KEY UPDATE term_id=VALUES(term_id)");
        if ($stmt) {
            $stmt->bind_param('ii', $student_id, $term_id);
            if ($stmt->execute()) {
                $message = 'Student assigned to term successfully.';
                $message_type = 'success';
            } else {
                $message = 'Error assigning term: ' . htmlspecialchars($conn->error);
                $message_type = 'danger';
            }
            $stmt->close();
        } else {
            $message = 'Failed to prepare statement: ' . htmlspecialchars($conn->error);
            $message_type = 'danger';
        }
    }
}

// Load current assignments (simple list)
$assignments = [];
$resA = mysqli_query($conn, "SELECT st.id, u.username, t.name, t.academic_year FROM student_terms st JOIN user u ON u.id = st.student_id JOIN academic_terms t ON t.id = st.term_id ORDER BY t.academic_year DESC, t.name, u.username");
if ($resA) {
    while ($row = mysqli_fetch_assoc($resA)) { $assignments[] = $row; }
}
?>

<?php include 'includes/admin-sidebar.php'; ?>
            <div class="page-title">Assign Students to Terms</div>
            <div class="page-subtitle">Link students to First/Second/Third term per academic year</div>

            <?php if (!empty($message)): ?>
                <?php showAlert($message, $message_type ?: 'info'); ?>
            <?php endif; ?>

            <?php startModernForm('assign_student_term.php', 'POST'); ?>
                <input type="hidden" name="assign_term" value="1">
                <div class="row g-3">
                    <div class="col-md-4">
                        <?php formSelect('student_id', 'Student', $students, $_POST['student_id'] ?? '', true); ?>
                    </div>
                    <div class="col-md-4">
                        <?php formSelect('term_id', 'Term', $termOptions, $_POST['term_id'] ?? '', true); ?>
                    </div>
                </div>
            <?php endModernForm('Assign Term'); ?>

            <?php startModernTable('Current Assignments'); ?>
                <tr>
                    <th>Student</th>
                    <th>Term</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($assignments)): ?>
                    <tr><td colspan="2" class="text-center text-muted" style="padding: 2rem;">No term assignments yet.</td></tr>
                <?php else: foreach ($assignments as $a): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($a['username']); ?></td>
                        <td><?php echo htmlspecialchars($a['academic_year'] . ' - ' . $a['name']); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
            <?php endModernTable(); ?>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
