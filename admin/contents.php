<?php
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

// --- 2. ดึงรายการ Content ทั้งหมด
$sql = "SELECT c.*, u.username 
        FROM contents c 
        LEFT JOIN users u ON c.upload_by = u.user_id 
        ORDER BY c.created_at DESC";
$result = $conn->query($sql);

function formatDateTime($dt) {
    return $dt ? date('Y-m-d H:i', strtotime($dt)) : 'เล่นตลอดไป';
}

function getDevicesForContent($conn, $content_id) {
    $devices = $conn->query("SELECT d.device_name FROM device_content dc JOIN devices d ON dc.device_id = d.device_id WHERE dc.content_id = $content_id");
    $list = [];
    while($row = $devices->fetch_assoc()) {
        $list[] = $row['device_name'];
    }
    return empty($list) ? 'ไม่ได้กำหนด' : implode(', ', $list);
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการ Content - Admin</title>
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
                <li class="nav-item"><a class="nav-link active " href="contents.php">📂 จัดการ Content</a></li>
                <li class="nav-item"><a class="nav-link" href="devices.php">💻 จัดการอุปกรณ์</a></li>
                <li class="nav-item"><a class="nav-link" href="users.php">👥 จัดการสมาชิก</a></li>
                <li class="nav-item"><a class="nav-link" href="user_roles.php">🔑 จัดการสิทธิ์</a></li>
                <li class="nav-item"><a class="nav-link" href="../logout.php">🚪 ออกจากระบบ</a></li>
            </ul>
        </div>

        <div class="content-area">
            <h1 class="mb-4">📂 จัดการ Content (วิดีโอ/ภาพ)</h1>

            <div class="d-flex justify-content-between mb-3">
                <a href="add_content.php" class="btn btn-primary">➕ อัพโหลด Content ใหม่</a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-striped shadow-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>ชื่อไฟล์</th>
                            <th>ประเภท</th>
                            <th>อัพโหลดโดย</th>
                            <th>วันที่เริ่มแสดง</th>
                            <th>วันที่สิ้นสุด</th>
                            <th>แสดงบนอุปกรณ์</th>
                            <th>การดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php $i = 1; while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td><?php echo htmlspecialchars($row['filename']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $row['content_type'] == 'video' ? 'danger' : 'success'; ?>">
                                            <?php echo ucfirst($row['content_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo formatDateTime($row['start_date']); ?></td>
                                    <td><?php echo formatDateTime($row['end_date']); ?></td>
                                    <td><?php echo getDevicesForContent($conn, $row['content_id']); ?></td>
                                    <td>
                                        <a href="edit_content.php?id=<?php echo $row['content_id']; ?>" class="btn btn-sm btn-info" title="แก้ไข">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        </button>
                                        <a href="delete_content.php?id=<?php echo $row['content_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบไฟล์นี้?')" title="ลบ">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">ไม่พบ Content ที่อัพโหลด</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>