<?php
require_once __DIR__ . '/../config/SessionManager.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Teacher.php';
require_once __DIR__ . '/../classes/Subject.php';
require_once __DIR__ . '/../classes/Classroom.php';
require_once __DIR__ . '/../classes/Schedule.php';

SessionManager::startTeacherSession();
if (!SessionManager::isLoggedIn() || !SessionManager::isTeacher()) {
    header('Location: ../login.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Get current teacher ID
$userTeacherId = null;
$query = "SELECT id FROM teachers WHERE user_id = ?";
$stmt = $conn->prepare($query);
$user_id = SessionManager::getUserId();
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $userTeacherId = $row['id'];
}

$teacherObj = new Teacher($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>

<body>
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap"></i> CMS Professor Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link">Welcome,
                            <?php echo htmlspecialchars(SessionManager::getUsername()); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="main-content" style="margin-top: 90px !important; padding-top: 20px !important;">
        <div class="container-fluid">
            <div class="col-12">
                <div class="page-header">
                    <h1>My Teaching Schedule</h1>
                    <p>View all your assigned classes and schedules</p>
                </div>

                <!-- Schedule Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Weekly Schedule</h5>
                        <div class="btn-group">
                            <button type="button" class="btn btn-success btn-sm" onclick="exportToPDF()">
                                <i class="fas fa-file-pdf"></i> Export PDF
                            </button>
                            <button type="button" class="btn btn-primary btn-sm" onclick="exportToCSV()">
                                <i class="fas fa-file-csv"></i> Export CSV
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <?php
                            // Get all time slots
                            $timeSlotsQuery = "SELECT * FROM time_slots WHERE status = 'active' ORDER BY start_time";
                            $timeSlotsResult = $conn->query($timeSlotsQuery);
                            $timeSlots = [];
                            while ($slot = $timeSlotsResult->fetch_assoc()) {
                                $timeSlots[] = $slot;
                            }

                            // Get teacher's schedules organized by day and time
                            $scheduleData = [];
                            if ($userTeacherId) {
                                $schedules = $teacherObj->getSchedules($userTeacherId);
                                while ($schedule = $schedules->fetch_assoc()) {
                                    $day = $schedule['day_of_week'];
                                    $timeSlotId = $schedule['time_slot_id'];
                                    $scheduleData[$day][$timeSlotId] = $schedule;
                                }
                            }

                            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                            ?>

                            <table class="table table-bordered schedule-grid">
                                <thead class="table-light">
                                    <tr>
                                        <th class="time-column">Time</th>
                                        <?php foreach ($days as $day): ?>
                                            <th class="text-center"><?php echo $day; ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($timeSlots as $timeSlot): ?>
                                        <tr>
                                            <td class="time-slot">
                                                <div class="text-center">
                                                    <strong><?php echo date('g:i A', strtotime($timeSlot['start_time'])); ?></strong><br>
                                                    <small
                                                        class="text-muted"><?php echo date('g:i A', strtotime($timeSlot['end_time'])); ?></small>
                                                </div>
                                            </td>
                                            <?php foreach ($days as $day): ?>
                                                <td class="schedule-cell">
                                                    <?php
                                                    if (isset($scheduleData[$day][$timeSlot['id']])) {
                                                        $class = $scheduleData[$day][$timeSlot['id']];
                                                        ?>
                                                        <div class="class-block">
                                                            <div class="subject-code">
                                                                <strong><?php echo htmlspecialchars($class['subject_code'] ?? 'N/A'); ?></strong>
                                                            </div>
                                                            <div class="subject-name">
                                                                <?php echo htmlspecialchars($class['subject_name']); ?>
                                                            </div>
                                                            <div class="room-info">
                                                                <small><i class="fas fa-map-marker-alt"></i>
                                                                    <?php echo htmlspecialchars($class['room_number']); ?></small>
                                                            </div>
                                                        </div>
                                                        <?php
                                                    }
                                                    ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                            <?php if (empty($scheduleData)): ?>
                                <div class="text-center text-muted mt-3">
                                    <p>No schedules assigned</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Current Assignment Card -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Current Assignment Details</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                if ($userTeacherId) {
                                    $current = $teacherObj->getCurrentAssignment($userTeacherId);
                                    if ($current) {
                                        ?>
                                        <p><strong>Subject:</strong> <?php echo htmlspecialchars($current['subject_name']); ?>
                                        </p>
                                        <p><strong>Room:</strong>
                                            <?php echo htmlspecialchars($current['room_number'] . ' - ' . $current['room_name']); ?>
                                        </p>
                                        <p><strong>Time:</strong>
                                            <?php echo htmlspecialchars($current['start_time'] . ' - ' . $current['end_time']); ?>
                                        </p>
                                        <p><strong>Status:</strong> <span class="badge badge-success">IN CLASS</span></p>
                                        <?php
                                    } else {
                                        echo '<p class="text-muted">No current class assignment</p>';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Assigned Subjects</h5>
                            </div>
                            <div class="card-body">
                                <div style="max-height: 250px; overflow-y: auto;">
                                    <?php
                                    if ($userTeacherId) {
                                        $query = "SELECT s.subject_name, s.subject_code FROM subjects s 
                                             JOIN teacher_subjects ts ON s.id = ts.subject_id 
                                             WHERE ts.teacher_id = ? AND ts.status = 'active'";
                                        $stmt = $conn->prepare($query);
                                        $stmt->bind_param("i", $userTeacherId);
                                        $stmt->execute();
                                        $result = $stmt->get_result();

                                        if ($result->num_rows > 0) {
                                            while ($subject = $result->fetch_assoc()) {
                                                echo '<p><strong>' . htmlspecialchars($subject['subject_code']) . '</strong><br>' .
                                                    htmlspecialchars($subject['subject_name']) . '</p>';
                                            }
                                        } else {
                                            echo '<p class="text-muted">No subjects assigned</p>';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .schedule-grid {
                font-size: 0.9rem;
            }

            .schedule-grid th {
                background-color: #e8f5e8 !important;
                text-align: center;
                font-weight: bold;
                border: 1px solid #28a745;
                padding: 12px 8px;
            }

            .time-column {
                width: 120px;
                min-width: 120px;
            }

            .time-slot {
                background-color: #e8f5e8;
                border: 1px solid #28a745;
                font-size: 0.85rem;
                padding: 20px 8px;
                vertical-align: middle;
            }

            .schedule-cell {
                width: 140px;
                height: 80px;
                vertical-align: middle;
                border: 1px solid #28a745;
                padding: 4px;
                position: relative;
            }

            .class-block {
                background-color: #d4edda;
                border: 1px solid #28a745;
                border-radius: 4px;
                padding: 6px;
                height: 100%;
                display: flex;
                flex-direction: column;
                justify-content: center;
                text-align: center;
                position: relative;
            }

            .subject-code {
                font-size: 0.85rem;
                font-weight: bold;
                line-height: 1.2;
                margin-bottom: 2px;
                color: #155724;
            }

            .subject-name {
                font-size: 0.75rem;
                line-height: 1.1;
                margin-bottom: 2px;
                color: #155724;
                word-wrap: break-word;
            }

            .room-info {
                font-size: 0.7rem;
                color: #6c757d;
                margin-top: auto;
            }

            .schedule-cell:empty {
                background-color: #f8f9fa;
            }

            /* Responsive design */
            @media (max-width: 768px) {
                .schedule-grid {
                    font-size: 0.75rem;
                }

                .schedule-cell {
                    width: 100px;
                    height: 60px;
                }

                .subject-code {
                    font-size: 0.7rem;
                }

                .subject-name {
                    font-size: 0.65rem;
                }

                .room-info {
                    font-size: 0.6rem;
                }
            }
        </style>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
        <script
            src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>

        <script>
            // Schedule data from PHP - properly serialized
            const scheduleData = <?php echo json_encode($scheduleData); ?>;
            const timeSlots = <?php echo json_encode($timeSlots); ?>;
            const teacherName = '<?php echo htmlspecialchars(SessionManager::getUsername()); ?>';
            const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

            // Export to PDF function
            function exportToPDF() {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF();

                // Add header
                doc.setFontSize(18);
                doc.text('Weekly Teaching Schedule', 14, 22);

                doc.setFontSize(12);
                doc.text('Teacher: ' + teacherName, 14, 35);
                doc.text('Generated on: ' + new Date().toLocaleDateString(), 14, 45);

                // Prepare data for the table
                const tableData = [];

                timeSlots.forEach(timeSlot => {
                    const row = [
                        new Date('1970-01-01T' + timeSlot.start_time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) +
                        ' - ' +
                        new Date('1970-01-01T' + timeSlot.end_time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                    ];

                    days.forEach(day => {
                        if (scheduleData[day] && scheduleData[day][timeSlot.id]) {
                            const classInfo = scheduleData[day][timeSlot.id];
                            const cellContent = (classInfo.subject_code || 'N/A') + '\n' +
                                classInfo.subject_name + '\nRoom: ' + classInfo.room_number;
                            row.push(cellContent);
                        } else {
                            row.push('');
                        }
                    });

                    tableData.push(row);
                });

                if (tableData.length === 0) {
                    tableData.push(['No schedules assigned', '', '', '', '', '', '', '']);
                }

                // Create table
                doc.autoTable({
                    head: [['Time', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']],
                    body: tableData,
                    startY: 55,
                    styles: {
                        fontSize: 8,
                        cellPadding: 2
                    },
                    headStyles: {
                        fillColor: [40, 167, 69],
                        textColor: 255
                    },
                    alternateRowStyles: {
                        fillColor: [248, 249, 250]
                    },
                    columnStyles: {
                        0: { cellWidth: 25 }
                    }
                });

                // Save the PDF
                const fileName = 'Weekly_Schedule_' + teacherName.replace(/[^a-z0-9]/gi, '_') + '_' +
                    new Date().toISOString().split('T')[0] + '.pdf';
                doc.save(fileName);
            }

            // Export to CSV function
            function exportToCSV() {
                let csvContent = 'Time,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday\n';

                timeSlots.forEach(timeSlot => {
                    let row = '"' +
                        new Date('1970-01-01T' + timeSlot.start_time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) +
                        ' - ' +
                        new Date('1970-01-01T' + timeSlot.end_time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) +
                        '"';

                    days.forEach(day => {
                        if (scheduleData[day] && scheduleData[day][timeSlot.id]) {
                            const classInfo = scheduleData[day][timeSlot.id];
                            const cellContent = (classInfo.subject_code || 'N/A') + ' - ' +
                                classInfo.subject_name + ' (Room: ' + classInfo.room_number + ')';
                            row += ',"' + cellContent.replace(/"/g, '""') + '"';
                        } else {
                            row += ',""';
                        }
                    });

                    csvContent += row + '\n';
                });

                if (timeSlots.length === 0) {
                    csvContent += '"No schedules assigned","","","","","","",""\n';
                }

                // Create and download CSV file
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                const fileName = 'Weekly_Schedule_' + teacherName.replace(/[^a-z0-9]/gi, '_') + '_' +
                    new Date().toISOString().split('T')[0] + '.csv';
                link.setAttribute('download', fileName);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            }
        </script>
</body>

</html>