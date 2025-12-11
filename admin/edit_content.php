<?php
// admin/edit_content.php - แก้ไข Content (รวม Header/Footer)
include '../config.php';
// ตรวจสอบสถานะการเข้าสู่ระบบ Admin
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

$content_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';

if ($content_id === 0) {
    // Redirect กลับไปหน้าหลักหากไม่มี ID
    header("Location: contents.php");
    exit();
}

// --- 1. Process Form Submission (UPDATE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_content'])) {
    
    // รับค่าจากฟอร์ม
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
    
    // อัปเดตข้อมูลหลัก (contents)
    $sql = "UPDATE contents SET duration_seconds = ?, start_date = ?, end_date = ? WHERE content_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issi", $duration_seconds, $start_date_db, $end_date_db, $content_id);
    
    if ($stmt->execute()) {
        
        // อัปเดต Playlist (device_content)
        // 1. ลบสิทธิ์เก่าทั้งหมดของ Content นี้ (เพื่อให้ Playlist ของ Device อื่นๆ ไม่ได้รับผลกระทบ)
        $conn->query("DELETE FROM device_content WHERE content_id = $content_id");

        // 2. เพิ่ม Content นี้กลับเข้าไปใน Device ใหม่ตามที่เลือก
        if (!empty($selected_devices)) {
            $insert_dc_sql = "INSERT INTO device_content (device_id, content_id, display_order) 
                              SELECT ?, ?, MAX(display_order) + 1 FROM device_content WHERE device_id = ?";
            $stmt_dc = $conn->prepare($insert_dc_sql);

            foreach ($selected_devices as $device_id) {
                if ($device_id != 'all_devices') { // ไม่ต้องสนใจ 'all_devices' ที่เป็นแค่ตัวเลือก
                    // ใช้วิธีหา order ล่าสุดใน Device นั้น ๆ
                    // Note: SQL query ด้านบนถูกปรับเพื่อให้ง่ายต่อการเขียน แต่ควรใช้ Transaction หากต้องการความแม่นยำสูง
                    $stmt_dc->bind_param("iii", $device_id, $content_id, $device_id);
                    $stmt_dc->execute();
                }
            }
            $stmt_dc->close();
        }

        $message = '<div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> แก้ไข Content ID ' . $content_id . ' และอัปเดต Playlist เรียบร้อยแล้ว!</div>';
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

// Helper: แยกวันที่และเวลา
$start_date_db_raw = $content['start_date'] ? strtotime($content['start_date']) : null;
$end_date_db_raw = $content['end_date'] ? strtotime($content['end_date']) : null;
$current_start_date = $start_date_db_raw ? date('Y-m-d', $start_date_db_raw) : '';
$current_start_time = $start_date_db_raw ? date('H:i', $start_date_db_raw) : '00:00';
$current_end_date = $end_date_db_raw ? date('Y-m-d', $end_date_db_raw) : '';
$current_end_time = $end_date_db_raw ? date('H:i', $end_date_db_raw) : '00:00';

// ใช้เวลาเล่น 10 วินาทีเป็นค่าเริ่มต้นสำหรับภาพ หากไม่มีค่า
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
        .content-preview {
            max-width: 100%;
            height: auto;
            max-height: 300px;
            display: block;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
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
        <h1 class="mb-4 text-primary"><i class="bi bi-pencil-square"></i> แก้ไข Content (ID: <?php echo $content_id; ?>)</h1>
        <?php echo $message; ?>
        
        <?php if ($content): ?>
        <div class="card shadow border-0">
            <div class="card-header-custom">
                <i class="bi bi-file-earmark-code me-2"></i> กำลังแก้ไขไฟล์: **<?php echo htmlspecialchars($content['filename']); ?>** (ชนิด: <?php echo strtoupper($content['content_type']); ?>)
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
                        <label for="duration_seconds" class="form-label fw-bold"><i class="bi bi-clock"></i> กำหนดเวลาเล่น (วินาที) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="duration_seconds" name="duration_seconds" min="0" value="<?php echo $current_duration; ?>" required>
                        <small class="form-text text-muted"><i class="bi bi-info-circle"></i> กำหนดระยะเวลาในการแสดง Content นี้ (สำหรับวิดีโอ แนะนำ **0** เพื่อเล่นตามความยาวจริง)</small>
                    </div>

                    <h5 class="mt-4 mb-3 border-bottom pb-2 text-info"><i class="bi bi-clock-history"></i> แก้ไขช่วงเวลาแสดงผล (Optional)</h5>
                    
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">วันที่และเวลาเริ่มแสดงผล</label>
                            <div class="row g-2">
                                <div class="col-md-7">
                                    <label for="start_date_only" class="form-label text-muted small">วันที่เริ่มแสดงผล</label>
                                    <input type="date" class="form-control" id="start_date_only" name="start_date_only" value="<?php echo $current_start_date; ?>">
                                </div>
                                <div class="col-md-5">
                                    <label for="start_time_only" class="form-label text-muted small">เวลาเริ่มแสดงผล</label>
                                    <input type="time" class="form-control" id="start_time_only" name="start_time_only" value="<?php echo $current_start_time; ?>">
                                </div>
                            </div>
                            <small class="form-text text-muted d-block mt-1">(ปล่อยว่าง = แสดงทันที)</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">วันที่และเวลาสิ้นสุดการแสดงผล</label>
                            <div class="row g-2">
                                <div class="col-md-7">
                                    <label for="end_date_only" class="form-label text-muted small">วันที่สิ้นสุดการแสดงผล</label>
                                    <input type="date" class="form-control" id="end_date_only" name="end_date_only" value="<?php echo $current_end_date; ?>">
                                </div>
                                <div class="col-md-5">
                                    <label for="end_time_only" class="form-label text-muted small">เวลาสิ้นสุดการแสดงผล</label>
                                    <input type="time" class="form-control" id="end_time_only" name="end_time_only" value="<?php echo $current_end_time; ?>">
                                </div>
                            </div>
                            <small class="form-text text-muted d-block mt-1">(ปล่อยว่าง = แสดงตลอดไป)</small>
                        </div>
                    </div>

                    <h5 class="mt-5 mb-3 border-bottom pb-2 text-info"><i class="bi bi-list-task"></i> แก้ไข Playlist / อุปกรณ์เป้าหมาย</h5>
                    
                    <div class="mb-4">
                        <label for="devices" class="form-label fw-bold">อุปกรณ์เป้าหมาย <span class="text-danger">*</span></label>
                        <select multiple class="form-select" id="devices" name="devices[]" size="8" required>
                            <option value="" disabled>--- เลือกอย่างน้อย 1 อุปกรณ์ ---</option>
                            <?php 
                            $devices_result->data_seek(0);
                            while($device = $devices_result->fetch_assoc()) {
                                $selected = in_array($device['device_id'], $current_devices) ? 'selected' : '';
                                echo '<option value="' . $device['device_id'] . '" ' . $selected . '>&#128205; ' . htmlspecialchars($device['device_name']) . ' (' . htmlspecialchars($device['location']) . ')</option>';
                            }
                            ?>
                        </select>
                        <small class="form-text text-muted"><i class="bi bi-info-circle"></i> กดปุ่ม **Ctrl** หรือ **Cmd** เพื่อเลือกหลายอุปกรณ์ (Content จะถูกเพิ่มใน **ท้ายสุด** ของ Playlist ในอุปกรณ์ที่เลือก)</small>
                    </div>

                    <div class="d-flex justify-content-end pt-3 border-top">
                        <button type="submit" name="update_content" class="btn btn-success btn-lg me-2"><i class="bi bi-save-fill"></i> บันทึกการแก้ไข</button>
                        <a href="contents.php" class="btn btn-secondary btn-lg"><i class="bi bi-arrow-left-circle-fill"></i> กลับไปรายการ Content</a>
                    </div>
                </form>
            </div>
        </div>
        <?php else: ?>
            <a href="contents.php" class="btn btn-secondary btn-lg"><i class="bi bi-arrow-left-circle-fill"></i> กลับไปรายการ Content</a>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ไม่มี JavaScript พิเศษสำหรับหน้านี้
    </script>
</body>
</html>
<?php $conn->close(); ?>