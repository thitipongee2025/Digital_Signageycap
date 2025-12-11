<?php
// devices.php
include '../config.php';
// ฟังก์ชัน checkAdminLogin() ต้องถูกกำหนดใน config.php
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


// --- Process Form Submissions ---
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_device'])) {
        $name = $_POST['device_name'];
        $location = $_POST['location'];
        
        $sql = "INSERT INTO devices (device_name, location, status) VALUES (?, ?, 'offline')"; // เพิ่ม status default
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $name, $location);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">เพิ่มอุปกรณ์สำเร็จ</div>';
        } else {
            $message = '<div class="alert alert-danger">เพิ่มอุปกรณ์ไม่สำเร็จ: ' . $stmt->error . '</div>';
        }
        $stmt->close();
    } elseif (isset($_POST['delete_device']) && is_numeric($_POST['device_id'])) {
        $device_id = (int)$_POST['device_id'];
        
        // ลบข้อมูลที่เกี่ยวข้องในตารางอื่น
        $conn->query("DELETE FROM device_content WHERE device_id = $device_id");
        $conn->query("DELETE FROM user_permissions WHERE device_id = $device_id");

        $sql = "DELETE FROM devices WHERE device_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $device_id);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">ลบอุปกรณ์สำเร็จ</div>';
        } else {
            $message = '<div class="alert alert-danger">ลบอุปกรณ์ไม่สำเร็จ: ' . $stmt->error . '</div>';
        }
        $stmt->close();
    }
}

// --- Fetch Devices ---
$devices_result = $conn->query("SELECT * FROM devices ORDER BY device_name ASC");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการอุปกรณ์ - Digital Signage</title>
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
                <li class="nav-item"><a class="nav-link active" href="devices.php">💻 จัดการอุปกรณ์</a></li>
                <li class="nav-item"><a class="nav-link" href="users.php">👥 จัดการสมาชิก</a></li>
                <li class="nav-item"><a class="nav-link" href="user_roles.php">🔑 จัดการสิทธิ์</a></li>
                <li class="nav-item"><a class="nav-link" href="../logout.php">🚪 ออกจากระบบ</a></li>
            </ul>
        </div>

        <div class="content-area">
            <h1 class="mb-4">💻 จัดการอุปกรณ์จอประชาสัมพันธ์</h1>
            <?php echo $message; ?>

            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white">เพิ่มอุปกรณ์ใหม่</div>
                <div class="card-body">
                    <form method="POST" action="devices.php" class="row">
                        <div class="col-md-4 mb-3">
                            <label for="device_name" class="form-label">ชื่ออุปกรณ์ (เช่น: Lobby Screen)</label>
                            <input type="text" class="form-control" name="device_name" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="location" class="form-label">ตำแหน่งติดตั้ง</label>
                            <input type="text" class="form-control" name="location">
                        </div>
                        <div class="col-md-4 mb-3 d-flex align-items-end">
                            <button type="submit" name="add_device" class="btn btn-success w-100">บันทึกอุปกรณ์</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="table-responsive">
    <table class="table table-hover table-striped shadow-sm">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>ชื่ออุปกรณ์</th>
                <th>ตำแหน่ง</th>
                <th>สถานะ</th>
                <th>Playlist</th> 
                <th>การดำเนินการ</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; while($row = $devices_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars($row['device_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['location']); ?></td>
                    <td>
                        <span class="badge bg-<?php echo $row['status'] === 'online' ? 'success' : 'secondary'; ?>">
                            <?php echo ucfirst($row['status']); ?>
                        </span>
                    </td>
                    <td>
                        <a href="device_playlist.php?device_id=<?php echo $row['device_id']; ?>" class="btn btn-sm btn-info text-white">
                            <i class="bi bi-list-task"></i> ดู Playlist
                        </a>
                    </td>
                    <td>
                        <form method="POST" action="devices.php" class="d-inline" onsubmit="return confirm('แน่ใจว่าต้องการลบอุปกรณ์ <?php echo htmlspecialchars($row['device_name']); ?> และ Playlist ทั้งหมดที่เกี่ยวข้อง?');">
                            <input type="hidden" name="device_id" value="<?php echo $row['device_id']; ?>">
                            <button type="submit" name="delete_device" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> ลบ</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    </div>
    </div>
    </div>
    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>