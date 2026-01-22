<?php
session_start();
include 'includes/connect.php';
include 'includes/session-helpers.php';
include 'includes/form-helpers.php';
include 'includes/grade-helpers.php';

requireRole('lecturer');

$conn = $data;
$lecturer_id = getLecturerId($conn);
$PASS_PERCENT = 45.0;

// -----------------------------------------------------------------------------
// Helper: get lecturer courses (same logic as lecturer_ca.php)
// -----------------------------------------------------------------------------

function getLecturerCoursesForReports($conn, $lecturer_id) {
    $courses = [];
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
        while ($row = $res->fetch_assoc()) { $courses[] = $row; }
        $stmt->close();
    }
    return $courses;
}

// -----------------------------------------------------------------------------
// Filters: academic year, term, course
// -----------------------------------------------------------------------------

$selected_year = isset($_GET['year']) && $_GET['year'] !== '' ? intval($_GET['year']) : null;
$selected_term_id = isset($_GET['term_id']) && $_GET['term_id'] !== '' ? intval($_GET['term_id']) : null;
$selected_course_id = isset($_GET['course_id']) && $_GET['course_id'] !== '' ? intval($_GET['course_id']) : null;
$export = $_GET['export'] ?? null; // 'courses' or 'at_risk'

// Academic years & terms
$available_years = [];
$available_terms = [];
$resT = mysqli_query($conn, "SELECT id, name, academic_year, is_current FROM academic_terms ORDER BY academic_year DESC, id ASC");
if ($resT) {
    while ($rowT = mysqli_fetch_assoc($resT)) {
        $y = (int)$rowT['academic_year'];
        if (!in_array($y, $available_years, true)) {
            $available_years[] = $y;
        }
        $available_terms[] = $rowT;
    }
}

if ($selected_year === null && !empty($available_years)) {
    $selected_year = $available_years[0];
}

// Lecturer's courses for filter
$lecturerCourses = $lecturer_id ? getLecturerCoursesForReports($conn, $lecturer_id) : [];
$course_options = [];
foreach ($lecturerCourses as $c) {
    $course_options[(int)$c['id']] = $c['code'] . ' - ' . $c['name'];
}

// -----------------------------------------------------------------------------
// Load CA data for this lecturer's courses
// -----------------------------------------------------------------------------

$assessmentsByCourse = [];
$scoresByCourseAndStudent = [];
$courseMeta = [];
$studentMeta = [];

$where = [];
$where[] = '1=1';
$where[] = ' (c.lecturer_id = ' . intval($lecturer_id) . ' OR lc.lecturer_id = ' . intval($lecturer_id) . ')';

if ($selected_year !== null) {
    $where[] = ' (t.academic_year = ' . intval($selected_year) . ' OR t.academic_year IS NULL)';
}
if ($selected_term_id !== null) {
    $where[] = ' a.term_id = ' . intval($selected_term_id);
}
if ($selected_course_id !== null) {
    $where[] = ' c.id = ' . intval($selected_course_id);
}
$whereSql = implode(' AND', $where);

$sql = "
SELECT
  cs.student_id,
  stu.username AS student_name,
  a.course_id,
  a.id AS assessment_id,
  a.max_score,
  a.weight,
  cs.score,
  c.code AS course_code,
  c.name AS course_name
FROM ca_scores cs
JOIN ca_assessments a ON a.id = cs.assessment_id
JOIN course c ON c.id = a.course_id
JOIN user stu ON stu.id = cs.student_id AND stu.usertype='student'
LEFT JOIN lecturer_course lc ON lc.course_id = c.id
LEFT JOIN academic_terms t ON t.id = a.term_id
WHERE $whereSql
";

$res = mysqli_query($conn, $sql);
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $course_id = (int)$row['course_id'];
        $student_id = (int)$row['student_id'];
        $assessment_id = (int)$row['assessment_id'];

        if (!isset($assessmentsByCourse[$course_id])) {
            $assessmentsByCourse[$course_id] = [];
        }
        if (!isset($assessmentsByCourse[$course_id][$assessment_id])) {
            $assessmentsByCourse[$course_id][$assessment_id] = [
                'id' => $assessment_id,
                'max_score' => (float)$row['max_score'],
                'weight' => $row['weight'],
            ];
        }

        if (!isset($scoresByCourseAndStudent[$course_id])) {
            $scoresByCourseAndStudent[$course_id] = [];
        }
        if (!isset($scoresByCourseAndStudent[$course_id][$student_id])) {
            $scoresByCourseAndStudent[$course_id][$student_id] = [];
        }
        $scoresByCourseAndStudent[$course_id][$student_id][$assessment_id] = (float)$row['score'];

        if (!isset($courseMeta[$course_id])) {
            $courseMeta[$course_id] = [
                'code' => $row['course_code'],
                'name' => $row['course_name'],
            ];
        }
        if (!isset($studentMeta[$student_id])) {
            $studentMeta[$student_id] = [
                'username' => $row['student_name'],
            ];
        }
    }
}

// -----------------------------------------------------------------------------
// Compute aggregates
// -----------------------------------------------------------------------------

$courseStats = [];
$global_total = 0;
$global_pass = 0;
$global_sum_percent = 0.0;
$gradeBuckets = [
    'A' => 0,
    'B' => 0,
    'C' => 0,
    'D' => 0,
    'F' => 0,
];
$atRiskStudents = [];

foreach ($scoresByCourseAndStudent as $course_id => $studentsScores) {
    $assessments = array_values($assessmentsByCourse[$course_id] ?? []);
    if (empty($assessments)) continue;

    if (!isset($courseStats[$course_id])) {
        $courseStats[$course_id] = [
            'sum_percent' => 0.0,
            'count' => 0,
            'pass_count' => 0,
            'gradeBuckets' => [
                'A' => 0,
                'B' => 0,
                'C' => 0,
                'D' => 0,
                'F' => 0,
            ],
        ];
    }

    foreach ($studentsScores as $student_id => $scoreMap) {
        $calc = compute_ca_percent($assessments, $scoreMap);
        if ($calc['percent'] === null) {
            continue;
        }
        $percent = (float)$calc['percent'];
        $letter = letter_grade($percent);

        $courseStats[$course_id]['sum_percent'] += $percent;
        $courseStats[$course_id]['count'] += 1;
        $courseStats[$course_id]['gradeBuckets'][$letter] = ($courseStats[$course_id]['gradeBuckets'][$letter] ?? 0) + 1;

        $global_sum_percent += $percent;
        $global_total += 1;
        $gradeBuckets[$letter] = ($gradeBuckets[$letter] ?? 0) + 1;

        if ($percent >= $PASS_PERCENT) {
            $courseStats[$course_id]['pass_count'] += 1;
            $global_pass += 1;
        } else {
            $atRiskStudents[] = [
                'student_id' => $student_id,
                'username' => $studentMeta[$student_id]['username'] ?? ('ID ' . $student_id),
                'course_code' => $courseMeta[$course_id]['code'] ?? '',
                'course_name' => $courseMeta[$course_id]['name'] ?? '',
                'percent' => $percent,
                'letter' => $letter,
            ];
        }
    }
}

usort($atRiskStudents, function ($a, $b) {
    if ($a['percent'] == $b['percent']) return 0;
    return ($a['percent'] < $b['percent']) ? -1 : 1;
});

// -----------------------------------------------------------------------------
// CSV export
// -----------------------------------------------------------------------------

if ($export === 'courses') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="lecturer_course_performance.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Course Code', 'Course Name', 'Students with CA', 'Average %', 'Pass Rate %']);

    foreach ($courseStats as $course_id => $st) {
        $meta = $courseMeta[$course_id] ?? ['code' => '', 'name' => ''];
        if ($st['count'] === 0) continue;
        $avg = $st['sum_percent'] / $st['count'];
        $passRate = $st['count'] > 0 ? ($st['pass_count'] / $st['count']) * 100.0 : 0.0;
        fputcsv($out, [
            $meta['code'],
            $meta['name'],
            $st['count'],
            round($avg, 2),
            round($passRate, 2),
        ]);
    }
    fclose($out);
    exit;
}

if ($export === 'at_risk') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="lecturer_at_risk_students.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Student', 'Course Code', 'Course Name', 'Final %', 'Grade']);
    foreach ($atRiskStudents as $row) {
        fputcsv($out, [
            $row['username'],
            $row['course_code'],
            $row['course_name'],
            round($row['percent'], 2),
            $row['letter'],
        ]);
    }
    fclose($out);
    exit;
}

// -----------------------------------------------------------------------------
// HTML OUTPUT
// -----------------------------------------------------------------------------

include 'includes/lecturer-sidebar.php';
?>

            <div class="page-title">Course Analytics</div>
            <div class="page-subtitle">Performance analytics for your courses. Passing threshold: <?php echo $PASS_PERCENT; ?>%.</div>

            <div class="card-modern" style="margin-bottom: 1.5rem;">
                <div class="card-modern-body">
                    <form method="get" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="year" class="form-label">Academic Year</label>
                            <select name="year" id="year" class="form-select" onchange="this.form.submit()">
                                <option value="">All Years</option>
                                <?php foreach ($available_years as $y): ?>
                                    <option value="<?php echo $y; ?>" <?php if ($selected_year === (int)$y) echo 'selected'; ?>>
                                        <?php echo $y; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="term_id" class="form-label">Term</label>
                            <select name="term_id" id="term_id" class="form-select" onchange="this.form.submit()">
                                <option value="">All Terms</option>
                                <?php foreach ($available_terms as $t): ?>
                                    <?php if ($selected_year !== null && (int)$t['academic_year'] !== (int)$selected_year) continue; ?>
                                    <option value="<?php echo (int)$t['id']; ?>" <?php if ($selected_term_id === (int)$t['id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($t['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="course_id" class="form-label">Course</label>
                            <select name="course_id" id="course_id" class="form-select" onchange="this.form.submit()">
                                <option value="">All My Courses</option>
                                <?php foreach ($course_options as $cid => $ctitle): ?>
                                    <option value="<?php echo $cid; ?>" <?php if ($selected_course_id === (int)$cid) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($ctitle); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            <?php
            $global_avg = $global_total > 0 ? ($global_sum_percent / $global_total) : null;
            $global_pass_rate = $global_total > 0 ? ($global_pass / $global_total) * 100.0 : null;
            ?>

            <div class="grid-cols-3">
                <div class="stat-card">
                    <div class="stat-card-icon primary">
                        <i class="bi bi-book"></i>
                    </div>
                    <div class="stat-card-content">
                        <h6>Courses with CA</h6>
                        <h3><?php echo count($courseStats); ?></h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-icon success">
                        <i class="bi bi-graph-up"></i>
                    </div>
                    <div class="stat-card-content">
                        <h6>Average CA %</h6>
                        <h3><?php echo $global_avg !== null ? round($global_avg, 2) . '%' : '—'; ?></h3>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-icon info">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div class="stat-card-content">
                        <h6>Pass Rate (≥ <?php echo $PASS_PERCENT; ?>%)</h6>
                        <h3><?php echo $global_pass_rate !== null ? round($global_pass_rate, 2) . '%' : '—'; ?></h3>
                    </div>
                </div>
            </div>

            <div style="margin-top: 2rem;" class="card-modern">
                <div class="card-modern-body">
                    <h5>Grade Distribution</h5>
                    <?php
                    $totalGrades = array_sum($gradeBuckets);
                    if ($totalGrades > 0):
                    ?>
                        <?php foreach ($gradeBuckets as $grade => $count):
                            $pct = $totalGrades > 0 ? ($count / $totalGrades) * 100.0 : 0;
                        ?>
                            <div class="mb-1">
                                <strong><?php echo $grade; ?></strong>
                                <div class="progress" style="height: 0.75rem;">
                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $pct; ?>%" aria-valuenow="<?php echo round($pct, 1); ?>" aria-valuemin="0" aria-valuemax="100">
                                        <?php echo $count; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <em>No CA data available for the selected filters.</em>
                    <?php endif; ?>
                </div>
            </div>

            <div style="margin-top: 2rem;" class="d-flex justify-content-between align-items-center">
                <h4 class="section-title mb-0">Course Performance</h4>
                <div>
                    <a href="lecturer_reports.php?<?php echo http_build_query(array_merge($_GET, ['export' => 'courses'])); ?>" class="btn btn-outline-secondary btn-sm">
                        Export Courses CSV
                    </a>
                </div>
            </div>

            <?php startModernTable('Course Performance'); ?>
                <tr>
                    <th>Course</th>
                    <th>Students with CA</th>
                    <th>Average %</th>
                    <th>Pass Rate</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($courseStats)): ?>
                    <tr><td colspan="4" class="text-center text-muted" style="padding: 1.5rem;">No CA records for the selected filters.</td></tr>
                <?php else: ?>
                    <?php foreach ($courseStats as $course_id => $st):
                        $meta = $courseMeta[$course_id] ?? ['code' => '', 'name' => ''];
                        if ($st['count'] === 0) continue;
                        $avg = $st['sum_percent'] / $st['count'];
                        $passRate = $st['count'] > 0 ? ($st['pass_count'] / $st['count']) * 100.0 : 0.0;
                    ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars(($meta['code'] ?? '') . ' - ' . ($meta['name'] ?? '')); ?></strong></td>
                            <td><?php echo $st['count']; ?></td>
                            <td><?php echo round($avg, 2); ?>%</td>
                            <td><?php echo round($passRate, 2); ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php endModernTable(); ?>

            <div style="margin-top: 2rem;" class="d-flex justify-content-between align-items-center">
                <h4 class="section-title mb-0">At-Risk Students (Final CA &lt; <?php echo $PASS_PERCENT; ?>%)</h4>
                <div>
                    <a href="lecturer_reports.php?<?php echo http_build_query(array_merge($_GET, ['export' => 'at_risk'])); ?>" class="btn btn-outline-secondary btn-sm">
                        Export At-Risk CSV
                    </a>
                </div>
            </div>

            <div class="card-modern" style="margin-top: 0.5rem;">
                <div class="card-modern-body">
                    <?php if (empty($atRiskStudents)): ?>
                        <em>No at-risk students for the selected filters.</em>
                    <?php else: ?>
                        <div class="table-responsive-modern">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Course</th>
                                        <th>Final %</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($atRiskStudents as $row): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                                            <td><?php echo htmlspecialchars($row['course_code'] . ' - ' . $row['course_name']); ?></td>
                                            <td><?php echo round($row['percent'], 2); ?>%</td>
                                            <td><?php echo htmlspecialchars($row['letter']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-3">
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    Print Analytics Report
                </button>
            </div>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
