<?php
include '../config.php';
// ตรวจสอบ Login และสิทธิ์
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$message = '';

// --- ดึงข้อมูลผู้ใช้งาน ---
$logged_in_user = ['fullname' => 'ไม่ระบุชื่อ', 'position' => 'ไม่ระบุตำแหน่ง', 'profile_initial' => 'A'];
$user_sql = "SELECT fullname FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
if ($user_data = $user_result->fetch_assoc()) {
    $logged_in_user['fullname'] = htmlspecialchars($user_data['fullname']);
    $logged_in_user['position'] = ($user_role === 'admin') ? 'ผู้ดูแลระบบ' : 'ผู้ใช้งานทั่วไป';
    $parts = explode(' ', trim($user_data['fullname']));
    $logged_in_user['profile_initial'] = mb_substr($parts[0], 0, 1, 'UTF-8');
}
$user_stmt->close();

// ดึงรายการอุปกรณ์ที่ได้รับสิทธิ์
if ($user_role === 'admin') {
    $devices_sql = "SELECT device_id, device_name, location FROM devices ORDER BY device_name";
    $devices_stmt = $conn->prepare($devices_sql);
} else {
    $devices_sql = "SELECT d.device_id, d.device_name, d.location FROM devices d 
                    JOIN user_permissions up ON d.device_id = up.device_id 
                    WHERE up.user_id = ? ORDER BY d.device_name";
    $devices_stmt = $conn->prepare($devices_sql);
    $devices_stmt->bind_param("i", $user_id);
}
$devices_stmt->execute();
$devices_result = $devices_stmt->get_result();
$allowed_devices = $devices_result->fetch_all(MYSQLI_ASSOC);
$allowed_device_ids = array_column($allowed_devices, 'device_id');
$devices_stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['content_file'])) {
    $target_dir = "../assets/uploads/";
    $original_filename = basename($_FILES["content_file"]["name"]);
    $file_extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
    $new_filename = time() . '_' . $original_filename;
    $target_file = $target_dir . $new_filename;
    
    $duration_seconds = $_POST['duration_seconds'] ?? 10;
    
    // รับค่าวันที่และเวลา
    $start_date_only = $_POST['start_date_only'] ?? '';
    $start_time_only = $_POST['start_time_only'] ?? '';
    $end_date_only = $_POST['end_date_only'] ?? '';
    $end_time_only = $_POST['end_time_only'] ?? '';

    $start_date_str = empty($start_date_only) ? null : $start_date_only . ' ' . $start_time_only;
    $end_date_str = empty($end_date_only) ? null : $end_date_only . ' ' . $end_time_only;
    
    $selected_devices = isset($_POST['devices']) ? $_POST['devices'] : [];

    $allowed_video = ['mp4', 'webm', 'ogg'];
    $allowed_image = ['jpg', 'jpeg', 'png', 'gif'];

    $uploadOk = 1;

    // ตรวจสอบว่าเลือกอุปกรณ์หรือไม่
    if (empty($selected_devices)) {
        $message = '<div class="alert alert-danger"><i class="bi bi-x-circle-fill"></i> กรุณาเลือกอุปกรณ์อย่างน้อย 1 เครื่อง</div>';
        $uploadOk = 0;
    }

    // ตรวจสอบประเภทไฟล์
    if ($uploadOk) {
        if (in_array($file_extension, $allowed_video)) {
            $content_type = 'video';
            $duration_seconds = 0; 
        } elseif (in_array($file_extension, $allowed_image)) {
            $content_type = 'image';
        } else {
            $message = '<div class="alert alert-danger"><i class="bi bi-x-circle-fill"></i> ไม่รองรับประเภทไฟล์นี้</div>';
            $uploadOk = 0;
        }
    }

    // สร้างโฟลเดอร์ถ้ายังไม่มี
    if ($uploadOk && !is_dir($target_dir)) {
        if (!mkdir($target_dir, 0777, true)) { 
            $message = '<div class="alert alert-danger"><i class="bi bi-x-circle-fill"></i> ไม่สามารถสร้างโฟลเดอร์อัพโหลดได้</div>';
            $uploadOk = 0;
        }
    }

    if ($uploadOk && move_uploaded_file($_FILES["content_file"]["tmp_name"], $target_file)) {
        $sql = "INSERT INTO contents (filename, filepath, content_type, duration_seconds, upload_by, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        $start_date_db = $start_date_str ? date('Y-m-d H:i:s', strtotime($start_date_str)) : null;
        $end_date_db = $end_date_str ? date('Y-m-d H:i:s', strtotime($end_date_str)) : null;
        
        $stmt->bind_param("sssiiss", $original_filename, $new_filename, $content_type, $duration_seconds, $user_id, $start_date_db, $end_date_db);
        
        if ($stmt->execute()) {
            $content_id = $conn->insert_id;
            
            $conn->begin_transaction();
            try {
                $devices_to_insert = [];
                
                // ถ้าเลือก "ทุกอุปกรณ์ที่ได้รับสิทธิ์"
                if (in_array('all_devices', $selected_devices)) {
                    $devices_to_insert = $allowed_device_ids;
                } else {
                    // เลือกเฉพาะอุปกรณ์ที่ระบุ
                    foreach ($selected_devices as $dev_id) {
                        if (in_array($dev_id, $allowed_device_ids)) {
                            $devices_to_insert[] = $dev_id;
                        }
                    }
                }

                // บันทึกลงฐานข้อมูล
                if (!empty($devices_to_insert)) {
                    $sql_dc = "INSERT INTO device_content (device_id, content_id, display_order) 
                               SELECT ?, ?, IFNULL(MAX(display_order), 0) + 1 
                               FROM device_content WHERE device_id = ?";
                    $stmt_dc = $conn->prepare($sql_dc);
                    
                    foreach ($devices_to_insert as $device_id) {
                        $stmt_dc->bind_param("iii", $device_id, $content_id, $device_id);
                        $stmt_dc->execute();
                    }
                    $stmt_dc->close();
                }
                
                $conn->commit();
                $message = '<div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> อัพโหลด Content สำเร็จ!</div>';
                
                // Redirect หลังสำเร็จ
                header("refresh:2;url=my_content.php");
            } catch (Exception $e) {
                $conn->rollback();
                $message = '<div class="alert alert-danger"><i class="bi bi-x-circle-fill"></i> เกิดข้อผิดพลาด: ' . $e->getMessage() . '</div>';
            }
        } else {
            $message = '<div class="alert alert-danger"><i class="bi bi-x-circle-fill"></i> Error: ' . $stmt->error . '</div>';
        }
        $stmt->close();
    } elseif ($uploadOk) {
        $message = '<div class="alert alert-danger"><i class="bi bi-x-circle-fill"></i> เกิดข้อผิดพลาดในการอัพโหลดไฟล์</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Signage - Upload</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/user-panel.css">
    <link rel="stylesheet" href="../assets/css/user-upload.css">
</head>
<body>
    <!-- Sidebar Toggle Button -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>

    <!-- Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h5 class="text-center mb-2">📺 Digital signage ycap</h5>
                <hr class="sidebar-divider">
            </div>
            
            <!-- User Profile -->
            <div class="user-profile">
                <div class="profile-initial"><?php echo $logged_in_user['profile_initial']; ?></div>
                <p class="profile-name" title="<?php echo $logged_in_user['fullname']; ?>"><?php echo $logged_in_user['fullname']; ?></p>
                <p class="profile-position"><?php echo $logged_in_user['position']; ?></p>
            </div>
            <hr class="sidebar-divider">
            
            <!-- Navigation Menu -->
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                        <i class="bi bi-house-door"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="my_content.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'my_content.php' ? 'active' : ''; ?>">
                        <i class="bi bi-film"></i> Content ของฉัน
                    </a>
                </li>
                <li class="nav-item">
                    <a href="upload.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'upload.php' ? 'active' : ''; ?>">
                        <i class="bi bi-cloud-arrow-up"></i> อัพโหลด Content
                    </a>
                </li>
                <li class="nav-item">
                    <a href="device_status.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'device_status.php' ? 'active' : ''; ?>">
                        <i class="bi bi-display"></i> สถานะอุปกรณ์
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../logout.php" class="nav-link text-danger">
                        <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
                    </a>
                </li>
            </ul>
        </div>

        <!-- Content Area -->
        <div class="content-area" id="contentArea">
            <h1 class="mb-4 page-title"><i class="bi bi-cloud-upload-fill"></i> อัพโหลด Content ใหม่</h1>
            
            <?php echo $message; ?>
            
            <div class="card shadow border-0">
                <div class="card-header card-header-custom">
                    <i class="bi bi-cloud-upload-fill me-2"></i> ข้อมูล Content
                </div>
                <div class="card-body">
                    <form action="upload.php" method="POST" enctype="multipart/form-data">
                        
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
                        
                        <div class="row g-3 mb-4">
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
                                <option value="all_devices" class="fw-bold text-primary" selected>-- เล่นบนทุกอุปกรณ์ที่ได้รับสิทธิ์ --</option>
                                <?php foreach ($allowed_devices as $device): ?>
                                <option value="<?php echo $device['device_id']; ?>">
                                    📍 <?php echo htmlspecialchars($device['device_name']); ?> 
                                    <?php if (!empty($device['location'])): ?>
                                        (<?php echo htmlspecialchars($device['location']); ?>)
                                    <?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">
                                <i class="bi bi-info-circle"></i> กด Ctrl/Cmd เพื่อเลือกหลายอุปกรณ์
                            </small>
                        </div>

                        <div class="d-flex flex-column flex-md-row justify-content-end gap-2 pt-3 border-top">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-upload"></i> อัพโหลดและบันทึก
                            </button>
                            <a href="my_content.php" class="btn btn-secondary btn-lg">
                                <i class="bi bi-x-circle"></i> ยกเลิก
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="footer-content-area">
                <h6>&copy; จัดทำโดย นายฐิติพงศ์ ภาสวร โครงการทดลองจ้างงานบุคคลออทิสติก รุ่นที่13</h6>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/sidebar-menu.js"></script>
    <script src="../assets/js/user-upload.js"></script>
</body>
</html>
<?php $conn->close(); ?>