<?php
$page_title = 'Classrooms Management';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Classroom.php';
$db = new Database();
$conn = $db->connect();

$classroomObj = new Classroom($conn);

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;
$message = '';
$error = '';

// Check if classroom was deleted successfully
if (isset($_GET['deleted']) && $_GET['deleted'] == '1') {
    $message = 'Classroom deleted successfully!';
}

// Handle delete action (GET request)
if ($action === 'delete' && $id) {
    // Check if classroom is being used in any schedules (active or inactive)
    $checkQuery = "SELECT COUNT(*) as count FROM schedules WHERE classroom_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param('i', $id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $count = $result->fetch_assoc()['count'];

    if ($count > 0) {
        $error = 'Cannot delete classroom. It has been used in ' . $count . ' schedule(s). Please remove all schedule references first.';
    } else {
        if ($classroomObj->delete($id)) {
            header('Location: classrooms.php?deleted=1');
            exit;
        } else {
            $error = 'Error deleting classroom. Please try again.';
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $room_number = $_POST['room_number'] ?? '';
        $room_name = $_POST['room_name'] ?? '';
        $building = $_POST['building'] ?? '';
        $capacity = $_POST['capacity'] ?? '';
        $room_type = $_POST['room_type'] ?? 'classroom';
        $equipment = $_POST['equipment'] ?? '';
        $floor = $_POST['floor'] ?? '';

        if (!empty($room_number) && !empty($room_name) && !empty($capacity)) {
            // Check for duplicate classroom (same room number in same building)
            $duplicateQuery = "SELECT id FROM classrooms WHERE room_number = ? AND building = ?";
            $duplicateStmt = $conn->prepare($duplicateQuery);
            $duplicateStmt->bind_param('ss', $room_number, $building);
            $duplicateStmt->execute();
            $duplicateResult = $duplicateStmt->get_result();

            if ($duplicateResult->num_rows > 0) {
                $buildingText = !empty($building) ? "in building '{$building}'" : "with no building specified";
                $error = "A classroom with room number '{$room_number}' already exists {$buildingText}. Please use a different room number or specify a different building.";
                $duplicateStmt->close();
            } else {
                $duplicateStmt->close();
                $result = $classroomObj->add($room_number, $room_name, $building, $capacity, $room_type, $equipment, $floor);
                if ($result['status']) {
                    $message = $result['message'];
                    header('Refresh: 2; url=classrooms.php');
                } else {
                    $error = $result['message'];
                }
            }
        } else {
            $error = 'Please fill in required fields.';
        }
    } elseif ($action === 'edit' && $id) {
        $room_number = $_POST['room_number'] ?? '';
        $room_name = $_POST['room_name'] ?? '';
        $building = $_POST['building'] ?? '';
        $capacity = $_POST['capacity'] ?? '';
        $room_type = $_POST['room_type'] ?? 'classroom';
        $equipment = $_POST['equipment'] ?? '';
        $floor = $_POST['floor'] ?? '';
        $status = $_POST['status'] ?? 'active';

        if (!empty($room_number) && !empty($room_name) && !empty($capacity)) {
            // Check for duplicate classroom (same room number in same building, excluding current record)
            $duplicateQuery = "SELECT id FROM classrooms WHERE room_number = ? AND building = ? AND id != ?";
            $duplicateStmt = $conn->prepare($duplicateQuery);
            $duplicateStmt->bind_param('ssi', $room_number, $building, $id);
            $duplicateStmt->execute();
            $duplicateResult = $duplicateStmt->get_result();

            if ($duplicateResult->num_rows > 0) {
                $buildingText = !empty($building) ? "in building '{$building}'" : "with no building specified";
                $error = "A classroom with room number '{$room_number}' already exists {$buildingText}. Please use a different room number or specify a different building.";
                $duplicateStmt->close();
            } else {
                $duplicateStmt->close();
                if ($classroomObj->update($id, $room_number, $room_name, $building, $capacity, $room_type, $equipment, $floor, $status)) {
                    $message = 'Classroom updated successfully!';
                    header('Refresh: 2; url=classrooms.php');
                } else {
                    $error = 'Error updating classroom.';
                }
            }
        } else {
            $error = 'Please fill in required fields.';
        }
    }
}

// Get classroom data for edit form
$editClassroom = null;
if ($action === 'edit' && $id) {
    $editClassroom = $classroomObj->getById($id);
}
?>

<?php require_once 'header.php'; ?>

<div class="main-content" style="margin-left: 280px; margin-top: 70px; padding: 30px; min-height: calc(100vh - 70px);">
    <div class="page-header">
        <div class="flex-between">
            <div>
                <h1>Classrooms Management</h1>
                <p>Manage classroom and room assignments</p>
            </div>
            <a href="classrooms.php?action=add" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Classroom
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
                <h5><?php echo ($action === 'add') ? 'Add New Classroom' : 'Edit Classroom'; ?></h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="room_number" class="form-label">Room Number *</label>
                                <input type="text" class="form-control" id="room_number" name="room_number"
                                    value="<?php echo $editClassroom ? htmlspecialchars($editClassroom['room_number']) : ''; ?>"
                                    required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="room_name" class="form-label">Room Name *</label>
                                <input type="text" class="form-control" id="room_name" name="room_name"
                                    value="<?php echo $editClassroom ? htmlspecialchars($editClassroom['room_name']) : ''; ?>"
                                    required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="building" class="form-label">Building</label>
                                <select class="form-select" id="building" name="building">
                                    <option value="">Select Building</option>
                                    <option value="Main Building" <?php echo ($editClassroom && $editClassroom['building'] === 'Main Building') ? 'selected' : ''; ?>>Main Building
                                    </option>
                                    <option value="Building A" <?php echo ($editClassroom && $editClassroom['building'] === 'Building A') ? 'selected' : ''; ?>>Orange Building
                                    </option>
                                    <option value="Building B" <?php echo ($editClassroom && $editClassroom['building'] === 'Building B') ? 'selected' : ''; ?>>Admin Building
                                    </option>
                                    <option value="Building C" <?php echo ($editClassroom && $editClassroom['building'] === 'Building C') ? 'selected' : ''; ?>>Registrar Building
                                    </option>
                                    <!-- <option value="Building D" <?php echo ($editClassroom && $editClassroom['building'] === 'Building D') ? 'selected' : ''; ?>>Building D</option>
                                <option value="Science Block" <?php echo ($editClassroom && $editClassroom['building'] === 'Science Block') ? 'selected' : ''; ?>>Science Block</option>
                                <option value="Engineering Block" <?php echo ($editClassroom && $editClassroom['building'] === 'Engineering Block') ? 'selected' : ''; ?>>Engineering Block</option>
                                <option value="Library Building" <?php echo ($editClassroom && $editClassroom['building'] === 'Library Building') ? 'selected' : ''; ?>>Library Building</option>
                                <option value="Admin Building" <?php echo ($editClassroom && $editClassroom['building'] === 'Admin Building') ? 'selected' : ''; ?>>Admin Building</option> -->
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="floor" class="form-label">Floor</label>
                                <select class="form-select" id="floor" name="floor">
                                    <option value="">Select Floor</option>
                                    <!-- <option value="Ground Floor" <?php echo ($editClassroom && $editClassroom['floor'] === 'Ground Floor') ? 'selected' : ''; ?>>Ground Floor</option> -->
                                    <option value="1st Floor" <?php echo ($editClassroom && $editClassroom['floor'] === '1st Floor') ? 'selected' : ''; ?>>1st Floor</option>
                                    <option value="2nd Floor" <?php echo ($editClassroom && $editClassroom['floor'] === '2nd Floor') ? 'selected' : ''; ?>>2nd Floor</option>
                                    <option value="3rd Floor" <?php echo ($editClassroom && $editClassroom['floor'] === '3rd Floor') ? 'selected' : ''; ?>>3rd Floor</option>
                                    <option value="4th Floor" <?php echo ($editClassroom && $editClassroom['floor'] === '4th Floor') ? 'selected' : ''; ?>>4th Floor</option>
                                    <option value="5th Floor" <?php echo ($editClassroom && $editClassroom['floor'] === '5th Floor') ? 'selected' : ''; ?>>5th Floor</option>
                                    <option value="Court" <?php echo ($editClassroom && $editClassroom['floor'] === 'Court') ? 'selected' : ''; ?>>Court</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="capacity" class="form-label">Capacity *</label>
                                <input type="number" class="form-control" id="capacity" name="capacity" min="1"
                                    value="<?php echo $editClassroom ? htmlspecialchars($editClassroom['capacity']) : ''; ?>"
                                    required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="room_type" class="form-label">Room Type</label>
                                <select class="form-select" id="room_type" name="room_type">
                                    <option value="classroom" <?php echo ($editClassroom && $editClassroom['room_type'] === 'classroom') ? 'selected' : ''; ?>>Classroom</option>
                                    <option value="lab" <?php echo ($editClassroom && $editClassroom['room_type'] === 'laboratory') ? 'selected' : ''; ?>>Laboratory
                                    </option>
                                    <option value="seminar" <?php echo ($editClassroom && $editClassroom['room_type'] === 'lecture room') ? 'selected' : ''; ?>>Lecture Room
                                    </option>
                                    <option value="auditorium" <?php echo ($editClassroom && $editClassroom['room_type'] === 'social hall') ? 'selected' : ''; ?>>Social Hall
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- <div class="form-group mb-3">
                    <label for="equipment" class="form-label">Equipment</label>
                    <input type="text" class="form-control" id="equipment" name="equipment" placeholder="e.g., Projector, Whiteboard, AC"
                           value="<?php echo $editClassroom ? htmlspecialchars($editClassroom['equipment'] ?? '') : ''; ?>">
                </div> -->

                    <?php if ($action === 'edit'): ?>
                        <div class="form-group mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" <?php echo ($editClassroom['status'] === 'active') ? 'selected' : ''; ?>>
                                    Active</option>
                                <option value="maintenance" <?php echo ($editClassroom['status'] === 'maintenance') ? 'selected' : ''; ?>>Under Maintenance</option>
                                <option value="inactive" <?php echo ($editClassroom['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            <?php echo ($action === 'add') ? 'Add Classroom' : 'Update Classroom'; ?>
                        </button>
                        <a href="classrooms.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

    <?php else: ?>
        <!-- Search and Filter Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-search"></i> Search & Filter Classrooms</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="searchInput" class="form-label">Search Classrooms</label>
                        <div class="position-relative">
                            <input type="text" id="searchInput" class="form-control"
                                placeholder="Type to search by room number, name, building..." autocomplete="off">
                            <div id="searchSuggestions" class="search-suggestions"></div>
                            <i class="fas fa-search search-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="buildingFilter" class="form-label">Building Filter</label>
                        <select id="buildingFilter" class="form-select">
                            <option value="">All Buildings</option>
                            <option value="Main Building">Main Building</option>
                            <option value="Building A">Orange Building</option>
                            <option value="Building B">Admin Building</option>
                            <option value="Building C">Registrar Building</option>
                            <!-- <option value="Building D">Building D</option>
                        <option value="Science Block">Science Block</option>
                        <option value="Engineering Block">Engineering Block</option> -->
                            <option value="Library Building">Library Building</option>
                            <!-- <option value="Admin Building">Admin Building</option> -->
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="typeFilter" class="form-label">Type Filter</label>
                        <select id="typeFilter" class="form-select">
                            <option value="">All Types</option>
                            <option value="classroom">Classroom</option>
                            <option value="lab">Laboratory</option>
                            <option value="seminar">Lecture Room</option>
                            <option value="auditorium">Social Hall</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-3">
                        <label for="statusFilter" class="form-label">Status Filter</label>
                        <select id="statusFilter" class="form-select">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="maintenance">Under Maintenance</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="sortBy" class="form-label">Sort By</label>
                        <select id="sortBy" class="form-select">
                            <option value="room_asc">Room Number A-Z</option>
                            <option value="room_desc">Room Number Z-A</option>
                            <option value="name_asc">Room Name A-Z</option>
                            <option value="name_desc">Room Name Z-A</option>
                            <option value="building_asc">Building A-Z</option>
                            <option value="capacity_asc">Capacity Low-High</option>
                            <option value="capacity_desc">Capacity High-Low</option>
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

        <!-- Classrooms Table -->
        <div class="card">
            <div class="card-header">
                <h5>Classrooms List</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Room Number</th>
                                <th>Room Name</th>
                                <th>Building</th>
                                <th>Schedule</th> <!-- NEW COLUMN -->
                                <th>Type</th>
                                <th>Capacity</th>
                                <!-- <th>Equipment</th> -->
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="classroomsTableBody">
                            <?php
                            $result = $classroomObj->getAll();

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    // Assuming you have a method to get schedule per classroom
                                    $schedule = $classroomObj->getSchedule($row['id']);
                                    ?>
                                    <tr data-room="<?= strtolower(htmlspecialchars($row['room_number'])) ?>"
                                        data-name="<?= strtolower(htmlspecialchars($row['room_name'])) ?>"
                                        data-building="<?= strtolower(htmlspecialchars($row['building'] ?? '')) ?>"
                                        data-type="<?= htmlspecialchars($row['room_type']) ?>"
                                        data-status="<?= htmlspecialchars($row['status']) ?>"
                                        data-capacity="<?= htmlspecialchars($row['capacity']) ?>"
                                        data-equipment="<?= strtolower(htmlspecialchars($row['equipment'] ?? '')) ?>">
                                        <td><strong><?php echo htmlspecialchars($row['room_number']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['room_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['building'] ?? '-'); ?></td>

                                        <!-- NEW Schedule Column -->
                                        <td>
                                            <?php
                                            // Display schedule info (example: "Mon 9-11, Wed 14-16")
                                            $scheduleText = '';

                                            if ($schedule && $schedule->num_rows > 0) {
                                                while ($sched = $schedule->fetch_assoc()) {
                                                    $scheduleText .= $sched['day_of_week'] . ' ' . $sched['start_time'] . '-' . $sched['end_time'] . ', ';
                                                }
                                            }

                                            echo !empty($scheduleText) ? htmlspecialchars(rtrim($scheduleText, ', ')) : '-';
                                            ?>
                                        </td>

                                        <td><span
                                                class="badge badge-info text-capitalize"><?php echo htmlspecialchars($row['room_type']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['capacity']); ?></td>
                                        <td><span
                                                class="badge badge-success text-capitalize"><?php echo htmlspecialchars($row['status'] ?? '-'); ?></span>
                                        </td>

                                        <td class="text-center align-middle">
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="classrooms.php?action=edit&id=<?php echo $row['id']; ?>"
                                                    class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <a href="classrooms.php?action=delete&id=<?php echo $row['id']; ?>"
                                                    class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Are you sure you want to delete this classroom? This action cannot be undone.');">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            </div>
                                        </td>
                                    <?php }
                            } else { ?>
                                <tr id="noClassroomsRow">
                                    <td colspan="9" class="text-center text-muted">No classrooms found</td>
                                    <!-- updated colspan -->
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

    .suggestion-room {
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
    // Classroom search and filter functionality
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('searchInput');
        const searchSuggestions = document.getElementById('searchSuggestions');
        const buildingFilter = document.getElementById('buildingFilter');
        const typeFilter = document.getElementById('typeFilter');
        const statusFilter = document.getElementById('statusFilter');
        const sortBy = document.getElementById('sortBy');
        const clearSearch = document.getElementById('clearSearch');
        const resultCount = document.getElementById('resultCount');
        const tbody = document.getElementById('classroomsTableBody');
        const rows = tbody.querySelectorAll('tr:not(#noClassroomsRow)');

        let currentSuggestionIndex = -1;

        // Get unique values for suggestions
        function getClassroomSuggestions(query) {
            if (!query || query.length < 1) return [];

            const suggestions = [];
            const queryLower = query.toLowerCase();

            rows.forEach(row => {
                const roomNumber = row.getAttribute('data-room');
                const roomName = row.getAttribute('data-name');
                const building = row.getAttribute('data-building');
                const equipment = row.getAttribute('data-equipment');
                const roomType = row.getAttribute('data-type');
                const capacity = row.getAttribute('data-capacity');

                // Check if any field matches
                if (roomNumber.includes(queryLower) ||
                    roomName.includes(queryLower) ||
                    building.includes(queryLower) ||
                    equipment.includes(queryLower) ||
                    roomType.includes(queryLower)) {

                    const roomNumDisplay = roomNumber.charAt(0).toUpperCase() + roomNumber.slice(1);
                    const roomNameDisplay = roomName.charAt(0).toUpperCase() + roomName.slice(1);
                    const buildingDisplay = building ? building.charAt(0).toUpperCase() + building.slice(1) : '';

                    suggestions.push({
                        room: roomNumDisplay,
                        name: roomNameDisplay,
                        building: buildingDisplay,
                        type: roomType,
                        capacity: capacity,
                        query: queryLower
                    });
                }
            });

            // Remove duplicates and limit results
            const unique = suggestions.filter((item, index, self) =>
                index === self.findIndex(t => t.room === item.room && t.name === item.name)
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
            <div class="suggestion-item" data-index="${index}" data-room="${item.room}" data-name="${item.name}">
                <div class="suggestion-room">${item.room}</div>
                <div class="suggestion-name">${item.name}</div>
                <div class="suggestion-details">${item.building ? item.building + ' • ' : ''}${item.type} • ${item.capacity} capacity</div>
            </div>
        `).join('');

            searchSuggestions.innerHTML = html;
            searchSuggestions.style.display = 'block';
            currentSuggestionIndex = -1;
        }

        // Filter classrooms based on all criteria
        function filterClassrooms() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            const buildingValue = buildingFilter.value.toLowerCase();
            const typeValue = typeFilter.value.toLowerCase();
            const statusValue = statusFilter.value.toLowerCase();
            const sortValue = sortBy.value;

            let visibleRows = [];

            rows.forEach(row => {
                const room = row.getAttribute('data-room');
                const name = row.getAttribute('data-name');
                const building = row.getAttribute('data-building');
                const equipment = row.getAttribute('data-equipment');
                const type = row.getAttribute('data-type');
                const status = row.getAttribute('data-status');

                // Check if row matches all filters
                const matchesSearch = !searchTerm ||
                    room.includes(searchTerm) ||
                    name.includes(searchTerm) ||
                    building.includes(searchTerm) ||
                    equipment.includes(searchTerm) ||
                    type.includes(searchTerm);

                const matchesBuilding = !buildingValue || building === buildingValue;
                const matchesType = !typeValue || type === typeValue;
                const matchesStatus = !statusValue || status === statusValue;

                if (matchesSearch && matchesBuilding && matchesType && matchesStatus) {
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
            const noResultsRow = document.getElementById('noClassroomsRow');
            if (visibleRows.length === 0 && !noResultsRow) {
                const newRow = document.createElement('tr');
                newRow.id = 'noClassroomsRow';
                newRow.innerHTML = '<td colspan="8" class="text-center text-muted">No classrooms found matching your criteria</td>';
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
                    case 'room_asc':
                        aVal = a.getAttribute('data-room');
                        bVal = b.getAttribute('data-room');
                        return aVal.localeCompare(bVal);
                    case 'room_desc':
                        aVal = a.getAttribute('data-room');
                        bVal = b.getAttribute('data-room');
                        return bVal.localeCompare(aVal);
                    case 'name_asc':
                        aVal = a.getAttribute('data-name');
                        bVal = b.getAttribute('data-name');
                        return aVal.localeCompare(bVal);
                    case 'name_desc':
                        aVal = a.getAttribute('data-name');
                        bVal = b.getAttribute('data-name');
                        return bVal.localeCompare(aVal);
                    case 'building_asc':
                        aVal = a.getAttribute('data-building') || 'zzz';
                        bVal = b.getAttribute('data-building') || 'zzz';
                        return aVal.localeCompare(bVal);
                    case 'capacity_asc':
                        aVal = parseInt(a.getAttribute('data-capacity') || 0);
                        bVal = parseInt(b.getAttribute('data-capacity') || 0);
                        return aVal - bVal;
                    case 'capacity_desc':
                        aVal = parseInt(a.getAttribute('data-capacity') || 0);
                        bVal = parseInt(b.getAttribute('data-capacity') || 0);
                        return bVal - aVal;
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
            resultCount.textContent = `Showing ${count} of ${total} classrooms`;
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
            const room = suggestionItem.getAttribute('data-room');
            const name = suggestionItem.getAttribute('data-name');
            searchInput.value = `${room} - ${name}`;
            searchSuggestions.style.display = 'none';
            filterClassrooms();
        }

        // Event listeners
        searchInput.addEventListener('input', function () {
            const query = this.value.trim();
            if (query.length >= 1) {
                const suggestions = getClassroomSuggestions(query);
                showSuggestions(suggestions);
            } else {
                searchSuggestions.style.display = 'none';
            }
            filterClassrooms();
        });

        searchInput.addEventListener('keydown', handleKeyNavigation);

        searchInput.addEventListener('focus', function () {
            if (this.value.trim().length >= 1) {
                const suggestions = getClassroomSuggestions(this.value.trim());
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
        buildingFilter.addEventListener('change', filterClassrooms);
        typeFilter.addEventListener('change', filterClassrooms);
        statusFilter.addEventListener('change', filterClassrooms);
        sortBy.addEventListener('change', filterClassrooms);

        // Clear filters
        clearSearch.addEventListener('click', function () {
            searchInput.value = '';
            buildingFilter.value = '';
            typeFilter.value = '';
            statusFilter.value = '';
            sortBy.value = 'room_asc';
            searchSuggestions.style.display = 'none';
            filterClassrooms();
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