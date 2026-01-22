<?php
session_start();
include 'includes/connect.php';
include 'includes/session-helpers.php';
include 'includes/form-helpers.php';
include 'includes/grade-helpers.php';

requireRole('student');

$conn = $data;
$student_id = getStudentId($conn);

// Ensure CA tables exist (no-op if already created)
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS ca_assessments (id INT AUTO_INCREMENT PRIMARY KEY, course_id INT NOT NULL, title VARCHAR(100) NOT NULL, type VARCHAR(30) NULL, max_score DECIMAL(7,2) NOT NULL DEFAULT 100.00, weight DECIMAL(6,3) NULL, assess_date DATE NULL, term_id INT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS ca_scores (id INT AUTO_INCREMENT PRIMARY KEY, assessment_id INT NOT NULL, student_id INT NOT NULL, score DECIMAL(7,2) NOT NULL, remarks VARCHAR(255) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uniq_assessment_student (assessment_id, student_id), INDEX idx_student (student_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

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

// Helper: get student's courses
function getStudentCourses($conn, $student_id) {
    $stmt = $conn->prepare(
        "SELECT c.id, c.code, c.name
         FROM student_course sc
         JOIN course c ON c.id = sc.course_id
         WHERE sc.student_id = ?
         ORDER BY c.code"
    );
    if ($stmt) {
        $stmt->bind_param('i', $student_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) { $rows[] = $r; }
        $stmt->close();
        if (!empty($rows)) return $rows;
    }
    $stmt = $conn->prepare(
        "SELECT c.id, c.code, c.name
         FROM student_enrollment se
         JOIN course c ON c.id = se.course_id
         WHERE se.student_id = ?
         ORDER BY c.code"
    );
    if ($stmt) {
        $stmt->bind_param('i', $student_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) { $rows[] = $r; }
        $stmt->close();
        return $rows;
    }
    return [];
}

$courses = $student_id ? getStudentCourses($conn, $student_id) : [];
$transcript_rows = [];

// Build list of available academic years & terms for this student
$available_years = [];
$available_terms = [];
$selected_year = isset($_GET['year']) && $_GET['year'] !== '' ? intval($_GET['year']) : null;
$selected_term_id = isset($_GET['term_id']) && $_GET['term_id'] !== '' ? intval($_GET['term_id']) : null;

if ($student_id) {
    $sqlTerms = "SELECT t.id, t.name, t.academic_year, t.is_current FROM academic_terms t JOIN student_terms st ON st.term_id = t.id WHERE st.student_id = " . intval($student_id) . " ORDER BY t.academic_year DESC, t.id ASC";
    $resT = mysqli_query($conn, $sqlTerms);
    if ($resT) {
        while ($rowT = mysqli_fetch_assoc($resT)) {
            $y = (int)$rowT['academic_year'];
            if (!in_array($y, $available_years, true)) {
                $available_years[] = $y;
            }
            $available_terms[] = $rowT;
        }
    }
}

// If no specific year chosen, default to most recent available year (if any)
if ($selected_year === null && !empty($available_years)) {
    $selected_year = $available_years[0];
}

// If no specific term chosen, try to pick current term for that year or first in list
if ($selected_term_id === null && !empty($available_terms)) {
    foreach ($available_terms as $t) {
        if ((int)$t['academic_year'] === (int)$selected_year && (int)$t['is_current'] === 1) {
            $selected_term_id = (int)$t['id'];
            break;
        }
    }
    if ($selected_term_id === null) {
        foreach ($available_terms as $t) {
            if ((int)$t['academic_year'] === (int)$selected_year) {
                $selected_term_id = (int)$t['id'];
                break;
            }
        }
    }
}

// Build transcript rows (per course) for the selected year/term filter
foreach ($courses as $c) {
    $cid = intval($c['id']);

    // Fetch assessments for this course (optionally filtered by selected year and term)
    $assessments = [];
    $sqlAssess = "SELECT a.* FROM ca_assessments a WHERE a.course_id=$cid";
    if ($selected_year !== null) {
        $sqlAssess .= " AND (a.assess_date IS NULL OR YEAR(a.assess_date)=" . intval($selected_year) . ")";
    }
    if ($selected_term_id !== null) {
        $sqlAssess .= " AND a.term_id=" . intval($selected_term_id);
    }
    $sqlAssess .= " ORDER BY a.assess_date IS NULL, a.assess_date DESC, a.id DESC";
    $res = mysqli_query($conn, $sqlAssess);
    while ($res && $row = mysqli_fetch_assoc($res)) { $assessments[] = $row; }

    // Scores for this student across these assessments
    $scores = [];
    if (!empty($assessments)) {
        $ids = array_map('intval', array_column($assessments, 'id'));
        $idlist = implode(',', $ids);
        $res2 = mysqli_query($conn, "SELECT assessment_id, score FROM ca_scores WHERE student_id=".$student_id." AND assessment_id IN (".$idlist.")");
        while ($res2 && $r = mysqli_fetch_assoc($res2)) { $scores[intval($r['assessment_id'])] = $r['score']; }
    }

    $calc = compute_ca_percent($assessments, $scores);
    $letter = letter_grade($calc['percent']);

    $transcript_rows[] = [
        'code' => $c['code'],
        'name' => $c['name'],
        'percent' => $calc['percent'],
        'mode' => $calc['mode'],
        'letter' => $letter,
    ];
}

// Determine if we have any transcript data
$has_transcript_data = false;
foreach ($transcript_rows as $row) {
    if ($row['percent'] !== null) {
        $has_transcript_data = true;
        break;
    }
}

?>

<?php include 'includes/student-sidebar.php'; ?>

            <div class="page-title">Transcript / Annual Results</div>
            <div class="page-subtitle">
                View your transcript summary and final yearly results.
            </div>

            <?php if (!empty($available_years)): ?>
                <form method="get" class="row g-2 align-items-end mb-3">
                    <div class="col-auto">
                        <label for="year" class="form-label mb-0">Academic Year</label>
                        <select name="year" id="year" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($available_years as $y): ?>
                                <option value="<?php echo $y; ?>" <?php if ($selected_year === (int)$y) echo 'selected'; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <label for="term_id" class="form-label mb-0">Term</label>
                        <select name="term_id" id="term_id" class="form-select" onchange="this.form.submit()">
                            <option value="">All Terms</option>
                            <?php foreach ($available_terms as $t): ?>
                                <?php if ((int)$t['academic_year'] !== (int)$selected_year) continue; ?>
                                <option value="<?php echo (int)$t['id']; ?>" <?php if ($selected_term_id === (int)$t['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($t['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            <?php endif; ?>

            <div class="mb-3">
                <a href="student_results.php" class="btn btn-outline-secondary">
                    &laquo; Back to My Results
                </a>
                <button type="button" class="btn btn-primary" onclick="window.print()" style="margin-left: 0.5rem;">
                    Print Transcript
                </button>
            </div>

            <div class="card-modern" style="margin-top: 0.5rem;">
                <div class="card-modern-body">
                    <?php if (empty($transcript_rows) || !$has_transcript_data): ?>
                        <em>No transcript data is available yet.</em>
                    <?php else: ?>
                        <div class="table-responsive-modern">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Course</th>
                                        <th>Final %</th>
                                        <th>Grade</th>
                                        <th>Mode</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($transcript_rows as $row): ?>
                                    <?php if ($row['percent'] === null) continue; ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['code'] . ' - ' . $row['name']); ?></td>
                                        <td><?php echo round($row['percent'], 2); ?>%</td>
                                        <td><?php echo htmlspecialchars($row['letter']); ?></td>
                                        <td><?php echo $row['mode'] === 'weighted' ? 'Weighted CA' : ($row['mode'] === 'plain' ? 'Plain Total' : 'N/A'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php
            // Final annual summary: combine First/Second/Third term for the selected academic year
            $annual_rows = [];
            if ($selected_year !== null && !empty($courses)) {
                // Collect terms for the selected year
                $year_terms = [];
                foreach ($available_terms as $t) {
                    if ((int)$t['academic_year'] === (int)$selected_year) {
                        $year_terms[] = $t;
                    }
                }

                if (!empty($year_terms)) {
                    foreach ($courses as $c) {
                        $cid = intval($c['id']);

                        // Prepare per-term buckets (First/Second/Third term)
                        $per_term = [
                            'First Term' => null,
                            'Second Term' => null,
                            'Third Term' => null,
                        ];

                        foreach ($year_terms as $t) {
                            $tid = (int)$t['id'];
                            $tname = $t['name'];
                            if (!array_key_exists($tname, $per_term)) {
                                // Ignore unknown term names for this summary
                                continue;
                            }

                            // Fetch assessments for this course & term
                            $assessT = [];
                            $sqlT = "SELECT * FROM ca_assessments WHERE course_id=$cid AND term_id=$tid";
                            $resT = mysqli_query($conn, $sqlT);
                            while ($resT && $rowT = mysqli_fetch_assoc($resT)) { $assessT[] = $rowT; }
                            if (empty($assessT)) continue;

                            // Scores for this student in this term
                            $scoresT = [];
                            $idsT = array_map('intval', array_column($assessT, 'id'));
                            $idlistT = implode(',', $idsT);
                            $resS = mysqli_query($conn, "SELECT assessment_id, score FROM ca_scores WHERE student_id=".$student_id." AND assessment_id IN (".$idlistT.")");
                            while ($resS && $rS = mysqli_fetch_assoc($resS)) { $scoresT[intval($rS['assessment_id'])] = $rS['score']; }

                            $calcT = compute_ca_percent($assessT, $scoresT);
                            if ($calcT['percent'] !== null) {
                                $per_term[$tname] = $calcT['percent'];
                            }
                        }

                        // Compute final annual percent as average of available term percents
                        $valid_percents = array_values(array_filter($per_term, function($v) { return $v !== null; }));
                        if (!empty($valid_percents)) {
                            $final_percent = array_sum($valid_percents) / count($valid_percents);
                            $final_grade = letter_grade($final_percent);

                            $annual_rows[] = [
                                'code' => $c['code'],
                                'name' => $c['name'],
                                'terms' => $per_term,
                                'final' => $final_percent,
                                'grade' => $final_grade,
                            ];
                        }
                    }
                }
            }
            ?>

            <?php if (!empty($annual_rows)): ?>
                <div class="page-title" style="margin-top: 2rem;">Final Year Summary (All Terms)</div>
                <div class="page-subtitle">Final annual score per course, computed as the average of available term percentages (First/Second/Third Term).</div>

                <div class="card-modern" style="margin-top: 0.5rem;">
                    <div class="card-modern-body">
                        <div class="table-responsive-modern">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Course</th>
                                        <th>First Term %</th>
                                        <th>Second Term %</th>
                                        <th>Third Term %</th>
                                        <th>Final %</th>
                                        <th>Final Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($annual_rows as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['code'] . ' - ' . $row['name']); ?></td>
                                        <td><?php echo $row['terms']['First Term'] !== null ? round($row['terms']['First Term'], 2) . '%' : '—'; ?></td>
                                        <td><?php echo $row['terms']['Second Term'] !== null ? round($row['terms']['Second Term'], 2) . '%' : '—'; ?></td>
                                        <td><?php echo $row['terms']['Third Term'] !== null ? round($row['terms']['Third Term'], 2) . '%' : '—'; ?></td>
                                        <td><?php echo round($row['final'], 2); ?>%</td>
                                        <td><?php echo htmlspecialchars($row['grade']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
