<?php
// admin/users.php - จัดการสมาชิก (รวม Header/Footer)
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

// --- Process Form Submissions (Delete User) ---
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user']) && is_numeric($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    
    // ลบสิทธิ์การใช้งานก่อน
    $conn->query("DELETE FROM user_permissions WHERE user_id = $user_id");
    // (ทางเลือก: ลบ Content ที่ User นี้อัพโหลด หรือเปลี่ยนเจ้าของ)

    $sql = "DELETE FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">ลบสมาชิกสำเร็จ</div>';
    } else {
        $message = '<div class="alert alert-danger">ลบสมาชิกไม่สำเร็จ: ' . $stmt->error . '</div>';
    }
    $stmt->close();
}

// --- Fetch Users ---
// ดึงฟิลด์ข้อมูลเพิ่มเติม (work_status, position, agency)
$users_result = $conn->query("SELECT user_id, username, fullname, role, work_status, position, agency, created_at FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสมาชิก - Digital Signage</title>
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
                <li class="nav-item"><a class="nav-link active" href="users.php">👥 จัดการสมาชิก</a></li>
                <li class="nav-item"><a class="nav-link" href="user_roles.php">🔑 จัดการสิทธิ์</a></li>
                <li class="nav-item"><a class="nav-link" href="../logout.php">🚪 ออกจากระบบ</a></li>
            </ul>
        </div>

        <div class="content-area">
            <h1 class="mb-4">👥 จัดการสมาชิก</h1>
            <?php echo $message; ?>

            <div class="table-responsive">
                <table class="table table-hover table-striped shadow-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>ชื่อผู้ใช้</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>สถานะปฏิบัติงาน</th>
                            <th>ตำแหน่ง/หน่วยงาน</th>
                            <th>สิทธิ์</th>
                            <th>วันที่สร้าง</th>
                            <th>การดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while($row = $users_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                                <td><?php echo htmlspecialchars($row['work_status']); ?></td>
                                <td><?php echo htmlspecialchars($row['position'] . ' / ' . $row['agency']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $row['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                        <?php echo ucfirst($row['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <?php if ($row['user_id'] !== $_SESSION['user_id']): ?>
                                        <form method="POST" action="users.php" class="d-inline" onsubmit="return confirm('แน่ใจว่าต้องการลบสมาชิก <?php echo htmlspecialchars($row['username']); ?>?');">
                                            <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                            <button type="submit" name="delete_user" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> ลบ</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">ไม่สามารถลบได้</span>
                                    <?php endif; ?>
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