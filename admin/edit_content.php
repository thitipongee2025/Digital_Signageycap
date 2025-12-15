<?php
// admin/edit_content.php - แก้ไข Content (รวม Header/Footer)
include '../config.php';
checkAdminLogin();

// --- 1. [ส่วนดึงข้อมูลผู้ใช้งานที่ล็อกอิน] ---
$logged_in_user = [
    'fullname' => 'ไม่ระบุชื่อ',
    'position' => 'ไม่ระบุตำแหน่ง',
    'profile_initial' => 'A'
];

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    $user_sql = "
        SELECT 
            u.fullname,
            u.position
        FROM 
            users u
        WHERE 
            u.user_id = ?
    ";
    
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    
    if ($user_data = $user_result->fetch_assoc()) {
        $logged_in_user['fullname'] = htmlspecialchars($user_data['fullname']);
        $logged_in_user['position'] = htmlspecialchars($user_data['position']);
        
        $initials = '';
        if (!empty($user_data['fullname'])) {
            $parts = explode(' ', trim($user_data['fullname']));
            $initials = mb_substr($parts[0], 0, 1, 'UTF-8'); 
        }
        $logged_in_user['profile_initial'] = empty($initials) ? 'U' : $initials;
    }
    $user_stmt->close();
}
// --- [สิ้นสุดส่วนดึงข้อมูล] ---

$content_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';

if ($content_id === 0) {
    header("Location: contents.php");
    exit();
}

// --- 1. Process Form Submission (UPDATE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_content'])) {
    
    $duration_seconds = $_POST['duration_seconds'] ?? 10;
    
    $start_date_only = $_POST['start_date_only'] ?? '';
    $start_time_only = $_POST['start_time_only'] ?? '00:00';
    $end_date_only = $_POST['end_date_only'] ?? '';
    $end_time_only = $_POST['end_time_only'] ?? '00:00';
    
    $start_date_str = empty($start_date_only) ? null : $start_date_only . ' ' . $start_time_only;
    $end_date_str = empty($end_date_only) ? null : $end_date_only . ' ' . $end_time_only;
    
    $selected_devices = isset($_POST['devices']) ? $_POST['devices'] : [];
    
    $start_date_db = $start_date_str ? date('Y-m-d H:i:s', strtotime($start_date_str)) : null;
    $end_date_db = $end_date_str ? date('Y-m-d H:i:s', strtotime($end_date_str)) : null;
    
    $sql = "UPDATE contents SET duration_seconds = ?, start_date = ?, end_date = ? WHERE content_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issi", $duration_seconds, $start_date_db, $end_date_db, $content_id);
    
    if ($stmt->execute()) {
        
        $conn->query("DELETE FROM device_content WHERE content_id = $content_id");

        if (!empty($selected_devices)) {
            $insert_dc_sql = "INSERT INTO device_content (device_id, content_id, display_order) 
                              SELECT ?, ?, MAX(display_order) + 1 FROM device_content WHERE device_id = ?";
            $stmt_dc = $conn->prepare($insert_dc_sql);

            foreach ($selected_devices as $device_id) {
                if ($device_id != 'all_devices') {
                    $stmt_dc->bind_param("iii", $device_id, $content_id, $device_id);
                    $stmt_dc->execute();
                }
            }
            $stmt_dc->close();
        }

        $message = '<div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> แก้ไข Content สำเร็จ!</div>';
    } else {
        $message = '<div class="alert alert-danger"><i class="bi bi-x-circle-fill"></i> Error: ' . $stmt->error . '</div>';
    }
    $stmt->close();
}

// --- 2. Fetch Content Data ---
$content_sql = "SELECT * FROM contents WHERE content_id = ?";
$content_stmt = $conn->prepare($content_sql);
$content_stmt->bind_param("i", $content_id);
$content_stmt->execute();
$content_result = $content_stmt->get_result();

if ($content_result->num_rows === 0) {
    $message = '<div class="alert alert-warning">ไม่พบ Content ที่ต้องการแก้ไข</div>';
    $content = null;
} else {
    $content = $content_result->fetch_assoc();
}
$content_stmt->close();

// --- 3. Fetch Devices and Current Permissions ---
$devices_result = $conn->query("SELECT * FROM devices ORDER BY device_name");

$current_devices = [];
if ($content) {
    $perm_result = $conn->query("SELECT device_id FROM device_content WHERE content_id = $content_id");
    while($row = $perm_result->fetch_assoc()) {
        $current_devices[] = $row['device_id'];
    }
}

$start_date_db_raw = $content['start_date'] ? strtotime($content['start_date']) : null;
$end_date_db_raw = $content['end_date'] ? strtotime($content['end_date']) : null;
$current_start_date = $start_date_db_raw ? date('Y-m-d', $start_date_db_raw) : '';
$current_start_time = $start_date_db_raw ? date('H:i', $start_date_db_raw) : '00:00';
$current_end_date = $end_date_db_raw ? date('Y-m-d', $end_date_db_raw) : '';
$current_end_time = $end_date_db_raw ? date('H:i', $end_date_db_raw) : '00:00';

$current_duration = $content['duration_seconds'] ?? 10;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไข Content - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/responsive_admin.css" rel="stylesheet">
    <style>
        .content-preview {
            max-width: 100%;
            height: auto;
            max-height: 300px;
            display: block;
            margin: 0 auto 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        @media (max-width: 640px) {
            .content-preview {
                max-height: 200px;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle">
        <i class="bi bi-list"></i>
    </button>

    <!-- Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="d-flex">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h5 class="text-center mb-2">📺Digital signage ycap</h5>
                <button class="mobile-close-btn" id="mobileCloseBtn">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <hr class="sidebar-divider">
            
            <div class="user-profile">
                <div class="profile-initial"><?php echo $logged_in_user['profile_initial']; ?></div>
                <p class="profile-name" title="<?php echo $logged_in_user['fullname']; ?>"><?php echo $logged_in_user['fullname']; ?></p>
                <p class="profile-position"><?php echo $logged_in_user['position']; ?></p>
            </div>
            <hr class="sidebar-divider">
            
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link active" href="contents.php"><i class="bi bi-folder2-open"></i> จัดการ Content</a></li>
                <li class="nav-item"><a class="nav-link" href="devices.php"><i class="bi bi-tv"></i> จัดการอุปกรณ์</a></li>
                <li class="nav-item"><a class="nav-link" href="users.php"><i class="bi bi-people"></i> จัดการสมาชิก</a></li>
                <li class="nav-item"><a class="nav-link" href="add_user.php"><i class="bi bi-people"></i> ลงทะเบียนสมาชิก</a></li>
                <li class="nav-item"><a class="nav-link" href="user_roles.php"><i class="bi bi-key"></i> จัดการสิทธิ์</a></li>
                <li class="nav-item"><a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a></li>
            </ul>
        </div>

        <div class="content-area" id="contentArea">
            <h1 class="mb-4 page-title"><i class="bi bi-pencil-square"></i> แก้ไข Content</h1>
            <?php echo $message; ?>
            
            <?php if ($content): ?>
            <div class="card shadow border-0">
                <div class="card-header card-header-custom">
                    <i class="bi bi-file-earmark-code me-2"></i> ไฟล์: <?php echo htmlspecialchars($content['filename']); ?>
                </div>
                <div class="card-body">
                    <div class="mb-4 text-center">
                        <?php 
                        $file_path = '../assets/uploads/' . $content['filepath'];
                        if ($content['content_type'] === 'image'): ?>
                            <img src="<?php echo $file_path; ?>" alt="Preview" class="content-preview img-fluid">
                        <?php elseif ($content['content_type'] === 'video'): ?>
                            <video controls class="content-preview img-fluid">
                                <source src="<?php echo $file_path; ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        <?php endif; ?>
                    </div>

                    <form action="edit_content.php?id=<?php echo $content_id; ?>" method="POST">

                        <div class="mb-4">
                            <label for="duration_seconds" class="form-label fw-bold">
                                <i class="bi bi-clock"></i> กำหนดเวลาเล่น (วินาที)
                            </label>
                            <input type="number" class="form-control" id="duration_seconds" name="duration_seconds" min="0" value="<?php echo $current_duration; ?>" required>
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle"></i> สำหรับวิดีโอ แนะนำ 0 เพื่อเล่นตามความยาวจริง
                            </small>
                        </div>

                        <h5 class="mt-4 mb-3 border-bottom pb-2 text-info">
                            <i class="bi bi-clock-history"></i> ช่วงเวลาแสดงผล
                        </h5>
                        
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-bold">วันที่และเวลาเริ่มแสดงผล</label>
                                <div class="row g-2">
                                    <div class="col-7">
                                        <input type="date" class="form-control" id="start_date_only" name="start_date_only" value="<?php echo $current_start_date; ?>">
                                    </div>
                                    <div class="col-5">
                                        <input type="time" class="form-control" id="start_time_only" name="start_time_only" value="<?php echo $current_start_time; ?>">
                                    </div>
                                </div>
                                <small class="form-text text-muted">(ปล่อยว่าง = แสดงทันที)</small>
                            </div>
                            
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-bold">วันที่และเวลาสิ้นสุด</label>
                                <div class="row g-2">
                                    <div class="col-7">
                                        <input type="date" class="form-control" id="end_date_only" name="end_date_only" value="<?php echo $current_end_date; ?>">
                                    </div>
                                    <div class="col-5">
                                        <input type="time" class="form-control" id="end_time_only" name="end_time_only" value="<?php echo $current_end_time; ?>">
                                    </div>
                                </div>
                                <small class="form-text text-muted">(ปล่อยว่าง = แสดงตลอดไป)</small>
                            </div>
                        </div>

                        <h5 class="mt-5 mb-3 border-bottom pb-2 text-info">
                            <i class="bi bi-list-task"></i> อุปกรณ์เป้าหมาย
                        </h5>
                        
                        <div class="mb-4">
                            <select multiple class="form-select" id="devices" name="devices[]" size="8" required>
                                <?php 
                                $devices_result->data_seek(0);
                                while($device = $devices_result->fetch_assoc()) {
                                    $selected = in_array($device['device_id'], $current_devices) ? 'selected' : '';
                                    echo '<option value="' . $device['device_id'] . '" ' . $selected . '>
                                        &#128205; ' . htmlspecialchars($device['device_name']) . ' (' . htmlspecialchars($device['location']) . ')
                                    </option>';
                                }
                                ?>
                            </select>
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle"></i> กด Ctrl/Cmd เพื่อเลือกหลายอุปกรณ์
                            </small>
                        </div>

                        <div class="d-flex flex-column flex-md-row justify-content-end gap-2 pt-3 border-top">
                            <button type="submit" name="update_content" class="btn btn-success btn-lg">
                                <i class="bi bi-save-fill"></i> บันทึกการแก้ไข
                            </button>
                            <a href="contents.php" class="btn btn-secondary btn-lg">
                                <i class="bi bi-arrow-left-circle-fill"></i> กลับ
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            <?php else: ?>
                <a href="contents.php" class="btn btn-secondary btn-lg">
                    <i class="bi bi-arrow-left-circle-fill"></i> กลับไปรายการ Content
                </a>
            <?php endif; ?>
           <div class="footer-content-area">
                <h6>&copy; จัดทำโดย นายฐิติพงศ์ ภาสวร โครงการทดลองจ้างงานบุคคลออทิสติก รุ่นที่13</h6>
            </div> 
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/responsive_sidebar.js"></script>
</body>
</html>
<?php $conn->close(); ?>

