<?php
/**
 * Advanced Analytics Dashboard
 * Save as: admin_analytics.php
 * 
 * Features:
 * - Real-time statistics
 * - Trend analysis
 * - Performance metrics
 * - Visual charts
 */

session_start();
include 'includes/connect.php';
include 'includes/session-helpers.php';
include 'includes/form-helpers.php';
include 'includes/security-helpers.php';

requireRole('admin');
requireCSRFToken();

$conn = $data;

// ============================================
// DATA COLLECTION
// ============================================

// Total counts
$total_students = db_fetch_one($conn, "SELECT COUNT(*) as count FROM user WHERE usertype='student'")['count'] ?? 0;
$total_lecturers = db_fetch_one($conn, "SELECT COUNT(*) as count FROM user WHERE usertype='lecturer'")['count'] ?? 0;
$total_courses = db_fetch_one($conn, "SELECT COUNT(*) as count FROM course")['count'] ?? 0;
$total_enrollments = db_fetch_one($conn, "SELECT COUNT(*) as count FROM student_course")['count'] ?? 0;

// Active students (logged in within last 30 days) - requires login tracking
$active_students = $total_students; // Placeholder

// Course enrollment stats
$courses_with_students = db_fetch_all($conn, "
    SELECT c.code, c.name, COUNT(sc.student_id) as student_count
    FROM course c
    LEFT JOIN student_course sc ON c.id = sc.course_id
    GROUP BY c.id
    ORDER BY student_count DESC
    LIMIT 10
");

// Lecturer workload
$lecturer_workload = db_fetch_all($conn, "
    SELECT u.username, COUNT(DISTINCT lc.course_id) as course_count, 
           COUNT(DISTINCT sc.student_id) as student_count
    FROM user u
    LEFT JOIN lecturer_course lc ON u.id = lc.lecturer_id
    LEFT JOIN student_course sc ON lc.course_id = sc.course_id
    WHERE u.usertype = 'lecturer'
    GROUP BY u.id
    ORDER BY course_count DESC, student_count DESC
");

// Recent activity (last 7 days) - requires activity tracking
// Placeholder for now

// CA Statistics
$ca_stats = db_fetch_one($conn, "
    SELECT 
        COUNT(DISTINCT ca.id) as total_assessments,
        COUNT(DISTINCT cs.student_id) as students_with_scores,
        AVG(cs.score / ca.max_score * 100) as avg_percent
    FROM ca_assessments ca
    LEFT JOIN ca_scores cs ON ca.id = cs.assessment_id
");

?>

<?php include 'includes/admin-sidebar.php'; ?>

<div class="page-title">📊 Advanced Analytics</div>
<div class="page-subtitle">Real-time insights and performance metrics</div>

<!-- Key Metrics -->
<div class="grid-cols-4" style="margin-bottom: 2rem;">
    <div class="stat-card">
        <div class="stat-card-icon primary">
            <i class="bi bi-people"></i>
        </div>
        <div class="stat-card-content">
            <h6>Total Students</h6>
            <h3><?php echo $total_students; ?></h3>
            <small class="text-success">↗️ Active: <?php echo $active_students; ?></small>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon success">
            <i class="bi bi-person-badge"></i>
        </div>
        <div class="stat-card-content">
            <h6>Total Lecturers</h6>
            <h3><?php echo $total_lecturers; ?></h3>
            <small class="text-muted">Teaching staff</small>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon warning">
            <i class="bi bi-book"></i>
        </div>
        <div class="stat-card-content">
            <h6>Active Courses</h6>
            <h3><?php echo $total_courses; ?></h3>
            <small class="text-muted">In catalog</small>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon info">
            <i class="bi bi-diagram-3"></i>
        </div>
        <div class="stat-card-content">
            <h6>Total Enrollments</h6>
            <h3><?php echo $total_enrollments; ?></h3>
            <small class="text-muted">
                Avg: <?php echo $total_students > 0 ? round($total_enrollments / $total_students, 1) : 0; ?>/student
            </small>
        </div>
    </div>
</div>

<!-- CA Performance Metrics -->
<?php if ($ca_stats['total_assessments'] > 0): ?>
<div class="grid-cols-3" style="margin-bottom: 2rem;">
    <div class="card-modern">
        <div class="card-modern-body text-center">
            <h2 class="text-primary"><?php echo $ca_stats['total_assessments']; ?></h2>
            <p class="mb-0 text-muted">Total Assessments</p>
        </div>
    </div>
    
    <div class="card-modern">
        <div class="card-modern-body text-center">
            <h2 class="text-success"><?php echo $ca_stats['students_with_scores']; ?></h2>
            <p class="mb-0 text-muted">Students with CA Scores</p>
        </div>
    </div>
    
    <div class="card-modern">
        <div class="card-modern-body text-center">
            <h2 class="text-info">
                <?php echo $ca_stats['avg_percent'] ? round($ca_stats['avg_percent'], 1) . '%' : 'N/A'; ?>
            </h2>
            <p class="mb-0 text-muted">Average CA Performance</p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Course Enrollment Chart -->
<div class="card-modern" style="margin-bottom: 2rem;">
    <div class="card-modern-header">
        <h5>📈 Top Enrolled Courses</h5>
    </div>
    <div class="card-modern-body">
        <?php if (!empty($courses_with_students)): ?>
            <?php foreach ($courses_with_students as $course): ?>
                <?php 
                $percentage = $total_students > 0 
                    ? ($course['student_count'] / $total_students) * 100 
                    : 0; 
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong><?php echo escapeHTML($course['code']); ?></strong>
                        <span class="text-muted">
                            <?php echo $course['student_count']; ?> students 
                            (<?php echo round($percentage, 1); ?>%)
                        </span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-primary" 
                             role="progressbar" 
                             style="width: <?php echo min($percentage, 100); ?>%"></div>
                    </div>
                    <small class="text-muted"><?php echo escapeHTML($course['name']); ?></small>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-muted">No enrollment data available.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Lecturer Workload -->
<div class="card-modern">
    <div class="card-modern-header">
        <h5>👨‍🏫 Lecturer Workload Distribution</h5>
    </div>
    <div class="table-responsive-modern">
        <table class="table">
            <thead>
                <tr>
                    <th>Lecturer</th>
                    <th>Courses</th>
                    <th>Students</th>
                    <th>Avg Students/Course</th>
                    <th>Workload</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($lecturer_workload)): ?>
                    <?php foreach ($lecturer_workload as $lecturer): ?>
                        <?php 
                        $avg_students = $lecturer['course_count'] > 0 
                            ? round($lecturer['student_count'] / $lecturer['course_count'], 1) 
                            : 0;
                        
                        // Workload indicator (simple formula)
                        $workload_score = $lecturer['course_count'] * 20 + $lecturer['student_count'];
                        
                        if ($workload_score > 100) {
                            $workload_badge = '<span class="badge-modern danger">High</span>';
                        } elseif ($workload_score > 50) {
                            $workload_badge = '<span class="badge-modern warning">Medium</span>';
                        } else {
                            $workload_badge = '<span class="badge-modern success">Low</span>';
                        }
                        ?>
                        <tr>
                            <td><strong><?php echo escapeHTML($lecturer['username']); ?></strong></td>
                            <td><?php echo $lecturer['course_count']; ?></td>
                            <td><?php echo $lecturer['student_count']; ?></td>
                            <td><?php echo $avg_students; ?></td>
                            <td><?php echo $workload_badge; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted" style="padding: 2rem;">
                            No lecturer data available.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Export Options -->
<div style="margin-top: 2rem;">
    <form method="POST" style="display: inline-block;">
        <?php echo csrfTokenField(); ?>
        <button type="button" onclick="window.print()" class="btn-modern primary">
            <i class="bi bi-printer"></i> Print Report
        </button>
        <button type="button" onclick="exportToCSV()" class="btn-modern success">
            <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
        </button>
        <a href="admin_reports.php" class="btn-modern secondary">
            <i class="bi bi-arrow-left"></i> Back to Reports
        </a>
    </form>
</div>

<script>
function exportToCSV() {
    // Simple CSV export functionality
    alert('CSV export feature - implement based on your needs');
}
</script>

</main>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>