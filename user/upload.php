<?php
include '../config.php';
// ตรวจสอบ Login และสิทธิ์ User
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// --- ดึงข้อมูลผู้ใช้งาน ---
$logged_in_user = [
    'fullname' => 'ไม่ระบุชื่อ',
    'position' => 'ไม่ระบุตำแหน่ง',
    'profile_initial' => 'A'
];

$user_sql = "SELECT fullname FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if ($user_data = $user_result->fetch_assoc()) {
    $logged_in_user['fullname'] = htmlspecialchars($user_data['fullname']);
    $logged_in_user['position'] = 'ผู้ใช้งานทั่วไป'; 
    
    $initials = '';
    if (!empty($user_data['fullname'])) {
        $parts = explode(' ', trim($user_data['fullname']));
        $initials = mb_substr($parts[0], 0, 1, 'UTF-8'); 
    }
    $logged_in_user['profile_initial'] = empty($initials) ? 'U' : $initials;
}
$user_stmt->close();

// ดึงรายการอุปกรณ์ที่ User นี้มีสิทธิ์
$devices_sql = "
    SELECT d.device_id, d.device_name 
    FROM devices d
    JOIN user_permissions up ON d.device_id = up.device_id
    WHERE up.user_id = ?
    ORDER BY d.device_name
";
$devices_stmt = $conn->prepare($devices_sql);
$devices_stmt->bind_param("i", $user_id);
$devices_stmt->execute();
$devices_result = $devices_stmt->get_result();
$allowed_device_ids = [];
while($row = $devices_result->fetch_assoc()) {
    $allowed_device_ids[] = $row['device_id'];
}
$devices_stmt->close();

if (empty($allowed_device_ids)) {
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'คุณยังไม่ได้รับสิทธิ์ให้เข้าถึงอุปกรณ์ใดๆ'];
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['content_file'])) {
    $target_dir = "../assets/uploads/";
    $original_filename = basename($_FILES["content_file"]["name"]);
    $file_extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
    $new_filename = time() . '_' . $original_filename;
    $target_file = $target_dir . $new_filename;
    
    $duration_seconds = $_POST['duration_seconds'] ?? 10;
    $start_date_str = empty($_POST['start_date']) ? null : $_POST['start_date'];
    $end_date_str = empty($_POST['end_date']) ? null : $_POST['end_date'];

    $uploadOk = 1;
    
    // ตรวจสอบขนาดไฟล์
    if ($_FILES["content_file"]["size"] > 50000000) {
        $message = '<div class="alert alert-danger">ขนาดไฟล์ใหญ่เกินไป (สูงสุด 50MB)</div>';
        $uploadOk = 0;
    }

    // ตรวจสอบนามสกุลไฟล์
    if($file_extension != "jpg" && $file_extension != "png" && $file_extension != "jpeg"
    && $file_extension != "gif" && $file_extension != "mp4" && $file_extension != "webm" && $file_extension != "ogg") {
        $message = '<div class="alert alert-danger">อนุญาตเฉพาะไฟล์ JPG, JPEG, PNG, GIF, MP4, WEBM, OGG เท่านั้น</div>';
        $uploadOk = 0;
    }
    
    if ($uploadOk == 0) {
        // อัพโหลดไม่สำเร็จ
    } else {
        if (move_uploaded_file($_FILES["content_file"]["tmp_name"], $target_file)) {
            $content_type = in_array($file_extension, ['mp4', 'webm', 'ogg']) ? 'video' : 'image';
            
            // 1. บันทึก Content
            $sql = "INSERT INTO contents (filename, filepath, content_type, duration_seconds, upload_by, start_date, end_date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssiiss", $original_filename, $new_filename, $content_type, $duration_seconds, $user_id, $start_date_str, $end_date_str);
            
            if ($stmt->execute()) {
                $content_id = $conn->insert_id;
                $stmt->close();
                
                // 2. ผูก Content กับอุปกรณ์ทั้งหมดที่ User มีสิทธิ์
                $conn->begin_transaction();
                try {
                    $sql_dc = "INSERT INTO device_content (device_id, content_id) VALUES (?, ?)";
                    $stmt_dc = $conn->prepare($sql_dc);
                    
                    foreach ($allowed_device_ids as $device_id) {
                        $stmt_dc->bind_param("ii", $device_id, $content_id);
                        $stmt_dc->execute();
                    }
                    $stmt_dc->close();
                    $conn->commit();
                    
                    $_SESSION['message'] = ['type' => 'success', 'text' => 'อัพโหลด Content เรียบร้อยแล้ว'];
                    header("Location: my_content.php");
                    exit();
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    if (file_exists($target_file)) { unlink($target_file); }
                    $message = '<div class="alert alert-danger">เกิดข้อผิดพลาดในการบันทึก Playlist: ' . $e->getMessage() . '</div>';
                }
                
            } else {
                if (file_exists($target_file)) { unlink($target_file); }
                $message = '<div class="alert alert-danger">บันทึก Content ไม่สำเร็จ: ' . $stmt->error . '</div>';
            }
        } else {
            $message = '<div class="alert alert-danger">มีข้อผิดพลาดในการอัพโหลดไฟล์</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Signage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/user-panel.css">
</head>

<body>
    <button class="sidebar-toggle">
        <i class="bi bi-list"></i>
    </button>

    <div class="sidebar">
        <div class="sidebar-header">
        <h5 class="text-center mb-2">📺Digital signage ycap</h5>
        </div>
        <hr class="sidebar-divider">
        <div class="user-profile">
            <div class="profile-initial"><?php echo $logged_in_user['profile_initial']; ?></div>
            <p class="profile-name"><?php echo $logged_in_user['fullname']; ?></p>
            <p class="profile-position"><?php echo $logged_in_user['position']; ?></p>
        </div>
        <hr class="sidebar-divider">
        <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
            <i class="bi bi-house-door"></i> Dashboard
        </a>
        <a href="my_content.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'my_content.php' ? 'active' : ''; ?>">
            <i class="bi bi-film"></i> Content ของฉัน
        </a>
        <a href="upload.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'upload.php' ? 'active' : ''; ?>">
            <i class="bi bi-cloud-arrow-up"></i> อัพโหลด Content
        </a>
        <a href="device_status.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'device_status.php' ? 'active' : ''; ?>">
            <i class="bi bi-display"></i> สถานะอุปกรณ์
        </a>
        <a href="../logout.php" class="text-danger">
            <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
        </a>
    </div>

    <div class="content-area">
            <h1 class="mb-4 text-primary"><i class="bi bi-film"></i> Content ของฉัน</h1>
            <?php echo $message; ?>
            <div class="mb-4">
                <a href="upload.php" class="btn btn-success"><i class="bi bi-plus-circle"></i> อัพโหลด Content ใหม่</a>
            </div>

            <div class="card shadow">
                <div class="card-header card-header-custom">
                    อัพโหลดไฟล์วิดีโอหรือภาพ
                </div>
                <div class="card-body">
                    <form action="upload.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="content_file" class="form-label fw-bold">เลือกไฟล์วิดีโอ/ภาพ</label>
                            <input type="file" class="form-control" id="content_file" name="content_file" accept=".mp4,.webm,.ogg,.jpg,.jpeg,.png,.gif" required>
                            <small class="form-text text-muted">รองรับไฟล์: MP4, WEBM, OGG, JPG, JPEG, PNG, GIF (สูงสุด 50MB)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="duration_seconds" class="form-label fw-bold">ระยะเวลาแสดงผล (วินาที)</label>
                            <input type="number" class="form-control" id="duration_seconds" name="duration_seconds" value="10" min="1" required>
                            <small class="form-text text-muted">ใช้สำหรับภาพนิ่ง และกำหนดการข้ามของวิดีโอถ้าไม่มีการเล่นซ้ำ</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label">วันที่และเวลาเริ่มแสดงผล</label>
                                <input type="datetime-local" class="form-control" id="start_date" name="start_date">
                                <small class="form-text text-muted">ปล่อยว่าง = แสดงทันที</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label">วันที่และเวลาสิ้นสุดการแสดงผล</label>
                                <input type="datetime-local" class="form-control" id="end_date" name="end_date">
                                <small class="form-text text-muted">ปล่อยว่าง = แสดงตลอดไป</small>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-upload"></i> อัพโหลด Content</button>
                        <a href="my_content.php" class="btn btn-secondary mt-3">กลับหน้า Content</a>
                    </form>
                </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/sidebar-menu.js"></script>
    <div class="footer-content-area">
                <h6>&copy; จัดทำโดย นายฐิติพงศ์ ภาสวร โครงการทดลองจ้างงานบุคคลออทิสติก รุ่นที่13</h6>
            </div>
</body>
</html>
<?php $conn->close(); ?>