<?php
session_start();
include 'includes/connect.php';
include 'includes/session-helpers.php';
include 'includes/form-helpers.php';

requireRole('lecturer');

$conn = $data;
$lecturer_id = getLecturerId($conn);
$message = '';
$message_type = 'success';

// Create CA tables if they don't exist
mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS ca_assessments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  course_id INT NOT NULL,
  title VARCHAR(100) NOT NULL,
  type VARCHAR(30) NULL,
  max_score DECIMAL(7,2) NOT NULL DEFAULT 100.00,
  weight DECIMAL(6,3) NULL,
  assess_date DATE NULL,
  term_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

// Ensure term_id column exists for older installations
$colCheck = mysqli_query($conn, "SHOW COLUMNS FROM ca_assessments LIKE 'term_id'");
if ($colCheck && mysqli_num_rows($colCheck) == 0) {
    mysqli_query($conn, "ALTER TABLE ca_assessments ADD COLUMN term_id INT NULL");
}

// Ensure type column exists for older installations
$colCheckType = mysqli_query($conn, "SHOW COLUMNS FROM ca_assessments LIKE 'type'");
if ($colCheckType && mysqli_num_rows($colCheckType) == 0) {
    mysqli_query($conn, "ALTER TABLE ca_assessments ADD COLUMN type VARCHAR(30) NULL AFTER title");
}

mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS ca_scores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  assessment_id INT NOT NULL,
  student_id INT NOT NULL,
  score DECIMAL(7,2) NOT NULL,
  remarks VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_assessment_student (assessment_id, student_id),
  INDEX idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

// Helper: fetch lecturer courses
function getLecturerCourses($conn, $lecturer_id) {
    // Try via lecturer_course join
    $stmt = $conn->prepare(
        "SELECT c.id, c.code, c.name
         FROM lecturer_course lc
         JOIN course c ON lc.course_id = c.id
         WHERE lc.lecturer_id = ?
         ORDER BY c.code"
    );
    if ($stmt) {
        $stmt->bind_param('i', $lecturer_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $courses = [];
        while ($row = $res->fetch_assoc()) { $courses[] = $row; }
        $stmt->close();
        if (!empty($courses)) return $courses;
    }
    // Fallback: direct FK on course.lecturer_id
    $stmt = $conn->prepare(
        "SELECT c.id, c.code, c.name
         FROM course c
         WHERE c.lecturer_id = ?
         ORDER BY c.code"
    );
    if ($stmt) {
        $stmt->bind_param('i', $lecturer_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $courses = [];
        while ($row = $res->fetch_assoc()) { $courses[] = $row; }
        $stmt->close();
        return $courses;
    }
    return [];
}

function lecturerOwnsCourse($conn, $lecturer_id, $course_id) {
    // Check both schemas
    $stmt = $conn->prepare("SELECT 1 FROM lecturer_course WHERE lecturer_id=? AND course_id=? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('ii', $lecturer_id, $course_id);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        if ($exists) return true;
    }
    $stmt = $conn->prepare("SELECT 1 FROM course WHERE lecturer_id=? AND id=? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('ii', $lecturer_id, $course_id);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $exists;
    }
    return false;
}

function getEnrolledStudents($conn, $course_id) {
    // Try student_course
    $stmt = $conn->prepare(
        "SELECT u.id as student_id, u.username, u.email
         FROM student_course sc
         JOIN user u ON u.id = sc.student_id AND u.usertype='student'
         WHERE sc.course_id = ?
         ORDER BY u.username"
    );
    if ($stmt) {
        $stmt->bind_param('i', $course_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) { $rows[] = $r; }
        $stmt->close();
        if (!empty($rows)) return $rows;
    }
    // Fallback: student_enrollment
    $stmt = $conn->prepare(
        "SELECT u.id as student_id, u.username, u.email
         FROM student_enrollment se
         JOIN user u ON u.id = se.student_id AND u.usertype='student'
         WHERE se.course_id = ?
         ORDER BY u.username"
    );
    if ($stmt) {
        $stmt->bind_param('i', $course_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) { $rows[] = $r; }
        $stmt->close();
        return $rows;
    }
    return [];
}

// Handle create assessment
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    !isset($_POST['save_scores']) &&
    isset($_POST['title']) && isset($_POST['course_id'])
) {
    $course_id = intval($_POST['course_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $max_score = floatval($_POST['max_score'] ?? 100);
    $weight_str = isset($_POST['weight']) && $_POST['weight'] !== '' ? strval($_POST['weight']) : '';
    $assess_date_str = isset($_POST['assess_date']) && $_POST['assess_date'] !== '' ? $_POST['assess_date'] : '';
    $term_id = isset($_POST['term_id']) && $_POST['term_id'] !== '' ? intval($_POST['term_id']) : 0;

    // Normalise assessment type (Assignment 1, Assignment 2, End of Term Test, Other)
    $rawType = $_POST['type'] ?? 'other';
    $allowedTypes = ['assignment1', 'assignment2', 'end_test', 'other'];
    if (!in_array($rawType, $allowedTypes, true)) {
        $rawType = 'other';
    }
    $type = $rawType;

    // If title empty but special type chosen, default the title
    if ($title === '' && $type !== 'other') {
        if ($type === 'assignment1') $title = 'Assignment 1';
        elseif ($type === 'assignment2') $title = 'Assignment 2';
        elseif ($type === 'end_test') $title = 'End of Term Test';
    }

    if (!$lecturer_id || !$course_id || !$title) {
        $message = 'Please fill in all required fields.';
        $message_type = 'danger';
    } elseif (!lecturerOwnsCourse($conn, $lecturer_id, $course_id)) {
        $message = 'You are not authorized for the selected course.';
        $message_type = 'danger';
    } else {
        // Enforce at most one Assignment 1 / Assignment 2 / End Test per course+term
        if (in_array($type, ['assignment1', 'assignment2', 'end_test'], true) && $term_id > 0) {
            $check = $conn->prepare("SELECT id FROM ca_assessments WHERE course_id=? AND term_id=? AND type=? LIMIT 1");
            if ($check) {
                $check->bind_param('iis', $course_id, $term_id, $type);
                $check->execute();
                $resChk = $check->get_result();
                if ($resChk && $resChk->num_rows > 0) {
                    $message = 'This assessment type already exists for the selected course and term.';
                    $message_type = 'danger';
                    $check->close();
                    return;
                }
                $check->close();
            }
        }

        // Use NULLIF to convert empty strings to NULL for optional fields
        $stmt = $conn->prepare("INSERT INTO ca_assessments (course_id, title, type, max_score, weight, assess_date, term_id) VALUES (?,?,?, ?,NULLIF(?,''),NULLIF(?,''),?)");
        if ($stmt) {
            $stmt->bind_param('issdssi', $course_id, $title, $type, $max_score, $weight_str, $assess_date_str, $term_id);
            if ($stmt->execute()) {
                $message = 'Assessment created successfully.';
                $message_type = 'success';
                $_GET['course_id'] = $course_id; // keep context
            } else {
                $message = 'Error creating assessment: ' . htmlspecialchars($conn->error);
                $message_type = 'danger';
            }
            $stmt->close();
        } else {
            $message = 'Failed to prepare statement: ' . htmlspecialchars($conn->error);
            $message_type = 'danger';
        }
    }
}

// Handle save scores
if (isset($_POST['save_scores'])) {
    $assessment_id = intval($_POST['assessment_id'] ?? 0);
    $scores = $_POST['score'] ?? [];
    $remarks = $_POST['remarks'] ?? [];

    // Load assessment for validation and context
    $ass = null;
    if ($assessment_id) {
        $res = mysqli_query($conn, "SELECT * FROM ca_assessments WHERE id=" . $assessment_id . " LIMIT 1");
        $ass = $res ? mysqli_fetch_assoc($res) : null;
    }
    if (!$ass) {
        $message = 'Invalid assessment.';
        $message_type = 'danger';
    } elseif (!lecturerOwnsCourse($conn, $lecturer_id, intval($ass['course_id']))) {
        $message = 'You are not authorized to edit this assessment.';
        $message_type = 'danger';
    } else {
        // Upsert each score
        foreach ($scores as $student_id => $score_val) {
            $student_id = intval($student_id);
            if ($student_id <= 0) continue;
            $score_num = ($score_val === '' ? null : floatval($score_val));
            $remark_text = isset($remarks[$student_id]) ? trim($remarks[$student_id]) : null;
            if ($score_num === null) continue; // skip blanks

            // INSERT ... ON DUPLICATE KEY UPDATE
            $stmt = $conn->prepare("INSERT INTO ca_scores (assessment_id, student_id, score, remarks) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE score=VALUES(score), remarks=VALUES(remarks)");
            if ($stmt) {
                $stmt->bind_param('iids', $assessment_id, $student_id, $score_num, $remark_text);
                $stmt->execute();
                $stmt->close();
            }
        }
        $message = 'Scores saved.';
        $message_type = 'success';
        $_GET['assessment_id'] = $assessment_id; // remain on page
        $_GET['course_id'] = intval($ass['course_id']);
    }
}

// Load data for UI
$courses = $lecturer_id ? getLecturerCourses($conn, $lecturer_id) : [];
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$assessment_id = isset($_GET['assessment_id']) ? intval($_GET['assessment_id']) : 0;

// Load academic terms for selection
$termOptions = [];
$resT = mysqli_query($conn, "SELECT id, name, academic_year, is_current FROM academic_terms ORDER BY academic_year DESC, id ASC");
if ($resT) {
    while ($row = mysqli_fetch_assoc($resT)) {
        $label = $row['academic_year'] . ' - ' . $row['name'];
        if ($row['is_current']) { $label .= ' (Current)'; }
        $termOptions[$row['id']] = $label;
    }
}

// If assessment specified, ensure course_id follows it
if ($assessment_id && !$course_id) {
    $res = mysqli_query($conn, "SELECT course_id FROM ca_assessments WHERE id=$assessment_id");
    if ($res) { $row = mysqli_fetch_assoc($res); $course_id = intval($row['course_id'] ?? 0); }
}
?>

<?php include 'includes/lecturer-sidebar.php'; ?>

            <div class="page-title">Continuous Assessment</div>
            <div class="page-subtitle">Create assessments and enter scores for your students</div>

            <?php if ($message) { showAlert(htmlspecialchars($message), $message_type); } ?>

            <!-- Course selector -->
            <div class="card-modern" style="margin-bottom: 1.5rem;">
                <div class="card-modern-body">
                    <form method="get" class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label">Course</label>
                            <select name="course_id" class="form-select" required>
                                <option value="">Select course</option>
                                <?php foreach ($courses as $c): ?>
                                    <?php $sel = ($course_id == intval($c['id'])) ? 'selected' : ''; ?>
                                    <option value="<?php echo intval($c['id']); ?>" <?php echo $sel; ?>><?php echo htmlspecialchars($c['code'] . ' - ' . $c['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn-modern primary"><i class="bi bi-funnel"></i> Load</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($course_id && lecturerOwnsCourse($conn, $lecturer_id, $course_id)): ?>
                <!-- Create new assessment -->
                <?php startModernForm('lecturer_ca.php?course_id=' . $course_id, 'POST'); ?>
                    <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <?php 
                            $typeOptions = [
                                'assignment1' => 'Assignment 1',
                                'assignment2' => 'Assignment 2',
                                'end_test'    => 'End of Term Test',
                                'other'      => 'Other / Custom',
                            ];
                            formSelect('type', 'Assessment Type', $typeOptions, $_POST['type'] ?? 'other', true);
                            ?>
                        </div>
                        <div class="col-md-3">
                            <?php formInput('title', 'Assessment Title', 'text', '', false, 'e.g., Quiz 1'); ?>
                        </div>
                        <div class="col-md-2">
                            <?php formInput('max_score', 'Max Score', 'number', '100', true, 'e.g., 20'); ?>
                        </div>
                        <div class="col-md-2">
                            <?php formInput('weight', 'Weight (%)', 'number', '', false, 'optional'); ?>
                        </div>
                        <div class="col-md-2">
                            <?php formInput('assess_date', 'Date', 'date', '', false); ?>
                        </div>
                        <div class="col-md-2">
                            <?php formSelect('term_id', 'Term (optional)', $termOptions, $_POST['term_id'] ?? '', false); ?>
                        </div>
                    </div>
                <?php endModernForm('Create Assessment'); ?>

                <!-- Existing assessments -->
                <?php
                $assessments = [];
                $res = mysqli_query($conn, "SELECT a.*, t.name AS term_name, t.academic_year FROM ca_assessments a LEFT JOIN academic_terms t ON a.term_id = t.id WHERE a.course_id=$course_id ORDER BY a.assess_date IS NULL, a.assess_date DESC, a.id DESC");
                while ($res && $row = mysqli_fetch_assoc($res)) { $assessments[] = $row; }
                ?>
                <?php startModernTable('Assessments'); ?>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Max</th>
                        <th>Weight</th>
                        <th>Term</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($assessments)): ?>
                        <tr><td colspan="5" class="text-center text-muted" style="padding: 2rem;">No assessments yet.</td></tr>
                    <?php else: foreach ($assessments as $a): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($a['title']); ?></strong></td>
                            <td>
                                <?php
                                $typeRaw = $a['type'] ?? '';
                                if ($typeRaw === 'assignment1') {
                                    echo 'Assignment 1';
                                } elseif ($typeRaw === 'assignment2') {
                                    echo 'Assignment 2';
                                } elseif ($typeRaw === 'end_test') {
                                    echo 'End of Term Test';
                                } else {
                                    echo 'Other';
                                }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($a['assess_date'] ?: '—'); ?></td>
                            <td><?php echo htmlspecialchars($a['max_score']); ?></td>
                            <td><?php echo ($a['weight'] !== null && $a['weight'] !== '') ? htmlspecialchars($a['weight']) . '%' : '—'; ?></td>
                            <td><?php echo isset($a['term_name']) ? htmlspecialchars($a['academic_year'] . ' - ' . $a['term_name']) : '—'; ?></td>
                            <td>
                                <a href="lecturer_ca.php?course_id=<?php echo $course_id; ?>&assessment_id=<?php echo intval($a['id']); ?>" class="btn-modern primary" style="padding: 0.35rem 0.75rem; font-size: 0.85rem;">
                                    <i class="bi bi-pencil-square"></i> Enter Scores
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
                <?php endModernTable(); ?>
            <?php endif; ?>

            <?php if ($assessment_id): ?>
                <?php
                // Load assessment details
                $ass = null;
                $res = mysqli_query($conn, "SELECT * FROM ca_assessments WHERE id=$assessment_id LIMIT 1");
                $ass = $res ? mysqli_fetch_assoc($res) : null;
                if ($ass && lecturerOwnsCourse($conn, $lecturer_id, intval($ass['course_id']))) {
                    $course_id = intval($ass['course_id']);
                    $students = getEnrolledStudents($conn, $course_id);

                    // Existing scores map
                    $score_map = [];
                    $res2 = mysqli_query($conn, "SELECT student_id, score, remarks FROM ca_scores WHERE assessment_id=$assessment_id");
                    while ($res2 && $r = mysqli_fetch_assoc($res2)) { $score_map[intval($r['student_id'])] = $r; }
                ?>
                <div class="page-subtitle" style="margin-top:2rem;">Enter scores for: <strong><?php echo htmlspecialchars($ass['title']); ?></strong> (Max: <?php echo htmlspecialchars($ass['max_score']); ?>)</div>
                <div class="card-modern">
                    <div class="card-modern-body">
                        <form method="post">
                            <input type="hidden" name="assessment_id" value="<?php echo $assessment_id; ?>">
                            <input type="hidden" name="save_scores" value="1">
                            <div class="table-responsive-modern">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Email</th>
                                            <th>Score</th>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($students)): ?>
                                            <tr><td colspan="4" class="text-center text-muted" style="padding: 2rem;">No enrolled students found.</td></tr>
                                        <?php else: foreach ($students as $s): $sid = intval($s['student_id']); $prev = $score_map[$sid] ?? null; ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($s['username']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($s['email'] ?? ''); ?></td>
                                                <td style="max-width: 120px;">
                                                    <input type="number" step="0.01" name="score[<?php echo $sid; ?>]" class="form-control" value="<?php echo htmlspecialchars($prev['score'] ?? ''); ?>" placeholder="0 - <?php echo htmlspecialchars($ass['max_score']); ?>">
                                                </td>
                                                <td>
                                                    <input type="text" name="remarks[<?php echo $sid; ?>]" class="form-control" value="<?php echo htmlspecialchars($prev['remarks'] ?? ''); ?>" placeholder="optional">
                                                </td>
                                            </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="form-group" style="margin-top: 1rem;">
                                <button type="submit" class="btn-modern primary"><i class="bi bi-save"></i> Save Scores</button>
                                <?php if ($course_id): ?>
                                <a href="lecturer_ca.php?course_id=<?php echo $course_id; ?>" class="btn-modern secondary" style="background:#6c757d;"><i class="bi bi-arrow-left"></i> Back</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                <?php } ?>
            <?php endif; ?>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
