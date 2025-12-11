<?php
include '../config.php';
// ตรวจสอบ Login และสิทธิ์ User
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// --- [ส่วนที่ถูกแก้ไข] ดึงข้อมูลผู้ใช้งานที่ล็อกอินจริง ---
$logged_in_user = [
    'fullname' => 'ไม่ระบุชื่อ',
    'position' => 'ไม่ระบุตำแหน่ง',
    'profile_initial' => 'A'
];

if (isset($_SESSION['user_id'])) {
    $user_id_session = $_SESSION['user_id'];
    
    $user_sql = "SELECT fullname FROM users WHERE user_id = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $user_id_session);
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
}
// --- [สิ้นสุดส่วนดึงข้อมูล] ---

// ดึงรายการอุปกรณ์ที่ User นี้มีสิทธิ์เข้าใช้งาน
$devices_sql = "
    SELECT d.device_id, d.device_name 
    FROM devices d
    JOIN user_permissions up ON d.device_id = up.device_id
    WHERE up.user_id = $user_id
    ORDER BY d.device_name
";
$devices_result = $conn->query($devices_sql);
$allowed_device_ids = [];
while($row = $devices_result->fetch_assoc()) {
    $allowed_device_ids[] = $row['device_id'];
}
$devices_result->data_seek(0); // Reset pointer

if (empty($allowed_device_ids)) {
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'คุณยังไม่ได้รับสิทธิ์ให้เข้าถึงอุปกรณ์ใดๆ กรุณาติดต่อ Admin'];
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['content_file'])) {
    // [*** โค้ด Logic การอัพโหลดเดิม ***]
    // ... (โค้ดส่วนนี้ยังคงเดิม ไม่ได้มีการแก้ไข) ...
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
    if ($_FILES["content_file"]["size"] > 50000000) { // 50MB
        $message = '<div class="alert alert-danger">ขนาดไฟล์ใหญ่เกินไป (สูงสุด 50MB)</div>';
        $uploadOk = 0;
    }

    // อนุญาตเฉพาะบางนามสกุล
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
            
            // 1. บันทึก Content ลงตาราง contents
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
                    
                    $_SESSION['message'] = ['type' => 'success', 'text' => 'อัพโหลด Content และกำหนด Playlist เรียบร้อยแล้ว'];
                    header("Location: index.php");
                    exit();
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    // ลบไฟล์ที่อัพโหลดไปแล้ว
                    if (file_exists($target_file)) { unlink($target_file); }
                    $message = '<div class="alert alert-danger">อัพโหลดสำเร็จ แต่เกิดข้อผิดพลาดในการกำหนด Playlist: ' . $e->getMessage() . '</div>';
                }
                
            } else {
                // ลบไฟล์หากบันทึก DB ไม่สำเร็จ
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
    <title>อัพโหลด Content - User Panel</title>
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
            background-color: #343a40;
            color: white;
            padding-top: 20px;
        }
        .sidebar a {
            color: #bdc3c7;
            padding: 10px 15px;
            text-decoration: none;
            display: block;
            transition: all 0.3s;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: #34495e;
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
        }

        /* --- [CSS ที่แก้ไขสำหรับ Profile] --- */
        .user-profile {
            padding: 15px 10px; 
            text-align: center;
            margin-bottom: 5px; 
            background-color: #2c3e50; 
            margin: 0 10px; 
            border-radius: 8px; 
            border: 1px solid #3c546c; 
        }
        .profile-initial {
            width: 60px; 
            height: 60px;
            background-color: #1abc9c; 
            color: white;
            border-radius: 50%;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            font-size: 1.8rem; 
            font-weight: 700;
            margin-bottom: 8px; 
            border: 3px solid #f4f7f6; 
            box-shadow: 0 0 0 2px #1abc9c; 
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
            color: #bdc3c7;
            margin-top: 2px;
            font-style: italic;
        }
        /* --- [สิ้นสุด CSS ที่แก้ไข] --- */
        
        /* สไตล์สำหรับเส้นแบ่ง */
        .sidebar-divider {
            border: 0;
            height: 1px;
            background-color: #495057; 
            margin: 15px 10px 20px 10px; 
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <div class="sidebar">
            <h4 class="text-center mb-2">🧑‍💻 User Panel</h4>
            
            <div class="user-profile">
                <div class="profile-initial"><?php echo $logged_in_user['profile_initial']; ?></div>
                <p class="profile-name" title="<?php echo $logged_in_user['fullname']; ?>"><?php echo $logged_in_user['fullname']; ?></p>
                <p class="profile-position"><?php echo $logged_in_user['position']; ?></p>
            </div>
            <hr class="sidebar-divider">
            
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="index.php">📊 Content ของฉัน</a></li>
                <li class="nav-item"><a class="nav-link active" href="upload.php">⬆️ อัพโหลด Content ใหม่</a></li>
                <li class="nav-item"><a class="nav-link" href="device_status.php">💻 สถานะอุปกรณ์ที่ได้รับสิทธิ์</a></li>
                <li class="nav-item"><a class="nav-link" href="../logout.php">🚪 ออกจากระบบ</a></li>
            </ul>
        </div>
        <div class="content-area">
            <h1 class="mb-4 text-success"><i class="bi bi-cloud-arrow-up"></i> อัพโหลด Content</h1>
            <?php echo $message; ?>

            <div class="card shadow">
                <div class="card-header card-header-custom">
                    อัพโหลดไฟล์ใหม่สำหรับแสดงผล
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
                                <label for="start_date" class="form-label">วันที่และเวลาเริ่มแสดงผล (ปล่อยว่าง = แสดงทันที)</label>
                                <input type="datetime-local" class="form-control" id="start_date" name="start_date">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label">วันที่และเวลาสิ้นสุดการแสดงผล (ปล่อยว่าง = แสดงตลอดไป)</label>
                                <input type="datetime-local" class="form-control" id="end_date" name="end_date">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-upload"></i> อัพโหลด Content</button>
                        <a href="index.php" class="btn btn-secondary mt-3">กลับหน้า Dashboard</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>