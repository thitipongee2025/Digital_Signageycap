<?php
// admin/user_roles.php - จัดการสิทธิ์ (รวม Header/Footer)
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

// --- Process Form Submissions (Update Permissions) ---
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_permissions'])) {
    $target_user_id = (int)$_POST['target_user_id'];
    $selected_devices = isset($_POST['devices']) ? $_POST['devices'] : [];
    
    // 1. ลบสิทธิ์เก่าทั้งหมดของ User นี้
    $conn->query("DELETE FROM user_permissions WHERE user_id = $target_user_id");

    // 2. เพิ่มสิทธิ์ใหม่ตามที่เลือก
    if (!empty($selected_devices)) {
        $insert_sql = "INSERT INTO user_permissions (user_id, device_id) VALUES (?, ?)";
        $stmt = $conn->prepare($insert_sql);
        
        foreach ($selected_devices as $device_id) {
            $device_id = (int)$device_id;
            $stmt->bind_param("ii", $target_user_id, $device_id);
            $stmt->execute();
        }
        $stmt->close();
    }
    $message = '<div class="alert alert-success">อัพเดทสิทธิ์การเข้าถึงอุปกรณ์สำหรับ User ID ' . $target_user_id . ' สำเร็จ</div>';
}

// --- Fetch Data ---
$users_result = $conn->query("SELECT user_id, username, fullname FROM users WHERE role = 'user' ORDER BY username ASC");
$devices_result = $conn->query("SELECT * FROM devices ORDER BY device_name ASC");

// --- Determine selected user and fetch permissions ---
$selected_user_id = isset($_POST['target_user_id']) ? (int)$_POST['target_user_id'] : null;

// ตั้งค่า $selected_user_id เริ่มต้น หากยังไม่ได้เลือกและมี User ในระบบ
if (!$selected_user_id && $users_result->num_rows > 0) {
    $users_result->data_seek(0);
    $selected_user_id_row = $users_result->fetch_assoc();
    $selected_user_id = $selected_user_id_row['user_id'];
    $users_result->data_seek(0);
}

$current_permissions = [];
if ($selected_user_id) {
    $perm_result = $conn->query("SELECT device_id FROM user_permissions WHERE user_id = $selected_user_id");
    while($row = $perm_result->fetch_assoc()) {
        $current_permissions[] = $row['device_id'];
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสิทธิ์ - Digital Signage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="../assets/css/style.css" rel="stylesheet">
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
        .card-icon {
            font-size: 3rem;
            opacity: 0.5;
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
                <li class="nav-item"><a class="nav-link" href="contents.php">📂 จัดการ Content</a></li>
                <li class="nav-item"><a class="nav-link" href="devices.php">💻 จัดการอุปกรณ์</a></li>
                <li class="nav-item"><a class="nav-link" href="users.php">👥 จัดการสมาชิก</a></li>
                <li class="nav-item"><a class="nav-link  active" href="user_roles.php">🔑 จัดการสิทธิ์</a></li>
                <li class="nav-item"><a class="nav-link" href="../logout.php">🚪 ออกจากระบบ</a></li>
            </ul>
        </div>

        <div class="content-area">
            <h1 class="mb-4">🔑 จัดการสิทธิ์การใช้งานของ User</h1>
            <?php echo $message; ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="user_roles.php">
                        <div class="mb-4">
                            <label for="target_user_id" class="form-label fw-bold">เลือก User ที่ต้องการกำหนดสิทธิ์</label>
                            <select class="form-select" id="target_user_id" name="target_user_id" onchange="this.form.submit()">
                                <option value="">-- เลือก User --</option>
                                <?php 
                                // เนื่องจาก data_seek(0) อาจไม่ทำงานในทุกกรณี จึงควรวนซ้ำจาก $users_result ที่ถูกรีเซ็ต (หากมีการดึงข้อมูลไปแล้ว)
                                $users_result->data_seek(0); 
                                while($user = $users_result->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $user['user_id']; ?>" <?php echo $user['user_id'] == $selected_user_id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['username']) . ' (' . htmlspecialchars($user['fullname']) . ')'; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </form>

                    <?php if ($selected_user_id): ?>
                    <form method="POST" action="user_roles.php">
                        <input type="hidden" name="target_user_id" value="<?php echo $selected_user_id; ?>">
                        
                        <h5 class="mt-4 mb-3">กำหนดอุปกรณ์ที่ User สามารถอัพโหลด/จัดการ Content ได้:</h5>
                        <div class="form-check p-3 border rounded mb-3 bg-light">
                            <label class="form-check-label fw-bold text-danger">หมายเหตุ: User จะสามารถอัพโหลดและลบ Content ในอุปกรณ์ที่เลือกไว้เท่านั้น</label>
                        </div>

                        <div class="row">
                        <?php 
                        // รีเซ็ตตัวชี้สำหรับ $devices_result 
                        $devices_result->data_seek(0);
                        while($device = $devices_result->fetch_assoc()): 
                        ?>
                            <div class="col-md-4 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="devices[]" 
                                        value="<?php echo $device['device_id']; ?>" 
                                        id="device_<?php echo $device['device_id']; ?>"
                                        <?php echo in_array($device['device_id'], $current_permissions) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="device_<?php echo $device['device_id']; ?>">
                                        <i class="bi bi-tv"></i> <?php echo htmlspecialchars($device['device_name']) . ' (' . htmlspecialchars($device['location']) . ')'; ?>
                                    </label>
                                </div>
                            </div>
                        <?php endwhile; ?>
                        </div>

                        <button type="submit" name="update_permissions" class="btn btn-primary mt-4">บันทึกสิทธิ์การใช้งาน</button>
                    </form>
                    <?php else: ?>
                        <div class="alert alert-info mt-3">กรุณาเลือก User เพื่อเริ่มกำหนดสิทธิ์</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>