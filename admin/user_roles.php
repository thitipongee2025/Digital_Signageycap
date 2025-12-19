<?php
// admin/user_roles.php - จัดการสิทธิ์
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

// --- Process Form Submissions (Update Permissions) ---
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_permissions'])) {
    $target_user_id = (int)$_POST['target_user_id'];
    $selected_devices = isset($_POST['devices']) ? $_POST['devices'] : [];
    
    $conn->query("DELETE FROM user_permissions WHERE user_id = $target_user_id");

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
    $message = '<div class="alert alert-success">อัพเดทสิทธิ์สำเร็จ</div>';
}

// --- Fetch Data ---
$users_result = $conn->query("SELECT user_id, username, fullname FROM users WHERE role = 'user' ORDER BY username ASC");
$devices_result = $conn->query("SELECT * FROM devices ORDER BY device_name ASC");

$selected_user_id = isset($_POST['target_user_id']) ? (int)$_POST['target_user_id'] : null;

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
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
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
                <li class="nav-item"><a class="nav-link" href="devices.php"><i class="bi bi-tv"></i> จัดการอุปกรณ์</a></li>
                <li class="nav-item"><a class="nav-link" href="users.php"><i class="bi bi-people"></i> จัดการสมาชิก</a></li>
                <li class="nav-item"><a class="nav-link" href="add_user.php"><i class="bi bi-people"></i> ลงทะเบียนสมาชิก</a></li>
                <li class="nav-item"><a class="nav-link active" href="user_roles.php"><i class="bi bi-key"></i> จัดการสิทธิ์</a></li>
                <li class="nav-item"><a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a></li>
            </ul>
        </div>

        <div class="content-area" id="contentArea">
            <h1 class="mb-4 page-title">🔑 จัดการสิทธิ์การใช้งาน</h1>
            <?php echo $message; ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="user_roles.php">
                        <div class="mb-4">
                            <label for="target_user_id" class="form-label fw-bold">เลือก User</label>
                            <select class="form-select" id="target_user_id" name="target_user_id" onchange="this.form.submit()">
                                <option value="">-- เลือก User --</option>
                                <?php 
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
                        
                        <h5 class="mt-4 mb-3">กำหนดอุปกรณ์ที่ User สามารถจัดการได้:</h5>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> User จะสามารถอัพโหลดและลบ Content ในอุปกรณ์ที่เลือกเท่านั้น
                        </div>

                        <div class="row">
                        <?php 
                        $devices_result->data_seek(0);
                        while($device = $devices_result->fetch_assoc()): 
                        ?>
                            <div class="col-12 col-sm-6 col-lg-4 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="devices[]" 
                                        value="<?php echo $device['device_id']; ?>" 
                                        id="device_<?php echo $device['device_id']; ?>"
                                        <?php echo in_array($device['device_id'], $current_permissions) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="device_<?php echo $device['device_id']; ?>">
                                        <i class="bi bi-tv"></i> <?php echo htmlspecialchars($device['device_name']); ?>
                                        <small class="text-muted d-block"><?php echo htmlspecialchars($device['location']); ?></small>
                                    </label>
                                </div>
                            </div>
                        <?php endwhile; ?>
                        </div>

                        <button type="submit" name="update_permissions" class="btn btn-primary mt-4 w-100 w-md-auto">
                            <i class="bi bi-save"></i> บันทึกสิทธิ์
                        </button>
                    </form>
                    <?php else: ?>
                        <div class="alert alert-info mt-3">กรุณาเลือก User เพื่อเริ่มกำหนดสิทธิ์</div>
                    <?php endif; ?>
                </div>
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

