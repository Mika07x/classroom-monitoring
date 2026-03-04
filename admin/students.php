<?php
// admin/students.php
require_once __DIR__ . '/../config/SessionManager.php';
require_once __DIR__ . '/../config/Database.php';

SessionManager::requireAdmin();

$db = new Database();
$conn = $db->connect();

$message = '';
$error = '';

// Handle Delete Student
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $student_id = (int) $_GET['id'];

    try {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
        $stmt->bind_param('i', $student_id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message = 'Student deleted successfully!';
        } else {
            $error = 'Student not found or could not be deleted.';
        }
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        $error = 'Error deleting student: ' . $e->getMessage();
    }

    // Redirect to avoid resubmission
    header('Location: students.php');
    exit;
}

// Handle Edit Student
$editStudent = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $student_id = (int) $_GET['id'];
    $stmt = $conn->prepare("SELECT id, username, email, status FROM users WHERE id = ? AND role = 'student'");
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $editStudent = $result->fetch_assoc();
    $stmt->close();
}

// Handle Update Student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_student'])) {
    $student_id = (int) $_POST['student_id'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $status = $_POST['status'] ?? 'active';
    $password = trim($_POST['password'] ?? '');

    // Check if username or email already exists for other users
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
    $checkStmt->bind_param('ssi', $username, $email, $student_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        $error = 'Username or email already exists. Please choose different credentials.';
    } else {
        try {
            if (!empty($password)) {
                // Update with password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ?, status = ? WHERE id = ? AND role = 'student'");
                $stmt->bind_param('ssssi', $username, $email, $hashedPassword, $status, $student_id);
            } else {
                // Update without password
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, status = ? WHERE id = ? AND role = 'student'");
                $stmt->bind_param('sssi', $username, $email, $status, $student_id);
            }

            if ($stmt->execute()) {
                $message = 'Student updated successfully!';
                header('Location: students.php');
                exit;
            } else {
                $error = 'Error updating student. Please try again.';
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
    $checkStmt->close();
}

// Handle Add Student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $status = $_POST['status'] ?? 'active';

    // Check if username or email already exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $checkStmt->bind_param('ss', $username, $email);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        $error = 'Username or email already exists. Please choose different credentials.';
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, 'student', ?)");
            $stmt->bind_param('ssss', $username, $email, $password, $status);

            if ($stmt->execute()) {
                $message = 'Student added successfully!';
                header('Refresh: 2; url=students.php');
            } else {
                $error = 'Error adding student. Please try again.';
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
    $checkStmt->close();
}

// Fetch Students
$students = [];
$result = $conn->query("SELECT id, username, email, status, created_at FROM users WHERE role = 'student' ORDER BY created_at DESC");
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}
$result->close();

$page_title = 'Student Management';
?>

<?php require_once 'header.php'; ?>

<div class="main-content">
    <div class="page-header">
        <div class="flex-between">
            <div>
                <h1><i class="fas fa-user-graduate"></i> Student Management</h1>
                <p>Manage student accounts and information</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                <i class="fas fa-plus"></i> Add New Student
            </button>
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

    <!-- Search and Filter Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-search"></i> Search & Filter</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="searchInput" class="form-label">Search Students</label>
                    <div class="position-relative">
                        <input type="text" id="searchInput" class="form-control"
                            placeholder="Type to search by username or email..." autocomplete="off">
                        <div id="searchSuggestions" class="search-suggestions"></div>
                        <i class="fas fa-search search-icon"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <label for="statusFilter" class="form-label">Status Filter</label>
                    <select id="statusFilter" class="form-select">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="sortBy" class="form-label">Sort By</label>
                    <select id="sortBy" class="form-select">
                        <option value="created_desc">Newest First</option>
                        <option value="created_asc">Oldest First</option>
                        <option value="username_asc">Username A-Z</option>
                        <option value="username_desc">Username Z-A</option>
                    </select>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <button id="clearSearch" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-times"></i> Clear Filters
                    </button>
                    <span id="resultCount" class="text-muted ms-3"></span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-list"></i> All Students</h5>
        </div>
        <div class="card-body">
            <table id="studentsTable" class="table table-bordered table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="studentsTableBody">
                    <?php if (count($students) > 0): ?>
                        <?php foreach ($students as $student): ?>
                            <tr data-username="<?= strtolower(htmlspecialchars($student['username'])) ?>"
                                data-email="<?= strtolower(htmlspecialchars($student['email'])) ?>"
                                data-status="<?= htmlspecialchars($student['status']) ?>"
                                data-created="<?= htmlspecialchars($student['created_at']) ?>">
                                <td><?= htmlspecialchars($student['id']) ?></td>
                                <td><strong><?= htmlspecialchars($student['username']) ?></strong></td>
                                <td><?= htmlspecialchars($student['email']) ?></td>
                                <td>
                                    <span class="badge badge-<?= ($student['status'] === 'active') ? 'success' : 'danger' ?>">
                                        <?= htmlspecialchars($student['status']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($student['created_at']) ?></td>
                                <td>
                                    <a href="students.php?action=edit&id=<?= $student['id'] ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="students.php?action=delete&id=<?= $student['id'] ?>" class="btn btn-sm btn-danger"
                                        onclick="return confirm('Are you sure you want to delete this student? This action cannot be undone.');">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr id="noStudentsRow">
                            <td colspan="6" class="text-center">No students found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addStudentModalLabel">Add Student</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_student" class="btn btn-primary">Add Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <?php if ($editStudent): ?>
        <div class="modal fade show" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel"
            aria-hidden="true" style="display: block;">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="post">
                        <input type="hidden" name="student_id" value="<?= $editStudent['id'] ?>">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editStudentModalLabel">Edit Student</h5>
                            <a href="students.php" class="btn-close" aria-label="Close"></a>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="edit_username" class="form-label">Username</label>
                                <input type="text" class="form-control" name="username"
                                    value="<?= htmlspecialchars($editStudent['username']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_email" class="form-label">Email</label>
                                <input type="email" class="form-control" name="email"
                                    value="<?= htmlspecialchars($editStudent['email']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" name="password"
                                    placeholder="Leave blank to keep current password">
                                <div class="form-text">Only fill this if you want to change the password</div>
                            </div>
                            <div class="mb-3">
                                <label for="edit_status" class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="active" <?= ($editStudent['status'] === 'active') ? 'selected' : '' ?>>
                                        Active</option>
                                    <option value="inactive" <?= ($editStudent['status'] === 'inactive') ? 'selected' : '' ?>>
                                        Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <a href="students.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" name="update_student" class="btn btn-primary">Update Student</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.body.classList.add('modal-open');
            });
        </script>
    <?php endif; ?>

    <style>
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

        .suggestion-username {
            font-weight: 600;
            color: #333;
        }

        .suggestion-email {
            font-size: 0.9em;
            color: #666;
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

        /* Mobile responsive */
        @media (max-width: 768px) {
            .search-suggestions {
                max-height: 150px;
            }

            .suggestion-item {
                padding: 10px;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('searchInput');
            const searchSuggestions = document.getElementById('searchSuggestions');
            const statusFilter = document.getElementById('statusFilter');
            const sortBy = document.getElementById('sortBy');
            const clearSearch = document.getElementById('clearSearch');
            const resultCount = document.getElementById('resultCount');
            const tableBody = document.getElementById('studentsTableBody');
            const allRows = Array.from(tableBody.querySelectorAll('tr:not(#noStudentsRow)'));

            let currentSuggestionIndex = -1;
            let filteredStudents = allRows;

            // Update result count
            function updateResultCount() {
                const visibleRows = allRows.filter(row => row.style.display !== 'none');
                const count = visibleRows.length;
                resultCount.textContent = `Showing ${count} of ${allRows.length} students`;
            }

            // Get student suggestions based on search term
            function getStudentSuggestions(searchTerm) {
                if (searchTerm.length < 2) return [];

                const suggestions = [];
                const searchTermLower = searchTerm.toLowerCase();

                allRows.forEach(row => {
                    const username = row.dataset.username;
                    const email = row.dataset.email;

                    if (username.includes(searchTermLower) || email.includes(searchTermLower)) {
                        suggestions.push({
                            username: row.children[1].textContent.trim(),
                            email: row.children[2].textContent.trim(),
                            fullMatch: username === searchTermLower || email === searchTermLower
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
            <div class="suggestion-item ${index === currentSuggestionIndex ? 'active' : ''}" data-username="${suggestion.username}" data-email="${suggestion.email}">
                <div class="suggestion-username">${suggestion.username}</div>
                <div class="suggestion-email">${suggestion.email}</div>
            </div>
        `).join('');

                searchSuggestions.innerHTML = html;
                searchSuggestions.style.display = 'block';

                // Add click handlers to suggestions
                searchSuggestions.querySelectorAll('.suggestion-item').forEach(item => {
                    item.addEventListener('click', function () {
                        searchInput.value = this.dataset.username;
                        searchSuggestions.style.display = 'none';
                        filterStudents();
                    });
                });
            }

            // Filter students based on all criteria
            function filterStudents() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                const statusValue = statusFilter.value;
                const sortValue = sortBy.value;

                // Reset all rows
                allRows.forEach(row => {
                    row.style.display = '';
                    row.classList.remove('highlight');
                });

                // Apply filters
                const visibleRows = allRows.filter(row => {
                    const username = row.dataset.username;
                    const email = row.dataset.email;
                    const status = row.dataset.status;

                    // Search filter
                    const matchesSearch = !searchTerm ||
                        username.includes(searchTerm) ||
                        email.includes(searchTerm);

                    // Status filter
                    const matchesStatus = !statusValue || status === statusValue;

                    const isVisible = matchesSearch && matchesStatus;

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
                    case 'created_asc':
                        sortedRows.sort((a, b) => new Date(a.dataset.created) - new Date(b.dataset.created));
                        break;
                    case 'created_desc':
                        sortedRows.sort((a, b) => new Date(b.dataset.created) - new Date(a.dataset.created));
                        break;
                    case 'username_asc':
                        sortedRows.sort((a, b) => a.dataset.username.localeCompare(b.dataset.username));
                        break;
                    case 'username_desc':
                        sortedRows.sort((a, b) => b.dataset.username.localeCompare(a.dataset.username));
                        break;
                }

                // Reorder table rows
                sortedRows.forEach((row, index) => {
                    tableBody.appendChild(row);
                });

                // Show no results message if needed
                const noResultsRow = document.getElementById('noStudentsRow');
                if (visibleRows.length === 0 && allRows.length > 0) {
                    if (!noResultsRow) {
                        const newNoResultsRow = document.createElement('tr');
                        newNoResultsRow.id = 'noStudentsRow';
                        newNoResultsRow.innerHTML = '<td colspan="6" class="text-center">No students match your search criteria.</td>';
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
            searchInput.addEventListener('input', function () {
                const searchTerm = this.value.trim();

                if (searchTerm.length >= 2) {
                    const suggestions = getStudentSuggestions(searchTerm);
                    showSuggestions(suggestions);
                } else {
                    searchSuggestions.style.display = 'none';
                }

                filterStudents();
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

            function updateSuggestionSelection() {
                const suggestions = searchSuggestions.querySelectorAll('.suggestion-item');
                suggestions.forEach((item, index) => {
                    item.classList.toggle('active', index === currentSuggestionIndex);
                });
            }

            // Hide suggestions when clicking outside
            document.addEventListener('click', function (e) {
                if (!searchInput.contains(e.target) && !searchSuggestions.contains(e.target)) {
                    searchSuggestions.style.display = 'none';
                    currentSuggestionIndex = -1;
                }
            });

            // Filter change handlers
            statusFilter.addEventListener('change', filterStudents);
            sortBy.addEventListener('change', filterStudents);

            // Clear filters
            clearSearch.addEventListener('click', function () {
                searchInput.value = '';
                statusFilter.value = '';
                sortBy.value = 'created_desc';
                searchSuggestions.style.display = 'none';
                currentSuggestionIndex = -1;
                filterStudents();
                searchInput.focus();
            });

            // Initialize
            updateResultCount();
        });
    </script>

    <?php require_once 'footer.php'; ?>