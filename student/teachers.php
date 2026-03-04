<?php
// REUSE EXISTING SYSTEM PATTERNS - consistent with admin/teacher modules
require_once $_SERVER['DOCUMENT_ROOT'] . '\config\SessionManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '\config\Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '\classes\Teacher.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '\includes\functions.php';

SessionManager::requireStudent();

// REUSE EXISTING DATABASE CONNECTION PATTERN
$db = new Database();
$conn = $db->connect();

// REUSE EXISTING TEACHER CLASS - same as admin module
$teacherObj = new Teacher($conn);

$page_title = 'Teacher Directory';

// AJAX search handler - REUSE existing teacher search functionality
if (isset($_POST['ajax_search'])) {
    $search = $_POST['search'] ?? '';

    // Use existing Teacher class search method
    if ($search) {
        $result = $teacherObj->search($search);
    } else {
        $result = $teacherObj->getAll('active');
    }

    if ($result->num_rows > 0) {
        while ($r = $result->fetch_assoc()) {
            // REUSE shared location function - consistent across all modules
            $loc = getCurrentTeacherLocation($conn, $r['id']);
            ?>
            <tr>
                <td><?php echo $r['id']; ?></td>
                <td><strong><?php echo htmlspecialchars($r['first_name'] . ' ' . $r['last_name']); ?></strong></td>
                <td><?php echo htmlspecialchars($r['email']); ?></td>
                <td><?php echo htmlspecialchars($r['phone'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($r['department'] ?? '-'); ?></td>
                <td><?php echo $loc !== '-' ? '<span class="badge bg-info text-dark">' . htmlspecialchars($loc) . '</span>' : '<span class="text-muted">-</span>'; ?>
                </td>
                <td><a href="teacher_profile.php?id=<?php echo $r['id']; ?>" class="btn btn-sm btn-primary">View</a></td>
            </tr>
            <?php
        }
    } else {
        echo '<tr><td colspan="7" class="text-center text-muted">No professor found</td></tr>';
    }
    exit;
}

require_once __DIR__ . '/header.php';

?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h3>Professor Directory</h3>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-6">
                    <input type="text" id="searchInput" class="form-control"
                        placeholder="Search by name, department, or subject...">
                </div>
                <div class="col-md-2">
                    <button id="searchBtn" class="btn btn-primary">Search</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Department</th>
                        <th>Location</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="resultsBody">
                    <!-- AJAX results here -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../admin/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const input = document.getElementById('searchInput');
        const btn = document.getElementById('searchBtn');
        const body = document.getElementById('resultsBody');
        let t;

        function doSearch() {
            const val = input.value.trim();
            const fd = new FormData();
            fd.append('ajax_search', '1');
            fd.append('search', val);

            fetch('teachers.php', { method: 'POST', body: fd })
                .then(r => r.text())
                .then(html => { body.innerHTML = html; })
                .catch(e => console.error(e));
        }

        input.addEventListener('input', function () { clearTimeout(t); t = setTimeout(doSearch, 250); });
        btn.addEventListener('click', doSearch);

        // initial load
        doSearch();
    });
</script>