<?php
include '../config.php';
// ตรวจสอบ Login และสิทธิ์ User
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$content_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ตรวจสอบว่า content นี้เป็นของ user และอยู่ในอุปกรณ์ที่ user มีสิทธิ์
if ($content_id === 0) {
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'ID Content ไม่ถูกต้อง'];
    header("Location: my_content.php");
    exit();
}

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
    header("Location: my_content.php");
    exit();
}

// ตรวจสอบ Content เป็นของ User และอยู่ในอุปกรณ์ที่มีสิทธิ์
$placeholders = implode(',', array_fill(0, count($allowed_device_ids), '?'));
$check_sql = "
    SELECT c.content_id, c.filename, c.content_type, c.start_date, c.end_date, c.duration_seconds
    FROM contents c
    JOIN device_content dc ON c.content_id = dc.content_id
    WHERE c.content_id = ? AND c.upload_by = ? AND dc.device_id IN ($placeholders)
    LIMIT 1
";
$check_stmt = $conn->prepare($check_sql);
$params = array_merge([$content_id, $user_id], $allowed_device_ids);
$types = str_repeat('i', count($params));
$check_stmt->bind_param($types, ...$params);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$content_data = $check_result->fetch_assoc();
$check_stmt->close();

if (!$content_data) {
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'ไม่พบ Content นี้ หรือคุณไม่มีสิทธิ์'];
    header("Location: my_content.php");
    exit();
}

// ดึงข้อมูล User
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

// หากมี POST request - บันทึกการแก้ไข
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $duration_seconds = isset($_POST['duration_seconds']) ? (int)$_POST['duration_seconds'] : 10;
    $start_date_str = empty($_POST['start_date']) ? null : $_POST['start_date'];
    $end_date_str = empty($_POST['end_date']) ? null : $_POST['end_date'];

    $update_sql = "
        UPDATE contents 
        SET duration_seconds = ?, start_date = ?, end_date = ?
        WHERE content_id = ? AND upload_by = ?
    ";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("isssii", $duration_seconds, $start_date_str, $end_date_str, $content_id, $user_id);
    
    if ($update_stmt->execute()) {
        $update_stmt->close();
        $_SESSION['message'] = ['type' => 'success', 'text' => 'แก้ไข Content เรียบร้อยแล้ว'];
        header("Location: my_content.php");
        exit();
    } else {
        $message = '<div class="alert alert-danger">เกิดข้อผิดพลาดในการแก้ไข: ' . $update_stmt->error . '</div>';
        $update_stmt->close();
    }
}

// เตรียมข้อมูลสำหรับแสดง
$start_date_display = $content_data['start_date'] ? substr($content_data['start_date'], 0, 16) : '';
$end_date_display = $content_data['end_date'] ? substr($content_data['end_date'], 0, 16) : '';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไข Content - User Panel</title>
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
        
        .sidebar-divider {
            border: 0;
            height: 1px;
            background-color: #495057; 
            margin: 15px 10px 20px 10px; 
        }

        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #0066cc;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .info-box strong {
            color: #0066cc;
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
                <li class="nav-item"><a class="nav-link" href="index.php">🏠 Dashboard</a></li>
                <li class="nav-item"><a class="nav-link active" href="my_content.php">📊 Content ของฉัน</a></li>
                <li class="nav-item"><a class="nav-link" href="upload.php">⬆️ อัพโหลด Content ใหม่</a></li>
                <li class="nav-item"><a class="nav-link" href="device_status.php">💻 สถานะอุปกรณ์ที่ได้รับสิทธิ์</a></li>
                <li class="nav-item"><a class="nav-link" href="../logout.php">🚪 ออกจากระบบ</a></li>
            </ul>
        </div>
        <div class="content-area">
            <h1 class="mb-4 text-primary"><i class="bi bi-pencil"></i> แก้ไข Content</h1>
            <?php echo $message; ?>

            <div class="card shadow">
                <div class="card-header card-header-custom">
                    ข้อมูล Content: <?php echo htmlspecialchars($content_data['filename']); ?>
                </div>
                <div class="card-body">
                    <div class="info-box">
                        <strong>📁 ชื่อไฟล์:</strong> <?php echo htmlspecialchars($content_data['filename']); ?><br>
                        <strong>📺 ประเภท:</strong> <?php echo ucfirst($content_data['content_type']); ?>
                    </div>

                    <form action="edit_content.php?id=<?php echo $content_id; ?>" method="POST">
                        <div class="mb-3">
                            <label for="duration_seconds" class="form-label fw-bold">ระยะเวลาแสดงผล (วินาที)</label>
                            <input type="number" class="form-control" id="duration_seconds" name="duration_seconds" value="<?php echo $content_data['duration_seconds']; ?>" min="1" required>
                            <small class="form-text text-muted">ใช้สำหรับภาพนิ่ง และกำหนดการข้ามของวิดีโอถ้าไม่มีการเล่นซ้ำ</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label">วันที่และเวลาเริ่มแสดงผล</label>
                                <input type="datetime-local" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date_display; ?>">
                                <small class="form-text text-muted">ปล่อยว่าง = แสดงทันที</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label">วันที่และเวลาสิ้นสุดการแสดงผล</label>
                                <input type="datetime-local" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date_display; ?>">
                                <small class="form-text text-muted">ปล่อยว่าง = แสดงตลอดไป</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> บันทึกการแก้ไข</button>
                            <a href="my_content.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> ยกเลิก</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>