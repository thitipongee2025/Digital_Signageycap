<?php
// admin/add_content.php - อัพโหลด Content ใหม่ (รวม Header/Footer)
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
    
    // แก้ไข SQL: ตัด LEFT JOIN กับ user_roles ออก เพื่อแก้ไข Fatal Error ชั่วคราว
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
        
        // กำหนดตำแหน่งเป็นค่าเริ่มต้น "ผู้ดูแลระบบ" 
        $logged_in_user['position'] = htmlspecialchars($user_data['position']);
        
        // สร้างอักษรย่อสำหรับแสดงผลในวงกลม
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


$devices_result = $conn->query("SELECT * FROM devices ORDER BY device_name");
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['content_file'])) {
    // กำหนด Path ปลายทาง
    $target_dir = "../assets/uploads/";
    $original_filename = basename($_FILES["content_file"]["name"]);
    $file_extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
    $new_filename = time() . '_' . $original_filename;
    $target_file = $target_dir . $new_filename;
    
    // 1. รับค่าระยะเวลาเล่น (Duration)
    $duration_seconds = $_POST['duration_seconds'] ?? 10;
    
    // การรับค่าวันที่และเวลาที่แยกกัน
    $start_date_only = $_POST['start_date_only'] ?? '';
    $start_time_only = $_POST['start_time_only'] ?? '00:00';
    $end_date_only = $_POST['end_date_only'] ?? '';
    $end_time_only = $_POST['end_time_only'] ?? '00:00';

    // รวมวันที่และเวลาเข้าด้วยกัน
    $start_date_str = empty($start_date_only) ? null : $start_date_only . ' ' . $start_time_only;
    $end_date_str = empty($end_date_only) ? null : $end_date_only . ' ' . $end_time_only;
    
    $selected_devices = isset($_POST['devices']) ? $_POST['devices'] : [];

    // ตรวจสอบประเภทไฟล์
    $allowed_video = ['mp4', 'webm', 'ogg'];
    $allowed_image = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($file_extension, $allowed_video)) {
        $content_type = 'video';
        // ตั้งค่า duration เป็น 0 สำหรับวิดีโอเพื่อเล่นตามความยาวไฟล์
        $duration_seconds = 0; 
    } elseif (in_array($file_extension, $allowed_image)) {
        $content_type = 'image';
    } else {
        $message = '<div class="alert alert-danger"><i class="bi bi-x-circle-fill"></i> ไม่รองรับประเภทไฟล์นี้ อนุญาตเฉพาะ MP4, WebM, OGG, JPG, JPEG, PNG, GIF เท่านั้น</div>';
    }

    if (!isset($content_type)) goto end_upload; 

    // ตรวจสอบและสร้างโฟลเดอร์สำหรับอัพโหลด หากยังไม่มี
    if (!is_dir($target_dir)) {
        if (!mkdir($target_dir, 0777, true)) { 
            $message = '<div class="alert alert-danger"><i class="bi bi-x-circle-fill"></i> Error: ไม่สามารถสร้างโฟลเดอร์อัพโหลดได้ โปรดตรวจสอบสิทธิ์ในการเขียน (Write Permission) ของโฟลเดอร์ **assets/**.</div>';
            goto end_upload;
        }
    }
    
    // ตรวจสอบข้อผิดพลาดในการอัพโหลด
    if ($_FILES["content_file"]["error"] !== UPLOAD_ERR_OK) {
        $error_code = $_FILES["content_file"]["error"];
        $message = '<div class="alert alert-danger"><i class="bi bi-x-circle-fill"></i> เกิดข้อผิดพลาดในการอัพโหลดไฟล์ (Code: ' . $error_code . ')</div>';
        goto end_upload;
    }

    if (move_uploaded_file($_FILES["content_file"]["tmp_name"], $target_file)) {
        
        // 2. INSERT INTO contents
        $sql = "INSERT INTO contents (filename, filepath, content_type, upload_by, duration_seconds, start_date, end_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        $start_date_db = $start_date_str ? date('Y-m-d H:i:s', strtotime($start_date_str)) : null;
        $end_date_db = $end_date_str ? date('Y-m-d H:i:s', strtotime($end_date_str)) : null;
        
        $stmt->bind_param("sssiiss", $original_filename, $new_filename, $content_type, $_SESSION['user_id'], $duration_seconds, $start_date_db, $end_date_db);
        
        if ($stmt->execute()) {
            $content_id = $conn->insert_id;
            
            // 4. บันทึก Content เข้า Device/Playlist (device_content)
            
            // --- [ Logic: จัดการการเลือก "เล่นบนทุกอุปกรณ์" ] ---
            $devices_to_insert = [];
            
            if (in_array('all_devices', $selected_devices)) {
                // หากเลือก "เล่นบนทุกอุปกรณ์" ให้ดึง ID ของอุปกรณ์ทั้งหมด
                $all_devices_result = $conn->query("SELECT device_id FROM devices");
                while ($dev = $all_devices_result->fetch_assoc()) {
                    $devices_to_insert[] = $dev['device_id'];
                }
            } else {
                // หากเลือกเฉพาะอุปกรณ์
                $devices_to_insert = $selected_devices;
            }

            if (!empty($devices_to_insert)) {
                // คำสั่ง SQL ที่จะหา MAX(display_order) + 1 ของแต่ละ Device เพื่อให้ Content ไปอยู่ท้าย Playlist
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
            // --- [ สิ้นสุด Logic: จัดการการเลือก "เล่นบนทุกอุปกรณ์" ] ---

            $message = '<div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> อัพโหลด Content **' . htmlspecialchars($original_filename) . '** และกำหนด Playlist เรียบร้อยแล้ว!</div>';
        } else {
            $message = '<div class="alert alert-danger"><i class="bi bi-x-circle-fill"></i> Error: ' . $stmt->error . '</div>';
        }
        $stmt->close();
    } else {
        $message = '<div class="alert alert-danger"><i class="bi bi-x-circle-fill"></i> เกิดข้อผิดพลาดในการอัพโหลดไฟล์ (อาจเกิดจากสิทธิ์ในการเขียนหรือไฟล์มีขนาดใหญ่เกินกำหนด)</div>';
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
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f4f7f6;
        }
        .sidebar {
            position: fixed;
            height: 100vh;
            width: 250px;
            background-image: linear-gradient( 0deg , #006622ff, #00998cff );
            color: white;
            padding-top: 20px;
        }
        .sidebar a {
            color: #ffffffff;
            padding: 10px 15px;
            text-decoration: none;
            display: block;
            transition: all 0.3s;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: #009999ff;
            color: white;
            border-left: 4px solid #1abc9c;
        }
        .content-area {
            margin-left: 250px;
            width: calc(100% - 250px);
            min-height: 100vh;
            padding: 40px;
        }
        .card-header-custom {
            background-color: #1abc9c;
            color: white;
            font-weight: 600;
            padding: 1rem;
            border-top-left-radius: 0.375rem;
            border-top-right-radius: 0.375rem;
            display: flex;
            align-items: center;
        }
        .form-select[multiple] {
            min-height: 200px;
        }

        /* --- [CSS ที่แก้ไขสำหรับ Profile] --- */
        .user-profile {
            padding: 15px 10px; /* ลด padding แนวข้าง */
            text-align: center;
            margin-bottom: 5px; 
            background-image: linear-gradient(0deg , #060041ff, #685abdff);
            margin: 0 10px; 
            border-radius: 8px; 
            border: 1px solid #3c546c; /* เพิ่มเส้นขอบบางๆ ให้ดูเป็นกรอบ */
        }
        .profile-initial {
            width: 60px; 
            height: 60px;
            background-color: #1abc9c; /* สีเขียวเด่น */
            color: white;
            border-radius: 50%;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            font-size: 1.8rem; 
            font-weight: 700;
            margin-bottom: 8px; /* ลด margin-bottom */
            border: 3px solid #f4f7f6; 
            box-shadow: 0 0 0 2px #1abc9c; /* เงารอบวงกลม */
        }
        .profile-name {
            font-weight: 700;
            margin: 0;
            font-size: 1.1rem;
            color: #ecf0f1; 
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis; 
        }
        .profile-position {
            font-size: 0.85rem;
            color: #ffffffff;
            margin-top: 2px;
        }
        /* --- [สิ้นสุด CSS ที่แก้ไข] --- */
        
        /* สไตล์สำหรับเส้นแบ่ง */
        .sidebar-divider {
            border: 0;
            height: 1px;
            background-color: #ebfddcff; 
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <div class="sidebar">
            <h4 class="text-center mb-2">📺 Admin Panel</h4>
            <hr class="sidebar-divider">
     <div class="user-profile">
                <div class="profile-initial"><?php echo $logged_in_user['profile_initial']; ?></div>
                <p class="profile-name" title="<?php echo $logged_in_user['fullname']; ?>"><?php echo $logged_in_user['fullname']; ?></p>
                <p class="profile-position"><?php echo $logged_in_user['position']; ?></p>
            </div>
            <hr class="sidebar-divider">
            
            <ul class="nav flex-column">
               <li class="nav-item"><a class="nav-link" href="index.php">📊 Dashboard</a></li>
                <li class="nav-item"><a class="nav-link active" href="contents.php">📂 จัดการ Content</a></li>
                <li class="nav-item"><a class="nav-link" href="devices.php">💻 จัดการอุปกรณ์</a></li>
                <li class="nav-item"><a class="nav-link" href="users.php">👥 จัดการสมาชิก</a></li>
                <li class="nav-item"><a class="nav-link" href="user_roles.php">🔑 จัดการสิทธิ์</a></li>
                <li class="nav-item"><a class="nav-link" href="../logout.php">🚪 ออกจากระบบ</a></li>
            </ul>
        </div>

        <div class="content-area">
        <h1 class="mb-4 text-primary"><i class="bi bi-plus-circle-fill"></i> อัพโหลด Content ใหม่</h1>
        <?php echo $message; ?>
        <div class="card shadow border-0">
            <div class="card-header-custom">
                <i class="bi bi-cloud-upload-fill me-2"></i> ข้อมูล Content
            </div>
            <div class="card-body">
                <form action="add_content.php" method="POST" enctype="multipart/form-data">

                    <div class="mb-4">
                        <label for="content_file" class="form-label fw-bold"><i class="bi bi-file-earmark-arrow-up"></i> เลือกไฟล์วิดีโอ/ภาพ <span class="text-muted">(MP4, WebM, OGG, JPG, PNG, GIF)</span> <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="content_file" name="content_file" accept=".mp4,.webm,.ogg,.jpg,.jpeg,.png,.gif" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="duration_seconds" class="form-label fw-bold"><i class="bi bi-clock"></i> กำหนดเวลาเล่น (วินาที) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="duration_seconds" name="duration_seconds" min="0" value="10" required>
                        <small class="form-text text-muted"><i class="bi bi-info-circle"></i> กำหนดระยะเวลาในการแสดง Content นี้ (แนะนำ **10 วินาที** สำหรับภาพนิ่ง, **0** สำหรับวิดีโอ)</small>
                    </div>

                    <h5 class="mt-4 mb-3 border-bottom pb-2 text-info"><i class="bi bi-clock-history"></i> กำหนดช่วงเวลาแสดงผล (Optional)</h5>
                    
                    <div class="row g-3">
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">วันที่และเวลาเริ่มแสดงผล</label>
                            <div class="row g-2">
                                <div class="col-md-7">
                                    <label for="start_date_only" class="form-label text-muted small">วันที่เริ่มแสดงผล</label>
                                    <input type="date" class="form-control" id="start_date_only" name="start_date_only">
                                </div>
                                <div class="col-md-5">
                                    <label for="start_time_only" class="form-label text-muted small">เวลาเริ่มแสดงผล</label>
                                    <input type="time" class="form-control" id="start_time_only" name="start_time_only" value="00:00">
                                </div>
                            </div>
                            <small class="form-text text-muted d-block mt-1">(ปล่อยว่าง = แสดงทันที)</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">วันที่และเวลาสิ้นสุดการแสดงผล</label>
                            <div class="row g-2">
                                <div class="col-md-7">
                                    <label for="end_date_only" class="form-label text-muted small">วันที่สิ้นสุดการแสดงผล</label>
                                    <input type="date" class="form-control" id="end_date_only" name="end_date_only">
                                </div>
                                <div class="col-md-5">
                                    <label for="end_time_only" class="form-label text-muted small">เวลาสิ้นสุดการแสดงผล</label>
                                    <input type="time" class="form-control" id="end_time_only" name="end_time_only" value="00:00">
                                </div>
                            </div>
                            <small class="form-text text-muted d-block mt-1">(ปล่อยว่าง = แสดงตลอดไป)</small>
                        </div>
                    </div>

                    <h5 class="mt-5 mb-3 border-bottom pb-2 text-info"><i class="bi bi-list-task"></i> เลือก Playlist / อุปกรณ์เป้าหมาย</h5>
                    
                    <div class="mb-4">
                        <label for="devices" class="form-label fw-bold">อุปกรณ์เป้าหมาย <span class="text-danger">*</span></label>
                        <select multiple class="form-select" id="devices" name="devices[]" size="8" required>
                            <option value="" disabled>--- เลือกอย่างน้อย 1 อุปกรณ์ ---</option>
                            <option value="all_devices" class="fw-bold text-primary" selected>-- เล่นบนทุกอุปกรณ์ (เริ่มต้น)</option>
                            <?php 
                            if ($devices_result->num_rows > 0) {
                                $devices_result->data_seek(0);
                            }
                            while($device = $devices_result->fetch_assoc()) {
                                echo '<option value="' . $device['device_id'] . '">&#128205; ' . htmlspecialchars($device['device_name']) . ' (' . htmlspecialchars($device['location']) . ')</option>';
                            }
                            ?>
                        </select>
                        <small class="form-text text-muted"><i class="bi bi-info-circle"></i> กดปุ่ม **Ctrl** หรือ **Cmd** เพื่อเลือกหลายอุปกรณ์</small>
                    </div>

                    <div class="d-flex justify-content-end pt-3 border-top">
                        <button type="submit" class="btn btn-primary btn-lg me-2"><i class="bi bi-upload"></i> อัพโหลดและบันทึก</button>
                        <a href="contents.php" class="btn btn-secondary btn-lg"><i class="bi bi-x-circle"></i> ยกเลิก</a>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JavaScript สำหรับจัดการการเลือก "เล่นบนทุกอุปกรณ์"
        document.getElementById('devices').addEventListener('change', function() {
            const allDevicesOption = this.querySelector('option[value="all_devices"]');
            
            // ตรวจสอบว่ามีการเลือกตัวเลือกอื่นที่ไม่ใช่ 'all_devices' หรือไม่
            const otherSelected = Array.from(this.options).some(option => 
                option.selected && option.value !== 'all_devices' && option.value !== ''
            );
            
            // ถ้ามีการเลือก 'all_devices' พร้อมกับตัวเลือกอื่น ให้ยกเลิกการเลือก 'all_devices'
            if (allDevicesOption.selected && otherSelected) {
                allDevicesOption.selected = false;
            } 
            
            // ถ้าไม่มีการเลือกตัวเลือกใดเลย ให้เลือก 'all_devices' เป็นค่าเริ่มต้นอีกครั้ง (ป้องกันฟอร์มว่าง)
            const nothingSelected = Array.from(this.options).every(option => !option.selected || option.value === '');
            if (nothingSelected) {
                 allDevicesOption.selected = true;
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>