<?php
// devices.php
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
        $logged_in_user['position'] = htmlspecialchars($user_data['position']);
        
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
        
        $sql = "INSERT INTO devices (device_name, location, status) VALUES (?, ?, 'offline')";
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
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/responsive_admin.css" rel="stylesheet">
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" id="mobileMenuToggle">
        <i class="bi bi-list"></i>
    </button>

    <!-- Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="d-flex">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h5 class="text-center mb-2">📺Digital signage ycap</h5>
                <button class="mobile-close-btn" id="mobileCloseBtn">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <hr class="sidebar-divider">
            
            <div class="user-profile">
                <div class="profile-initial"><?php echo $logged_in_user['profile_initial']; ?></div>
                <p class="profile-name" title="<?php echo $logged_in_user['fullname']; ?>"><?php echo $logged_in_user['fullname']; ?></p>
                <p class="profile-position"><?php echo $logged_in_user['position']; ?></p>
            </div>
            <hr class="sidebar-divider">
            
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="contents.php"><i class="bi bi-folder2-open"></i> จัดการ Content</a></li>
                <li class="nav-item"><a class="nav-link active" href="devices.php"><i class="bi bi-tv"></i> จัดการอุปกรณ์</a></li>
                <li class="nav-item"><a class="nav-link" href="users.php"><i class="bi bi-people"></i> จัดการสมาชิก</a></li>
                <li class="nav-item"><a class="nav-link" href="add_user.php"><i class="bi bi-people"></i> ลงทะเบียนสมาชิก</a></li>
                <li class="nav-item"><a class="nav-link" href="user_roles.php"><i class="bi bi-key"></i> จัดการสิทธิ์</a></li>
                <li class="nav-item"><a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a></li>
            </ul>
        </div>

        <div class="content-area" id="contentArea">
            <h1 class="mb-4 page-title">💻 จัดการอุปกรณ์จอประชาสัมพันธ์</h1>
            <?php echo $message; ?>

            <div class="card mb-4 shadow-sm">
                <div class="card-header card-header-custom">
                    <i class="bi bi-plus-circle me-2"></i>เพิ่มอุปกรณ์ใหม่
                </div>
                <div class="card-body">
                    <form method="POST" action="devices.php">
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label for="device_name" class="form-label">ชื่ออุปกรณ์</label>
                                <input type="text" class="form-control" name="device_name" placeholder="เช่น: Lobby Screen" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="location" class="form-label">ตำแหน่งติดตั้ง</label>
                                <input type="text" class="form-control" name="location" placeholder="เช่น: ชั้น 1">
                            </div>
                            <div class="col-12 col-md-4 d-flex align-items-end">
                                <button type="submit" name="add_device" class="btn btn-success w-100">
                                    <i class="bi bi-save"></i> บันทึกอุปกรณ์
                                </button>
                            </div>
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
                            <th class="hide-mobile">ตำแหน่ง</th>
                            <th>สถานะ</th>
                            <th class="hide-tablet">Playlist</th>
                            <th>การดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while($row = $devices_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($row['device_name']); ?>
                                    <small class="text-muted d-block d-md-none">
                                        <?php echo htmlspecialchars($row['location']); ?>
                                    </small>
                                </td>
                                <td class="hide-mobile"><?php echo htmlspecialchars($row['location']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $row['status'] === 'online' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td class="hide-tablet">
                                    <a href="device_playlist.php?device_id=<?php echo $row['device_id']; ?>" class="btn btn-sm btn-info text-white">
                                        <i class="bi bi-list-task"></i> <span class="d-none d-lg-inline">ดู Playlist</span>
                                    </a>
                                </td>
                                <td>
                                    <div class="action-buttons-group">
                                        <a href="device_playlist.php?device_id=<?php echo $row['device_id']; ?>" class="btn btn-sm btn-info text-white d-md-none" title="Playlist">
                                            <i class="bi bi-list-task"></i>
                                        </a>
                                        <form method="POST" action="devices.php" class="d-inline" onsubmit="return confirm('แน่ใจว่าต้องการลบ?');">
                                            <input type="hidden" name="device_id" value="<?php echo $row['device_id']; ?>">
                                            <button type="submit" name="delete_device" class="btn btn-sm btn-danger" title="ลบ">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
             <div class="footer-content-area">
                <h6>&copy; จัดทำโดย นายฐิติพงศ์ ภาสวร โครงการทดลองจ้างงานบุคคลออทิสติก รุ่นที่13</h6>
            </div> 
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/responsive_sidebar.js"></script>
</body>
</html>
<?php $conn->close(); ?>

