<?php
$page_title = 'Subjects Management';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Subject.php';

$db = new Database();
$conn = $db->connect();

$subjectObj = new Subject($conn);

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;
$message = '';
$error = '';

// Check if subject was deleted successfully
if (isset($_GET['deleted']) && $_GET['deleted'] == '1') {
    $message = 'Subject deleted successfully!';
}

// Handle delete action (GET request)
if ($action === 'delete' && $id) {
    // Check if subject is being used in any schedules, teacher assignments, or class assignments
    $dependencies = [];

    // Check schedules
    $checkQuery = "SELECT COUNT(*) as count FROM schedules WHERE subject_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param('i', $id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $scheduleCount = $result->fetch_assoc()['count'];
    if ($scheduleCount > 0) {
        $dependencies[] = "$scheduleCount schedule(s)";
    }

    // Check teacher assignments
    $checkQuery = "SELECT COUNT(*) as count FROM teacher_subjects WHERE subject_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param('i', $id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $teacherCount = $result->fetch_assoc()['count'];
    if ($teacherCount > 0) {
        $dependencies[] = "$teacherCount teacher assignment(s)";
    }

    // Check class assignments
    $checkQuery = "SELECT COUNT(*) as count FROM class_assignments WHERE subject_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param('i', $id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $classCount = $result->fetch_assoc()['count'];
    if ($classCount > 0) {
        $dependencies[] = "$classCount class assignment(s)";
    }

    if (!empty($dependencies)) {
        $error = 'Cannot delete subject. It is currently being used in ' . implode(', ', $dependencies) . '. Please remove all references first.';
    } else {
        if ($subjectObj->delete($id)) {
            header('Location: subjects.php?deleted=1');
            exit;
        } else {
            $error = 'Error deleting subject. Please try again.';
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $subject_code = $_POST['subject_code'] ?? '';
        $subject_name = $_POST['subject_name'] ?? '';
        $department = $_POST['department'] ?? '';
        $description = $_POST['description'] ?? '';
        $credits = $_POST['credits'] ?? 3;

        if (!empty($subject_code) && !empty($subject_name)) {
            $result = $subjectObj->add($subject_code, $subject_name, $department, $description, $credits);
            if ($result['status']) {
                $message = $result['message'];
                header('Refresh: 2; url=subjects.php');
            } else {
                $error = $result['message'];
            }
        } else {
            $error = 'Please fill in required fields.';
        }
    } elseif ($action === 'edit' && $id) {
        $subject_code = $_POST['subject_code'] ?? '';
        $subject_name = $_POST['subject_name'] ?? '';
        $department = $_POST['department'] ?? '';
        $description = $_POST['description'] ?? '';
        $credits = $_POST['credits'] ?? 3;
        $status = $_POST['status'] ?? 'active';

        if (!empty($subject_code) && !empty($subject_name)) {
            if ($subjectObj->update($id, $subject_code, $subject_name, $department, $description, $credits, $status)) {
                $message = 'Subject updated successfully!';
                header('Refresh: 2; url=subjects.php');
            } else {
                $error = 'Error updating subject.';
            }
        } else {
            $error = 'Please fill in required fields.';
        }
    }
}

// Get subject data for edit form
$editSubject = null;
if ($action === 'edit' && $id) {
    $editSubject = $subjectObj->getById($id);
}
?>

<?php require_once 'header.php'; ?>

<div class="main-content" style="margin-left: 280px; margin-top: 70px; padding: 30px; min-height: calc(100vh - 70px);">
    <div class="page-header">
        <div class="flex-between">
            <div>
                <h1>Subjects Management</h1>
                <p>Manage courses and subjects</p>
            </div>
            <a href="subjects.php?action=add" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Subject
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

    <?php if ($action === 'add' || $action === 'edit'): ?>
        <!-- Add/Edit Form -->
        <div class="card">
            <div class="card-header">
                <h5><?php echo ($action === 'add') ? 'Add New Subject' : 'Edit Subject'; ?></h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="subject_code" class="form-label">Subject Code *</label>
                                <input type="text" class="form-control" id="subject_code" name="subject_code"
                                    value="<?php echo $editSubject ? htmlspecialchars($editSubject['subject_code']) : ''; ?>"
                                    required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="subject_name" class="form-label">Subject Name *</label>
                                <input type="text" class="form-control" id="subject_name" name="subject_name"
                                    value="<?php echo $editSubject ? htmlspecialchars($editSubject['subject_name']) : ''; ?>"
                                    required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="department" class="form-label">Department</label>
                                <input type="text" class="form-control" id="department" name="department"
                                    value="<?php echo $editSubject ? htmlspecialchars($editSubject['department'] ?? '') : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="credits" class="form-label">Credits</label>
                                <input type="number" class="form-control" id="credits" name="credits" min="1" max="10"
                                    value="<?php echo $editSubject ? htmlspecialchars($editSubject['credits'] ?? 3) : 3; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description"
                            rows="4"><?php echo $editSubject ? htmlspecialchars($editSubject['description'] ?? '') : ''; ?></textarea>
                    </div>

                    <?php if ($action === 'edit'): ?>
                        <div class="form-group mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" <?php echo ($editSubject['status'] === 'active') ? 'selected' : ''; ?>>
                                    Active</option>
                                <option value="inactive" <?php echo ($editSubject['status'] === 'inactive') ? 'selected' : ''; ?>>
                                    Inactive</option>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            <?php echo ($action === 'add') ? 'Add Subject' : 'Update Subject'; ?>
                        </button>
                        <a href="subjects.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

    <?php else: ?>
        <!-- Search and Filter Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-search"></i> Search & Filter Subjects</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="searchInput" class="form-label">Search Subjects</label>
                        <div class="position-relative">
                            <input type="text" id="searchInput" class="form-control"
                                placeholder="Type to search by code, name, or department..." autocomplete="off">
                            <div id="searchSuggestions" class="search-suggestions"></div>
                            <i class="fas fa-search search-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="departmentFilter" class="form-label">Department Filter</label>
                        <select id="departmentFilter" class="form-select">
                            <option value="">All Departments</option>
                            <option value="Computer Science">Computer Science</option>
                            <option value="Mathematics">Mathematics</option>
                            <option value="English">English</option>
                            <option value="Physics">Physics</option>
                            <option value="Chemistry">Chemistry</option>
                            <option value="Biology">Biology</option>
                            <option value="History">History</option>
                            <option value="Business">Business</option>
                            <option value="Engineering">Engineering</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="statusFilter" class="form-label">Status Filter</label>
                        <select id="statusFilter" class="form-select">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label for="sortBy" class="form-label">Sort By</label>
                        <select id="sortBy" class="form-select">
                            <option value="code_asc">Subject Code A-Z</option>
                            <option value="code_desc">Subject Code Z-A</option>
                            <option value="name_asc">Subject Name A-Z</option>
                            <option value="name_desc">Subject Name Z-A</option>
                            <option value="department_asc">Department A-Z</option>
                            <option value="credits_asc">Credits Low-High</option>
                            <option value="credits_desc">Credits High-Low</option>
                        </select>
                    </div>
                    <div class="col-md-6">
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

        <!-- Subjects Table -->
        <div class="card">
            <div class="card-header">
                <h5>Subjects List</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Credits</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="subjectsTableBody">
                            <?php
                            $result = $subjectObj->getAll();

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    ?>
                                    <tr data-code="<?= strtolower(htmlspecialchars($row['subject_code'])) ?>"
                                        data-name="<?= strtolower(htmlspecialchars($row['subject_name'])) ?>"
                                        data-department="<?= strtolower(htmlspecialchars($row['department'] ?? '')) ?>"
                                        data-status="<?= htmlspecialchars($row['status']) ?>"
                                        data-credits="<?= htmlspecialchars($row['credits']) ?>">
                                        <td><strong><?php echo htmlspecialchars($row['subject_code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['department'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($row['credits']); ?></td>
                                        <td>
                                            <span
                                                class="badge badge-<?php echo ($row['status'] === 'active') ? 'success' : 'danger'; ?>">
                                                <?php echo htmlspecialchars($row['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="subjects.php?action=edit&id=<?php echo $row['id']; ?>"
                                                class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="subjects.php?action=delete&id=<?php echo $row['id']; ?>"
                                                class="btn btn-sm btn-danger"
                                                onclick="return confirm('Are you sure you want to delete this subject? This action cannot be undone.');">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php }
                            } else {
                                ?>
                                <tr id="noSubjectsRow">
                                    <td colspan="6" class="text-center text-muted">No subjects found</td>
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

    .suggestion-code {
        font-weight: 600;
        color: #333;
    }

    .suggestion-name {
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
    document.addEventListener('DOMContentLoaded', function () {
        // Only initialize search functionality when NOT adding/editing
        <?php if ($action !== 'add' && $action !== 'edit'): ?>

            const searchInput = document.getElementById('searchInput');
            const searchSuggestions = document.getElementById('searchSuggestions');
            const departmentFilter = document.getElementById('departmentFilter');
            const statusFilter = document.getElementById('statusFilter');
            const sortBy = document.getElementById('sortBy');
            const clearSearch = document.getElementById('clearSearch');
            const resultCount = document.getElementById('resultCount');
            const tableBody = document.getElementById('subjectsTableBody');
            const allRows = Array.from(tableBody.querySelectorAll('tr:not(#noSubjectsRow)'));

            let currentSuggestionIndex = -1;
            let filteredSubjects = allRows;

            // Update result count
            function updateResultCount() {
                const visibleRows = allRows.filter(row => row.style.display !== 'none');
                const count = visibleRows.length;
                resultCount.textContent = `Showing ${count} of ${allRows.length} subjects`;
            }

            // Get subject suggestions based on search term
            function getSubjectSuggestions(searchTerm) {
                if (searchTerm.length < 2) return [];

                const suggestions = [];
                const searchTermLower = searchTerm.toLowerCase();

                allRows.forEach(row => {
                    const code = row.dataset.code;
                    const name = row.dataset.name;
                    const department = row.dataset.department;

                    if (code.includes(searchTermLower) || name.includes(searchTermLower) || department.includes(searchTermLower)) {
                        const codeDisplay = row.children[0].textContent.trim();
                        const nameDisplay = row.children[1].textContent.trim();
                        const departmentDisplay = row.children[2].textContent.trim();
                        const creditsDisplay = row.children[3].textContent.trim();

                        suggestions.push({
                            code: codeDisplay,
                            name: nameDisplay,
                            department: departmentDisplay,
                            credits: creditsDisplay,
                            fullMatch: code === searchTermLower || name === searchTermLower
                        });
                    }
                });

                // Sort by relevance (exact matches first, then partial matches)
                suggestions.sort((a, b) => {
                    if (a.fullMatch && !b.fullMatch) return -1;
                    if (!a.fullMatch && b.fullMatch) return 1;
                    return 0;
                });

                return suggestions.slice(0, 5); // Limit to 5 suggestions
            }

            // Display suggestions
            function showSuggestions(suggestions) {
                if (suggestions.length === 0) {
                    searchSuggestions.style.display = 'none';
                    return;
                }

                const html = suggestions.map((suggestion, index) => `
            <div class="suggestion-item ${index === currentSuggestionIndex ? 'active' : ''}" data-code="${suggestion.code}">
                <div class="suggestion-code">${suggestion.code}</div>
                <div class="suggestion-name">${suggestion.name}</div>
                <div class="suggestion-details">${suggestion.department} • ${suggestion.credits} Credit(s)</div>
            </div>
        `).join('');

                searchSuggestions.innerHTML = html;
                searchSuggestions.style.display = 'block';

                // Add click handlers to suggestions
                searchSuggestions.querySelectorAll('.suggestion-item').forEach(item => {
                    item.addEventListener('click', function () {
                        searchInput.value = this.dataset.code;
                        searchSuggestions.style.display = 'none';
                        filterSubjects();
                    });
                });
            }

            // Filter subjects based on all criteria
            function filterSubjects() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                const departmentValue = departmentFilter.value.toLowerCase();
                const statusValue = statusFilter.value;
                const sortValue = sortBy.value;

                // Reset all rows
                allRows.forEach(row => {
                    row.style.display = '';
                    row.classList.remove('highlight');
                });

                // Apply filters
                const visibleRows = allRows.filter(row => {
                    const code = row.dataset.code;
                    const name = row.dataset.name;
                    const department = row.dataset.department;
                    const status = row.dataset.status;

                    // Search filter
                    const matchesSearch = !searchTerm ||
                        code.includes(searchTerm) ||
                        name.includes(searchTerm) ||
                        department.includes(searchTerm);

                    // Department filter
                    const matchesDepartment = !departmentValue || department.includes(departmentValue);

                    // Status filter
                    const matchesStatus = !statusValue || status === statusValue;

                    const isVisible = matchesSearch && matchesDepartment && matchesStatus;

                    if (!isVisible) {
                        row.style.display = 'none';
                    } else if (searchTerm) {
                        // Highlight matching row
                        row.classList.add('highlight');
                        setTimeout(() => row.classList.remove('highlight'), 2000);
                    }

                    return isVisible;
                });

                // Sort visible rows
                const sortedRows = [...visibleRows];
                switch (sortValue) {
                    case 'code_asc':
                        sortedRows.sort((a, b) => a.dataset.code.localeCompare(b.dataset.code));
                        break;
                    case 'code_desc':
                        sortedRows.sort((a, b) => b.dataset.code.localeCompare(a.dataset.code));
                        break;
                    case 'name_asc':
                        sortedRows.sort((a, b) => a.dataset.name.localeCompare(b.dataset.name));
                        break;
                    case 'name_desc':
                        sortedRows.sort((a, b) => b.dataset.name.localeCompare(a.dataset.name));
                        break;
                    case 'department_asc':
                        sortedRows.sort((a, b) => a.dataset.department.localeCompare(b.dataset.department));
                        break;
                    case 'credits_asc':
                        sortedRows.sort((a, b) => parseInt(a.dataset.credits) - parseInt(b.dataset.credits));
                        break;
                    case 'credits_desc':
                        sortedRows.sort((a, b) => parseInt(b.dataset.credits) - parseInt(a.dataset.credits));
                        break;
                }

                // Reorder table rows
                sortedRows.forEach((row, index) => {
                    tableBody.appendChild(row);
                });

                // Show no results message if needed
                const noResultsRow = document.getElementById('noSubjectsRow');
                if (visibleRows.length === 0 && allRows.length > 0) {
                    if (!noResultsRow) {
                        const newNoResultsRow = document.createElement('tr');
                        newNoResultsRow.id = 'noSubjectsRow';
                        newNoResultsRow.innerHTML = '<td colspan="6" class="text-center">No subjects match your search criteria.</td>';
                        tableBody.appendChild(newNoResultsRow);
                    } else {
                        noResultsRow.style.display = '';
                    }
                } else if (noResultsRow) {
                    noResultsRow.style.display = 'none';
                }

                updateResultCount();
            }

            // Search input event handlers
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    const searchTerm = this.value.trim();

                    if (searchTerm.length >= 2) {
                        const suggestions = getSubjectSuggestions(searchTerm);
                        showSuggestions(suggestions);
                    } else {
                        searchSuggestions.style.display = 'none';
                    }

                    filterSubjects();
                });

                // Keyboard navigation for suggestions
                searchInput.addEventListener('keydown', function (e) {
                    const suggestions = searchSuggestions.querySelectorAll('.suggestion-item');

                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        currentSuggestionIndex = Math.min(currentSuggestionIndex + 1, suggestions.length - 1);
                        updateSuggestionSelection();
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        currentSuggestionIndex = Math.max(currentSuggestionIndex - 1, -1);
                        updateSuggestionSelection();
                    } else if (e.key === 'Enter' && currentSuggestionIndex >= 0) {
                        e.preventDefault();
                        suggestions[currentSuggestionIndex].click();
                    } else if (e.key === 'Escape') {
                        searchSuggestions.style.display = 'none';
                        currentSuggestionIndex = -1;
                    }
                });
            }

            function updateSuggestionSelection() {
                const suggestions = searchSuggestions.querySelectorAll('.suggestion-item');
                suggestions.forEach((item, index) => {
                    item.classList.toggle('active', index === currentSuggestionIndex);
                });
            }

            // Hide suggestions when clicking outside
            document.addEventListener('click', function (e) {
                if (searchInput && !searchInput.contains(e.target) && !searchSuggestions.contains(e.target)) {
                    searchSuggestions.style.display = 'none';
                    currentSuggestionIndex = -1;
                }
            });

            // Filter change handlers
            if (departmentFilter) departmentFilter.addEventListener('change', filterSubjects);
            if (statusFilter) statusFilter.addEventListener('change', filterSubjects);
            if (sortBy) sortBy.addEventListener('change', filterSubjects);

            // Clear filters
            if (clearSearch) {
                clearSearch.addEventListener('click', function () {
                    searchInput.value = '';
                    departmentFilter.value = '';
                    statusFilter.value = '';
                    sortBy.value = 'code_asc';
                    searchSuggestions.style.display = 'none';
                    currentSuggestionIndex = -1;
                    filterSubjects();
                    searchInput.focus();
                });
            }

            // Initialize
            updateResultCount();

        <?php endif; ?>
    });
</script>

<?php require_once 'footer.php'; ?>