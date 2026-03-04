<?php
$page_title = 'Professor  Assignments';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Teacher.php';
require_once __DIR__ . '/../classes/Subject.php';

$db = new Database();
$conn = $db->connect();

$teacherObj = new Teacher($conn);
$subjectObj = new Subject($conn);

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $teacher_id = $_POST['teacher_id'] ?? '';
        $subject_id = $_POST['subject_id'] ?? '';
        $academic_year = $_POST['academic_year'] ?? date('Y');
        $semester = $_POST['semester'] ?? '1';

        if (!empty($teacher_id) && !empty($subject_id)) {
            $query = "INSERT INTO teacher_subjects (teacher_id, subject_id, academic_year, semester, status) VALUES (?, ?, ?, ?, 'active')";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iisi", $teacher_id, $subject_id, $academic_year, $semester);

            if ($stmt->execute()) {
                $message = 'Assignment created successfully!';
                header('Refresh: 2; url=assignments.php');
            } else {
                $error = 'Error creating assignment.';
            }
        } else {
            $error = 'Please fill in all required fields.';
        }
    } elseif ($action === 'delete' && $id) {
        $query = "DELETE FROM teacher_subjects WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $message = 'Assignment deleted successfully!';
            header('Refresh: 2; url=assignments.php');
        } else {
            $error = 'Error deleting assignment.';
        }
    }
}
?>

<?php require_once 'header.php'; ?>

<div class="main-content" style="margin-left: 280px; margin-top: 70px; padding: 30px; min-height: calc(100vh - 70px);">
    <div class="page-header">
        <div class="flex-between">
            <div>
                <h1>Professor Assignments</h1>
                <p>Assign subjects to professor</p>
            </div>
            <a href="assignments.php?action=add" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Assignment
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($action === 'add'): ?>
        <!-- Add Form -->
        <div class="card">
            <div class="card-header">
                <h5>Create Professor Assignment</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="teacher_id" class="form-label">Professor *</label>
                                <select class="form-select" id="teacher_id" name="teacher_id" required>
                                    <option value="">Select Professor</option>
                                    <?php
                                    $teachersResult = $teacherObj->getAll('active');
                                    while ($teacher = $teachersResult->fetch_assoc()) {
                                        echo "<option value='{$teacher['id']}'>{$teacher['first_name']} {$teacher['last_name']} ({$teacher['department']})</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="subject_id" class="form-label">Subject *</label>
                                <select class="form-select" id="subject_id" name="subject_id" required>
                                    <option value="">Select Subject</option>
                                    <?php
                                    $subjectsResult = $subjectObj->getAll('active');
                                    while ($subject = $subjectsResult->fetch_assoc()) {
                                        echo "<option value='{$subject['id']}'>{$subject['subject_name']} ({$subject['subject_code']})</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="academic_year" class="form-label">Academic Year</label>
                                <input type="text" class="form-control" id="academic_year" name="academic_year"
                                    value="<?php echo date('Y'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="semester" class="form-label">Semester</label>
                                <input type="text" class="form-control" id="semester" name="semester" value="1">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Assignment
                        </button>
                        <a href="assignments.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

    <?php else: ?>
        <!-- Search and Filter Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-search"></i> Search & Filter Assignments</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="searchInput" class="form-label">Search Assignments</label>
                        <div class="position-relative">
                            <input type="text" id="searchInput" class="form-control"
                                placeholder="Type to search by professor, subject, department..." autocomplete="off">
                            <div id="searchSuggestions" class="search-suggestions"></div>
                            <i class="fas fa-search search-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="yearFilter" class="form-label">Academic Year</label>
                        <select id="yearFilter" class="form-select">
                            <option value="">All Years</option>
                            <?php
                            $yearQuery = "SELECT DISTINCT academic_year FROM teacher_subjects ORDER BY academic_year DESC";
                            $yearResult = $conn->query($yearQuery);
                            while ($year = $yearResult->fetch_assoc()) {
                                echo "<option value='{$year['academic_year']}'>{$year['academic_year']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="semesterFilter" class="form-label">Semester</label>
                        <select id="semesterFilter" class="form-select">
                            <option value="">All Semesters</option>
                            <option value="1">Semester 1</option>
                            <option value="2">Semester 2</option>
                            <option value="3">Semester 3</option>
                            <option value="Summer">Summer</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-3">
                        <label for="statusFilter" class="form-label">Status Filter</label>
                        <select id="statusFilter" class="form-select">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="departmentFilter" class="form-label">Department</label>
                        <select id="departmentFilter" class="form-select">
                            <option value="">All Departments</option>
                            <?php
                            $deptQuery = "SELECT DISTINCT t.department FROM teachers t JOIN teacher_subjects ts ON t.id = ts.teacher_id WHERE t.department IS NOT NULL ORDER BY t.department";
                            $deptResult = $conn->query($deptQuery);
                            while ($dept = $deptResult->fetch_assoc()) {
                                echo "<option value='{$dept['department']}'>{$dept['department']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="sortBy" class="form-label">Sort By</label>
                        <select id="sortBy" class="form-select">
                            <option value="teacher_asc">Teacher A-Z</option>
                            <option value="teacher_desc">Teacher Z-A</option>
                            <option value="subject_asc">Subject A-Z</option>
                            <option value="subject_desc">Subject Z-A</option>
                            <option value="year_desc">Year (Newest)</option>
                            <option value="year_asc">Year (Oldest)</option>
                            <option value="department_asc">Department A-Z</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-end h-100">
                            <button id="clearSearch" class="btn btn-outline-secondary me-3">
                                <i class="fas fa-times"></i> Clear Filters
                            </button>
                            <span id="resultCount" class="text-muted"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assignments Table -->
        <div class="card">
            <div class="card-header">
                <h5>Assignments List</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Professor</th>
                                <th>Subject</th>
                                <th>Academic Year</th>
                                <th>Semester</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="assignmentsTableBody">
                            <?php
                            $query = "SELECT ts.id, ts.status, ts.academic_year, ts.semester, 
                                         t.first_name, t.last_name, t.department, s.subject_name, s.subject_code
                                  FROM teacher_subjects ts
                                  JOIN teachers t ON ts.teacher_id = t.id
                                  JOIN subjects s ON ts.subject_id = s.id
                                  ORDER BY t.first_name, s.subject_name";
                            $result = $conn->query($query);

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $teacherName = $row['first_name'] . ' ' . $row['last_name'];
                                    ?>
                                    <tr data-teacher="<?= strtolower(htmlspecialchars($teacherName)) ?>"
                                        data-subject="<?= strtolower(htmlspecialchars($row['subject_name'])) ?>"
                                        data-subject-code="<?= strtolower(htmlspecialchars($row['subject_code'])) ?>"
                                        data-department="<?= htmlspecialchars($row['department'] ?? '') ?>"
                                        data-year="<?= htmlspecialchars($row['academic_year']) ?>"
                                        data-semester="<?= htmlspecialchars($row['semester']) ?>"
                                        data-status="<?= htmlspecialchars($row['status']) ?>">
                                        <td><strong><?php echo htmlspecialchars($teacherName); ?></strong><br><small
                                                class="text-muted"><?php echo htmlspecialchars($row['department']); ?></small></td>
                                        <td><?php echo htmlspecialchars($row['subject_name']); ?><br><small
                                                class="text-muted"><?php echo htmlspecialchars($row['subject_code']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['academic_year']); ?></td>
                                        <td><?php echo htmlspecialchars($row['semester']); ?></td>
                                        <td>
                                            <span
                                                class="badge badge-<?php echo ($row['status'] === 'active') ? 'success' : 'danger'; ?>">
                                                <?php echo htmlspecialchars($row['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="assignments.php?action=delete&id=<?php echo $row['id']; ?>"
                                                class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php }
                            } else {
                                ?>
                                <tr id="noAssignmentsRow">
                                    <td colspan="6" class="text-center text-muted">No assignments found</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php endif; ?>

</div>

<style>
    @media (max-width: 768px) {
        .main-content {
            margin-left: 0 !important;
            padding: 20px !important;
        }
    }

    /* Search Suggestions Styles */
    .position-relative {
        position: relative;
    }

    .search-suggestions {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        border-top: none;
        border-radius: 0 0 4px 4px;
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    .suggestion-item {
        padding: 12px;
        cursor: pointer;
        border-bottom: 1px solid #eee;
        transition: background-color 0.2s;
    }

    .suggestion-item:hover,
    .suggestion-item.active {
        background-color: #f8f9fa;
    }

    .suggestion-item:last-child {
        border-bottom: none;
    }

    .suggestion-teacher {
        font-weight: 600;
        color: #333;
    }

    .suggestion-subject {
        color: #666;
        margin-top: 2px;
    }

    .suggestion-details {
        font-size: 0.9em;
        color: #999;
        margin-top: 2px;
    }

    .search-icon {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
        pointer-events: none;
    }

    .highlight {
        background-color: #fff3cd;
        transition: background-color 0.3s ease;
    }

    /* Filter animations */
    .fade-out {
        opacity: 0;
        transform: scale(0.95);
        transition: all 0.2s ease-out;
    }

    .fade-in {
        opacity: 1;
        transform: scale(1);
        transition: all 0.2s ease-in;
    }
</style>

<script>
    // Assignment search and filter functionality
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchInput');
        const searchSuggestions = document.getElementById('searchSuggestions');
        const yearFilter = document.getElementById('yearFilter');
        const semesterFilter = document.getElementById('semesterFilter');
        const statusFilter = document.getElementById('statusFilter');
        const departmentFilter = document.getElementById('departmentFilter');
        const sortBy = document.getElementById('sortBy');
        const clearSearch = document.getElementById('clearSearch');
        const resultCount = document.getElementById('resultCount');
        const tbody = document.getElementById('assignmentsTableBody');
        const rows = tbody.querySelectorAll('tr:not(#noAssignmentsRow)');

        let currentSuggestionIndex = -1;

        // Get unique values for suggestions
        function getAssignmentSuggestions(query) {
            if (!query || query.length < 1) return [];

            const suggestions = [];
            const queryLower = query.toLowerCase();

            rows.forEach(row => {
                const teacher = row.getAttribute('data-teacher');
                const subject = row.getAttribute('data-subject');
                const subjectCode = row.getAttribute('data-subject-code');
                const department = row.getAttribute('data-department').toLowerCase();
                const year = row.getAttribute('data-year');
                const semester = row.getAttribute('data-semester');

                // Check if any field matches
                if (teacher.includes(queryLower) ||
                    subject.includes(queryLower) ||
                    subjectCode.includes(queryLower) ||
                    department.includes(queryLower) ||
                    year.includes(queryLower) ||
                    semester.includes(queryLower)) {

                    const teacherDisplay = teacher.split(' ').map(word =>
                        word.charAt(0).toUpperCase() + word.slice(1)
                    ).join(' ');

                    const subjectDisplay = subject.charAt(0).toUpperCase() + subject.slice(1);
                    const subjectCodeDisplay = subjectCode.toUpperCase();
                    const departmentDisplay = department.charAt(0).toUpperCase() + department.slice(1);

                    suggestions.push({
                        teacher: teacherDisplay,
                        subject: subjectDisplay,
                        subjectCode: subjectCodeDisplay,
                        department: departmentDisplay,
                        year: year,
                        semester: semester,
                        query: queryLower
                    });
                }
            });

            // Remove duplicates and limit results
            const unique = suggestions.filter((item, index, self) =>
                index === self.findIndex(t =>
                    t.teacher === item.teacher &&
                    t.subject === item.subject &&
                    t.year === item.year
                )
            );

            return unique.slice(0, 8);
        }

        // Display suggestions
        function showSuggestions(suggestions) {
            if (suggestions.length === 0) {
                searchSuggestions.style.display = 'none';
                return;
            }

            const html = suggestions.map((item, index) => `
            <div class="suggestion-item" data-index="${index}" data-teacher="${item.teacher}" data-subject="${item.subject}">
                <div class="suggestion-teacher">${item.teacher}</div>
                <div class="suggestion-subject">${item.subject} (${item.subjectCode})</div>
                <div class="suggestion-details">${item.department} • ${item.year} • Sem ${item.semester}</div>
            </div>
        `).join('');

            searchSuggestions.innerHTML = html;
            searchSuggestions.style.display = 'block';
            currentSuggestionIndex = -1;
        }

        // Filter assignments based on all criteria
        function filterAssignments() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            const yearValue = yearFilter.value;
            const semesterValue = semesterFilter.value;
            const statusValue = statusFilter.value;
            const departmentValue = departmentFilter.value;
            const sortValue = sortBy.value;

            let visibleRows = [];

            rows.forEach(row => {
                const teacher = row.getAttribute('data-teacher');
                const subject = row.getAttribute('data-subject');
                const subjectCode = row.getAttribute('data-subject-code');
                const department = row.getAttribute('data-department');
                const year = row.getAttribute('data-year');
                const semester = row.getAttribute('data-semester');
                const status = row.getAttribute('data-status');

                // Check if row matches all filters
                const matchesSearch = !searchTerm ||
                    teacher.includes(searchTerm) ||
                    subject.includes(searchTerm) ||
                    subjectCode.includes(searchTerm) ||
                    department.toLowerCase().includes(searchTerm) ||
                    year.includes(searchTerm) ||
                    semester.includes(searchTerm);

                const matchesYear = !yearValue || year === yearValue;
                const matchesSemester = !semesterValue || semester === semesterValue;
                const matchesStatus = !statusValue || status === statusValue;
                const matchesDepartment = !departmentValue || department === departmentValue;

                if (matchesSearch && matchesYear && matchesSemester && matchesStatus && matchesDepartment) {
                    row.style.display = '';
                    row.classList.remove('fade-out');
                    row.classList.add('fade-in');
                    visibleRows.push(row);

                    if (searchTerm) {
                        row.classList.add('highlight');
                        setTimeout(() => row.classList.remove('highlight'), 2000);
                    }
                } else {
                    row.classList.add('fade-out');
                    setTimeout(() => {
                        row.style.display = 'none';
                        row.classList.remove('fade-out', 'highlight');
                    }, 200);
                }
            });

            // Sort visible rows
            if (sortValue && visibleRows.length > 0) {
                sortRows(visibleRows, sortValue);
            }

            // Update result count
            updateResultCount(visibleRows.length);

            // Show/hide no results message
            const noResultsRow = document.getElementById('noAssignmentsRow');
            if (visibleRows.length === 0 && !noResultsRow) {
                const newRow = document.createElement('tr');
                newRow.id = 'noAssignmentsRow';
                newRow.innerHTML = '<td colspan="6" class="text-center text-muted">No assignments found matching your criteria</td>';
                tbody.appendChild(newRow);
            } else if (visibleRows.length > 0 && noResultsRow) {
                noResultsRow.remove();
            }
        }

        // Sort rows
        function sortRows(visibleRows, sortValue) {
            visibleRows.sort((a, b) => {
                let aVal, bVal;

                switch (sortValue) {
                    case 'teacher_asc':
                        aVal = a.getAttribute('data-teacher');
                        bVal = b.getAttribute('data-teacher');
                        return aVal.localeCompare(bVal);
                    case 'teacher_desc':
                        aVal = a.getAttribute('data-teacher');
                        bVal = b.getAttribute('data-teacher');
                        return bVal.localeCompare(aVal);
                    case 'subject_asc':
                        aVal = a.getAttribute('data-subject');
                        bVal = b.getAttribute('data-subject');
                        return aVal.localeCompare(bVal);
                    case 'subject_desc':
                        aVal = a.getAttribute('data-subject');
                        bVal = b.getAttribute('data-subject');
                        return bVal.localeCompare(aVal);
                    case 'year_asc':
                        aVal = parseInt(a.getAttribute('data-year') || 0);
                        bVal = parseInt(b.getAttribute('data-year') || 0);
                        return aVal - bVal;
                    case 'year_desc':
                        aVal = parseInt(a.getAttribute('data-year') || 0);
                        bVal = parseInt(b.getAttribute('data-year') || 0);
                        return bVal - aVal;
                    case 'department_asc':
                        aVal = a.getAttribute('data-department') || 'zzz';
                        bVal = b.getAttribute('data-department') || 'zzz';
                        return aVal.localeCompare(bVal);
                    default:
                        return 0;
                }
            });

            // Reorder in DOM
            visibleRows.forEach(row => tbody.appendChild(row));
        }

        // Update result count
        function updateResultCount(count) {
            const total = rows.length;
            resultCount.textContent = `Showing ${count} of ${total} assignments`;
            resultCount.className = count === 0 ? 'text-danger' : count < total ? 'text-warning' : 'text-success';
        }

        // Handle keyboard navigation
        function handleKeyNavigation(e) {
            const suggestions = searchSuggestions.querySelectorAll('.suggestion-item');

            if (suggestions.length === 0) return;

            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    currentSuggestionIndex = Math.min(currentSuggestionIndex + 1, suggestions.length - 1);
                    updateSuggestionHighlight(suggestions);
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    currentSuggestionIndex = Math.max(currentSuggestionIndex - 1, -1);
                    updateSuggestionHighlight(suggestions);
                    break;
                case 'Enter':
                    e.preventDefault();
                    if (currentSuggestionIndex >= 0) {
                        selectSuggestion(suggestions[currentSuggestionIndex]);
                    }
                    break;
                case 'Escape':
                    searchSuggestions.style.display = 'none';
                    currentSuggestionIndex = -1;
                    break;
            }
        }

        // Update suggestion highlight
        function updateSuggestionHighlight(suggestions) {
            suggestions.forEach((item, index) => {
                item.classList.toggle('active', index === currentSuggestionIndex);
            });
        }

        // Select suggestion
        function selectSuggestion(suggestionItem) {
            const teacher = suggestionItem.getAttribute('data-teacher');
            const subject = suggestionItem.getAttribute('data-subject');
            searchInput.value = `${teacher} - ${subject}`;
            searchSuggestions.style.display = 'none';
            filterAssignments();
        }

        // Event listeners
        searchInput.addEventListener('input', function () {
            const query = this.value.trim();
            if (query.length >= 1) {
                const suggestions = getAssignmentSuggestions(query);
                showSuggestions(suggestions);
            } else {
                searchSuggestions.style.display = 'none';
            }
            filterAssignments();
        });

        searchInput.addEventListener('keydown', handleKeyNavigation);

        searchInput.addEventListener('focus', function () {
            if (this.value.trim().length >= 1) {
                const suggestions = getAssignmentSuggestions(this.value.trim());
                showSuggestions(suggestions);
            }
        });

        // Handle suggestion clicks
        searchSuggestions.addEventListener('click', function (e) {
            const suggestionItem = e.target.closest('.suggestion-item');
            if (suggestionItem) {
                selectSuggestion(suggestionItem);
            }
        });

        // Filter change events
        yearFilter.addEventListener('change', filterAssignments);
        semesterFilter.addEventListener('change', filterAssignments);
        statusFilter.addEventListener('change', filterAssignments);
        departmentFilter.addEventListener('change', filterAssignments);
        sortBy.addEventListener('change', filterAssignments);

        // Clear filters
        clearSearch.addEventListener('click', function () {
            searchInput.value = '';
            yearFilter.value = '';
            semesterFilter.value = '';
            statusFilter.value = '';
            departmentFilter.value = '';
            sortBy.value = 'teacher_asc';
            searchSuggestions.style.display = 'none';
            filterAssignments();
            searchInput.focus();
        });

        // Hide suggestions when clicking outside
        document.addEventListener('click', function (e) {
            if (!searchInput.contains(e.target) && !searchSuggestions.contains(e.target)) {
                searchSuggestions.style.display = 'none';
            }
        });

        // Initialize result count
        updateResultCount(rows.length);
    });
</script>

<?php require_once 'footer.php'; ?>