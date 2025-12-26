<?php
include '../config.php';

// 1. ตรวจสอบ Login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$content_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$content = null; 

// 2. ดึงข้อมูล User สำหรับ Sidebar
$logged_in_user = ['fullname' => 'User', 'position' => 'Staff', 'profile_initial' => 'U'];
$u_sql = "SELECT fullname, position FROM users WHERE user_id = ?";
$u_stmt = $conn->prepare($u_sql);
$u_stmt->bind_param("i", $user_id);
$u_stmt->execute();
$u_res = $u_stmt->get_result();
if ($u_data = $u_res->fetch_assoc()) {
    $logged_in_user['fullname'] = htmlspecialchars($u_data['fullname']);
    $logged_in_user['position'] = htmlspecialchars($u_data['position'] ?? 'Staff');
    $logged_in_user['profile_initial'] = mb_substr($logged_in_user['fullname'], 0, 1, 'UTF-8');
}

// 3. ตรวจสอบว่ามี ID ที่ส่งมาหรือไม่
if ($content_id > 0) {
    // ดึงข้อมูล Content และตรวจสอบสิทธิ์เจ้าของ (ใช้ upload_by แทน user_id)
    $sql = "SELECT * FROM contents WHERE content_id = ? AND upload_by = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $content_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $content = $result->fetch_assoc();
}

// 4. ถ้าไม่พบข้อมูล ให้เด้งกลับหน้าเดิม
if (!$content) {
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'ไม่พบข้อมูล Content ที่ต้องการแก้ไข หรือคุณไม่มีสิทธิ์'];
    header("Location: my_content.php");
    exit();
}

// 5. ดึงรายการอุปกรณ์ที่ได้รับสิทธิ์
$devices_sql = "SELECT d.device_id, d.device_name, d.location FROM devices d 
                JOIN user_permissions up ON d.device_id = up.device_id 
                WHERE up.user_id = ? ORDER BY d.device_name";
$devices_stmt = $conn->prepare($devices_sql);
$devices_stmt->bind_param("i", $user_id);
$devices_stmt->execute();
$devices_result = $devices_stmt->get_result();

// 6. จัดการการบันทึกข้อมูล (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filename = isset($_POST['filename']) ? $_POST['filename'] : '';
    $selected_device = isset($_POST['device_id']) ? $_POST['device_id'] : '';
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;

    // ตรวจสอบว่าเลือก device_id หรือไม่
    if (!empty($selected_device)) {
        // Update ข้อมูลพื้นฐานของ content (ไม่รวม device_id)
        $update_sql = "UPDATE contents SET filename = ?, start_date = ?, end_date = ? WHERE content_id = ? AND upload_by = ?";
        $update_stmt = $conn->prepare($update_sql);
        
        if ($update_stmt) {
            $update_stmt->bind_param("sssii", $filename, $start_date, $end_date, $content_id, $user_id);
            
            if ($update_stmt->execute()) {
                // ลบ device_content เดิมทั้งหมด
                $conn->query("DELETE FROM device_content WHERE content_id = $content_id");
                
                // เพิ่ม device_content ใหม่
                if ($selected_device == 'all_devices') {
                    // ถ้าเลือก "ทุกอุปกรณ์" ให้ insert ทุกอุปกรณ์ที่ user มีสิทธิ์
                    $devices_stmt->execute();
                    $all_devices_result = $devices_stmt->get_result();
                    
                    $insert_dc_sql = "INSERT INTO device_content (device_id, content_id, display_order) 
                                      SELECT ?, ?, COALESCE(MAX(display_order), 0) + 1 FROM device_content WHERE device_id = ?";
                    $stmt_dc = $conn->prepare($insert_dc_sql);
                    
                    while($device_row = $all_devices_result->fetch_assoc()) {
                        $dev_id = $device_row['device_id'];
                        $stmt_dc->bind_param("iii", $dev_id, $content_id, $dev_id);
                        $stmt_dc->execute();
                    }
                    $stmt_dc->close();
                } else {
                    // เลือกอุปกรณ์เฉพาะ
                    $device_id_int = (int)$selected_device;
                    $insert_dc_sql = "INSERT INTO device_content (device_id, content_id, display_order) 
                                      SELECT ?, ?, COALESCE(MAX(display_order), 0) + 1 FROM device_content WHERE device_id = ?";
                    $stmt_dc = $conn->prepare($insert_dc_sql);
                    $stmt_dc->bind_param("iii", $device_id_int, $content_id, $device_id_int);
                    $stmt_dc->execute();
                    $stmt_dc->close();
                }
                
                $_SESSION['message'] = ['type' => 'success', 'text' => 'บันทึกการแก้ไขเรียบร้อยแล้ว'];
                header("Location: my_content.php");
                exit();
            } else {
                $message = '<div class="alert alert-danger">เกิดข้อผิดพลาด: ' . $conn->error . '</div>';
            }
        }
    } else {
        $message = '<div class="alert alert-warning">กรุณาเลือกอุปกรณ์</div>';
    }
}

// ดึงอุปกรณ์ที่ content นี้กำลังแสดงอยู่
$current_devices = [];
$perm_result = $conn->query("SELECT device_id FROM device_content WHERE content_id = $content_id");
while($row = $perm_result->fetch_assoc()) {
    $current_devices[] = $row['device_id'];
}

// กำหนดค่า selected device (ถ้ามีหลายอุปกรณ์ถือว่าเลือก "ทุกอุปกรณ์")
$selected_device_id = '';
if (count($current_devices) > 1) {
    $selected_device_id = 'all_devices';
} elseif (count($current_devices) == 1) {
    $selected_device_id = $current_devices[0];
}

// จัดรูปแบบวันที่สำหรับ HTML Input
$start_date_val = !empty($content['start_date']) ? date('Y-m-d\TH:i', strtotime($content['start_date'])) : '';
$end_date_val = !empty($content['end_date']) ? date('Y-m-d\TH:i', strtotime($content['end_date'])) : '';

// ใช้คอลัมน์ที่มีจริง (filepath)
$file_path = $content['filepath'] ?? '';
$file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไข Content - Digital Signage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
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
        <div class="user-profile">
            <div class="profile-initial"><?php echo $logged_in_user['profile_initial']; ?></div>
            <p class="profile-name"><?php echo $logged_in_user['fullname']; ?></p>
            <p class="profile-position"><?php echo $logged_in_user['position']; ?></p>
        </div>
        <hr class="sidebar-divider">
        <a href="index.php"><i class="bi bi-house-door"></i> Dashboard</a>
        <a href="my_content.php" class="active"><i class="bi bi-film"></i> Content ของฉัน</a>
        <a href="upload.php"><i class="bi bi-cloud-arrow-up"></i> อัพโหลด Content</a>
        <a href="device_status.php"><i class="bi bi-display"></i> สถานะอุปกรณ์</a>
        <a href="../logout.php" class="text-danger"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a>
    </div>

    <div class="content-area">
        <div class="container-fluid">
            <div class="d-flex align-items-center mb-4">
                <a href="my_content.php" class="btn btn-outline-secondary me-3 btn-sm">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h2 class="mb-0 text-primary"><i class="bi bi-pencil-square"></i> แก้ไขรายละเอียด Content</h2>
            </div>

            <?php echo $message; ?>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="mb-4 text-center bg-light p-3 rounded">
                        <p class="text-muted small mb-2">ไฟล์ปัจจุบัน:</p>
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

                    <form action="" method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="filename" class="form-label">ชื่อไฟล์/หัวข้อ</label>
                                <input type="text" class="form-control" id="filename" name="filename" 
                                       value="<?php echo htmlspecialchars($content['filename'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="device_id" class="form-label">แสดงผลที่อุปกรณ์</label>
                                <select class="form-select" id="device_id" name="device_id" required>
                                    <option value="">-- เลือกอุปกรณ์ --</option>
                                    <option value="all_devices" <?php echo ($selected_device_id == 'all_devices') ? 'selected' : ''; ?>>
                                        🌐 ทุกอุปกรณ์ที่มีสิทธิ์
                                    </option>
                                    <?php 
                                    // ต้อง reset pointer ของ result ใหม่
                                    $devices_stmt->execute();
                                    $devices_result = $devices_stmt->get_result();
                                    while($dev = $devices_result->fetch_assoc()): 
                                    ?>
                                        <option value="<?php echo $dev['device_id']; ?>" 
                                            <?php echo ($dev['device_id'] == $selected_device_id) ? 'selected' : ''; ?>>
                                            📍 <?php echo htmlspecialchars($dev['device_name']); ?>
                                            <?php if (!empty($dev['location'])): ?>
                                                (<?php echo htmlspecialchars($dev['location']); ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <small class="form-text text-muted">
                                    <i class="bi bi-info-circle"></i> เลือก "ทุกอุปกรณ์" เพื่อแสดงในทุกหน้าจอที่คุณมีสิทธิ์
                                </small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label">วันที่เริ่มแสดงผล</label>
                                <input type="datetime-local" class="form-control" id="start_date" name="start_date" 
                                       value="<?php echo $start_date_val; ?>">
                                <div class="form-text">ปล่อยว่างเพื่อแสดงทันที</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label">วันที่สิ้นสุดการแสดง</label>
                                <input type="datetime-local" class="form-control" id="end_date" name="end_date" 
                                       value="<?php echo $end_date_val; ?>">
                                <div class="form-text">ปล่อยว่างเพื่อแสดงตลอดไป</div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2 action-buttons">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-save"></i> บันทึกการแก้ไข
                            </button>
                            <a href="my_content.php" class="btn btn-light px-4">ยกเลิก</a>
                        </div>
                    </form>
                </div>
            
        </div>
        </div>
        </div>
       <div class="footer-content-area">
                <h6>&copy; จัดทำโดย นายฐิติพงศ์ ภาสวร โครงการทดลองจ้างงานบุคคลออทิสติก รุ่นที่13</h6>
            </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/sidebar-menu.js"></script>
</body>
</html>
<?php $conn->close(); ?>