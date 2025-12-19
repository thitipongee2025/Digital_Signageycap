<?php
// admin/add_content.php - อัพโหลด Content ใหม่
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

$devices_result = $conn->query("SELECT * FROM devices ORDER BY device_name");
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['content_file'])) {
    $target_dir = "../assets/uploads/";
    $original_filename = basename($_FILES["content_file"]["name"]);
    $file_extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
    $new_filename = time() . '_' . $original_filename;
    $target_file = $target_dir . $new_filename;
    
    $duration_seconds = $_POST['duration_seconds'] ?? 10;
    
    $start_date_only = $_POST['start_date_only'] ?? '';
    $start_time_only = $_POST['start_time_only'] ?? '00:00';
    $end_date_only = $_POST['end_date_only'] ?? '';
    $end_time_only = $_POST['end_time_only'] ?? '00:00';

    $start_date_str = empty($start_date_only) ? null : $start_date_only . ' ' . $start_time_only;
    $end_date_str = empty($end_date_only) ? null : $end_date_only . ' ' . $end_time_only;
    
    $selected_devices = isset($_POST['devices']) ? $_POST['devices'] : [];

    $allowed_video = ['mp4', 'webm', 'ogg'];
    $allowed_image = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($file_extension, $allowed_video)) {
        $content_type = 'video';
        $duration_seconds = 0; 
    } elseif (in_array($file_extension, $allowed_image)) {
        $content_type = 'image';
    } else {
        $message = '<div class="alert alert-danger"><i class="bi bi-x-circle-fill"></i> ไม่รองรับประเภทไฟล์นี้</div>';
    }

    if (!isset($content_type)) goto end_upload; 

    if (!is_dir($target_dir)) {
        if (!mkdir($target_dir, 0777, true)) { 
            $message = '<div class="alert alert-danger"><i class="bi bi-x-circle-fill"></i> ไม่สามารถสร้างโฟลเดอร์อัพโหลดได้</div>';
            goto end_upload;
        }
    }
    
    if ($_FILES["content_file"]["error"] !== UPLOAD_ERR_OK) {
        $error_code = $_FILES["content_file"]["error"];
        $message = '<div class="alert alert-danger"><i class="bi bi-x-circle-fill"></i> เกิดข้อผิดพลาดในการอัพโหลด (Code: ' . $error_code . ')</div>';
        goto end_upload;
    }

    if (move_uploaded_file($_FILES["content_file"]["tmp_name"], $target_file)) {
        
        $sql = "INSERT INTO contents (filename, filepath, content_type, upload_by, duration_seconds, start_date, end_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        $start_date_db = $start_date_str ? date('Y-m-d H:i:s', strtotime($start_date_str)) : null;
        $end_date_db = $end_date_str ? date('Y-m-d H:i:s', strtotime($end_date_str)) : null;
        
        $stmt->bind_param("sssiiss", $original_filename, $new_filename, $content_type, $_SESSION['user_id'], $duration_seconds, $start_date_db, $end_date_db);
        
        if ($stmt->execute()) {
            $content_id = $conn->insert_id;
            
            $devices_to_insert = [];
            
            if (in_array('all_devices', $selected_devices)) {
                $all_devices_result = $conn->query("SELECT device_id FROM devices");
                while ($dev = $all_devices_result->fetch_assoc()) {
                    $devices_to_insert[] = $dev['device_id'];
                }
            } else {
                $devices_to_insert = $selected_devices;
            }

            if (!empty($devices_to_insert)) {
                $insert_dc_sql = "INSERT INTO device_content (device_id, content_id, display_order) 
                                  SELECT ?, ?, IFNULL(MAX(display_order), 0) + 1 
                                  FROM device_content WHERE device_id = ?";
                $stmt_dc = $conn->prepare($insert_dc_sql);

                foreach ($devices_to_insert as $device_id) {
                    if (is_numeric($device_id)) {
                        $stmt_dc->bind_param("iii", $device_id, $content_id, $device_id);
                        $stmt_dc->execute();
                    }
                }
                $stmt_dc->close();
            }

            $message = '<div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> อัพโหลด Content สำเร็จ!</div>';
        } else {
            $message = '<div class="alert alert-danger"><i class="bi bi-x-circle-fill"></i> Error: ' . $stmt->error . '</div>';
        }
        $stmt->close();
    } else {
        $message = '<div class="alert alert-danger"><i class="bi bi-x-circle-fill"></i> เกิดข้อผิดพลาดในการอัพโหลดไฟล์</div>';
    }
}
end_upload:
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อัพโหลด Content - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
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
                <li class="nav-item"><a class="nav-link" href="user_roles.php"><i class="bi bi-key"></i> จัดการสิทธิ์</a></li>
                <li class="nav-item"><a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a></li>
            </ul>
        </div>

        <div class="content-area" id="contentArea">
            <h1 class="mb-4 page-title"><i class="bi bi-plus-circle-fill"></i> อัพโหลด Content ใหม่</h1>
            <?php echo $message; ?>
            
            <div class="card shadow border-0">
                <div class="card-header card-header-custom">
                    <i class="bi bi-cloud-upload-fill me-2"></i> ข้อมูล Content
                </div>
                <div class="card-body">
                    <form action="add_content.php" method="POST" enctype="multipart/form-data">

                        <div class="mb-4">
                            <label for="content_file" class="form-label fw-bold">
                                <i class="bi bi-file-earmark-arrow-up"></i> เลือกไฟล์
                            </label>
                            <input type="file" class="form-control" id="content_file" name="content_file" accept=".mp4,.webm,.ogg,.jpg,.jpeg,.png,.gif" required>
                            <small class="form-text text-muted">รองรับ: MP4, WebM, OGG, JPG, PNG, GIF</small>
                        </div>
                        
                        <div class="mb-4">
                            <label for="duration_seconds" class="form-label fw-bold">
                                <i class="bi bi-clock"></i> กำหนดเวลาเล่น (วินาที)
                            </label>
                            <input type="number" class="form-control" id="duration_seconds" name="duration_seconds" min="0" value="10" required>
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle"></i> แนะนำ 10 วินาที สำหรับภาพ, 0 สำหรับวิดีโอ
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
                                        <input type="date" class="form-control" id="start_date_only" name="start_date_only">
                                    </div>
                                    <div class="col-5">
                                        <input type="time" class="form-control" id="start_time_only" name="start_time_only" value="00:00">
                                    </div>
                                </div>
                                <small class="form-text text-muted">(ปล่อยว่าง = แสดงทันที)</small>
                            </div>
                            
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-bold">วันที่และเวลาสิ้นสุด</label>
                                <div class="row g-2">
                                    <div class="col-7">
                                        <input type="date" class="form-control" id="end_date_only" name="end_date_only">
                                    </div>
                                    <div class="col-5">
                                        <input type="time" class="form-control" id="end_time_only" name="end_time_only" value="00:00">
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
                                <option value="all_devices" class="fw-bold text-primary" selected>-- เล่นบนทุกอุปกรณ์ --</option>
                                <?php 
                                if ($devices_result->num_rows > 0) {
                                    $devices_result->data_seek(0);
                                }
                                while($device = $devices_result->fetch_assoc()) {
                                    echo '<option value="' . $device['device_id'] . '">&#128205; ' . htmlspecialchars($device['device_name']) . ' (' . htmlspecialchars($device['location']) . ')</option>';
                                }
                                ?>
                            </select>
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle"></i> กด Ctrl/Cmd เพื่อเลือกหลายอุปกรณ์
                            </small>
                        </div>

                        <div class="d-flex flex-column flex-md-row justify-content-end gap-2 pt-3 border-top">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-upload"></i> อัพโหลดและบันทึก
                            </button>
                            <a href="contents.php" class="btn btn-secondary btn-lg">
                                <i class="bi bi-x-circle"></i> ยกเลิก
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/responsive_sidebar.js"></script>
    <script>
        document.getElementById('devices').addEventListener('change', function() {
            const allDevicesOption = this.querySelector('option[value="all_devices"]');
            
            const otherSelected = Array.from(this.options).some(option => 
                option.selected && option.value !== 'all_devices' && option.value !== ''
            );
            
            if (allDevicesOption.selected && otherSelected) {
                allDevicesOption.selected = false;
            } 
            
            const nothingSelected = Array.from(this.options).every(option => !option.selected || option.value === '');
            if (nothingSelected) {
                allDevicesOption.selected = true;
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
