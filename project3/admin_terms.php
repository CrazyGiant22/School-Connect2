<?php
session_start();
include 'includes/connect.php';
include 'includes/session-helpers.php';
include 'includes/form-helpers.php';

requireRole('admin');

$conn = $data;
$message = '';
$message_type = '';

// Handle create term
if (isset($_POST['create_term'])) {
    $name = trim($_POST['name'] ?? '');
    $academic_year = intval($_POST['academic_year'] ?? 0);
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $is_current = isset($_POST['is_current']) ? 1 : 0;

    if ($name === '' || !$academic_year) {
        $message = 'Please provide term name and academic year.';
        $message_type = 'danger';
    } else {
        // If marking current, clear current flag on all terms
        if ($is_current) {
            mysqli_query($conn, "UPDATE academic_terms SET is_current=0");
        }
        $stmt = $conn->prepare("INSERT INTO academic_terms (name, academic_year, start_date, end_date, is_current) VALUES (?,?,?,?,?)");
        if ($stmt) {
            $stmt->bind_param('sissi', $name, $academic_year, $start_date, $end_date, $is_current);
            if ($stmt->execute()) {
                $message = 'Term created successfully.';
                $message_type = 'success';
            } else {
                $message = 'Error creating term: ' . htmlspecialchars($conn->error);
                $message_type = 'danger';
            }
            $stmt->close();
        } else {
            $message = 'Failed to prepare statement: ' . htmlspecialchars($conn->error);
            $message_type = 'danger';
        }
    }
}

// Handle set current
if (isset($_GET['set_current'])) {
    $term_id = intval($_GET['set_current']);
    mysqli_query($conn, "UPDATE academic_terms SET is_current=0");
    mysqli_query($conn, "UPDATE academic_terms SET is_current=1 WHERE id=$term_id");
    header('Location: admin_terms.php');
    exit;
}

// Handle delete term with validation (prevent deleting terms in use)
if (isset($_GET['delete'])) {
    $term_id = intval($_GET['delete']);

    // Check if any assessments or student-term links reference this term
    $in_use = false;

    $res1 = mysqli_query($conn, "SELECT COUNT(*) AS c FROM ca_assessments WHERE term_id=" . $term_id);
    if ($res1) {
        $row1 = mysqli_fetch_assoc($res1);
        if (!empty($row1['c']) && intval($row1['c']) > 0) {
            $in_use = true;
        }
    }

    $res2 = mysqli_query($conn, "SELECT COUNT(*) AS c FROM student_terms WHERE term_id=" . $term_id);
    if ($res2) {
        $row2 = mysqli_fetch_assoc($res2);
        if (!empty($row2['c']) && intval($row2['c']) > 0) {
            $in_use = true;
        }
    }

    if ($in_use) {
        $message = 'Cannot delete this term because it is linked to assessments or students. Remove those links first.';
        $message_type = 'danger';
    } else {
        db_execute($conn, "DELETE FROM academic_terms WHERE id = ?", [$term_id]);
        header('Location: admin_terms.php');
        exit;
    }
}

// Load all terms
$terms = [];
$res = mysqli_query($conn, "SELECT * FROM academic_terms ORDER BY academic_year DESC, id ASC");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) { $terms[] = $row; }
}
?>

<?php include 'includes/admin-sidebar.php'; ?>
            <div class="page-title">Academic Terms</div>
            <div class="page-subtitle">Manage First/Second/Third term for each academic year</div>

            <?php if (!empty($message)): ?>
                <?php showAlert($message, $message_type ?: 'info'); ?>
            <?php endif; ?>

            <?php startModernForm('admin_terms.php', 'POST'); ?>
                <input type="hidden" name="create_term" value="1">
                <div class="row g-3">
                    <div class="col-md-3">
                        <?php formSelect('name', 'Term Name', [
                            'First Term' => 'First Term',
                            'Second Term' => 'Second Term',
                            'Third Term' => 'Third Term',
                        ], $_POST['name'] ?? '', true); ?>
                    </div>
                    <div class="col-md-2">
                        <?php formInput('academic_year', 'Academic Year', 'number', $_POST['academic_year'] ?? date('Y'), true, 'e.g. 2024'); ?>
                    </div>
                    <div class="col-md-3">
                        <?php formInput('start_date', 'Start Date', 'date', $_POST['start_date'] ?? '', false); ?>
                    </div>
                    <div class="col-md-3">
                        <?php formInput('end_date', 'End Date', 'date', $_POST['end_date'] ?? '', false); ?>
                    </div>
                    <div class="col-md-1" style="padding-top: 2rem;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_current" name="is_current">
                            <label class="form-check-label" for="is_current">Current</label>
                        </div>
                    </div>
                </div>
            <?php endModernForm('Create Term'); ?>

            <?php startModernTable('Existing Terms'); ?>
                <tr>
                    <th>Academic Year</th>
                    <th>Term</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($terms)): ?>
                    <tr><td colspan="6" class="text-center text-muted" style="padding: 2rem;">No terms defined yet.</td></tr>
                <?php else: foreach ($terms as $t): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($t['academic_year']); ?></td>
                        <td><?php echo htmlspecialchars($t['name']); ?></td>
                        <td><?php echo htmlspecialchars($t['start_date'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($t['end_date'] ?? ''); ?></td>
                        <td>
                            <?php if ($t['is_current']): ?>
                                <?php statusBadge('Current', 'success'); ?>
                            <?php else: ?>
                                <?php statusBadge('Normal', 'secondary'); ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="admin_terms.php?set_current=<?php echo intval($t['id']); ?>" class="btn-modern primary" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;">Set Current</a>
                            <a href="admin_terms.php?delete=<?php echo intval($t['id']); ?>" class="btn-modern danger" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;" onclick="return confirm('Delete this term?');">Delete</a>
                        </td>
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
