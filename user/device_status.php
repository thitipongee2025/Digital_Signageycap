<?php
include '../config.php';
// ตรวจสอบ Login และสิทธิ์ User
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../index.php");
    exit();
}

// ตรวจสอบสถานะบัญชี
checkAccountStatus($conn, $_SESSION['user_id']);

$user_id = $_SESSION['user_id'];
$message = '';
if (isset($_SESSION['message'])) {
    $message = '<div class="alert alert-' . $_SESSION['message']['type'] . '">' . $_SESSION['message']['text'] . '</div>';
    unset($_SESSION['message']);
}

// --- ดึงข้อมูลผู้ใช้งานที่ล็อกอินจริง ---
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

// ดึงรายการอุปกรณ์ที่ User นี้มีสิทธิ์เข้าใช้งาน พร้อมสถานะ
$devices_sql = "
    SELECT 
        d.device_id, 
        d.device_name, 
        d.location, 
        d.status,
        (SELECT COUNT(dc.content_id) FROM device_content dc WHERE dc.device_id = d.device_id) as total_content
    FROM 
        devices d
    JOIN 
        user_permissions up ON d.device_id = up.device_id
    WHERE 
        up.user_id = ?
    ORDER BY 
        d.device_name
";
$devices_stmt = $conn->prepare($devices_sql);
$devices_stmt->bind_param("i", $user_id);
$devices_stmt->execute();
$devices_result = $devices_stmt->get_result();
$devices_stmt->close();

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Signage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/user-panel.css">
</head>

<body>
    <button class="sidebar-toggle">
        <i class="bi bi-list"></i>
    </button>

    <div class="sidebar">
    <div class="sidebar-header">
    <h5 class="text-center mb-2">📺Digital signage ycap</h5>
    </div>
    <div class="user-profile">
    <div class="profile-initial"><?php echo $logged_in_user['profile_initial']; ?></div>
    <p class="profile-name"><?php echo $logged_in_user['fullname']; ?></p>
    <p class="profile-position"><?php echo $logged_in_user['position']; ?></p>
    </div>
    <hr class="sidebar-divider">
    <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
    <i class="bi bi-house-door"></i> Dashboard
    </a>
    <a href="my_content.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'my_content.php' ? 'active' : ''; ?>">
    <i class="bi bi-film"></i> Content ของฉัน
    </a>
    <a href="upload.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'upload.php' ? 'active' : ''; ?>">
    <i class="bi bi-cloud-arrow-up"></i> อัพโหลด Content
    </a>
    <a href="device_status.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'device_status.php' ? 'active' : ''; ?>">
    <i class="bi bi-display"></i> สถานะอุปกรณ์
    </a>
    <a href="../logout.php" class="text-danger">
    <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
    </a>
    </div>

    <div class="content-area">
            <h1 class="mb-4 text-info"><i class="bi bi-display"></i> สถานะอุปกรณ์ที่ได้รับสิทธิ์</h1>
            <?php echo $message; ?>

            <div class="card shadow">
                <div class="card-header card-header-custom">
                    รายการอุปกรณ์ที่คุณสามารถอัพโหลด Content ได้
                </div>
                <div class="card-body">
                    <?php if ($devices_result->num_rows === 0): ?>
    <div class="alert alert-warning">คุณยังไม่ได้รับสิทธิ์ให้เข้าถึงอุปกรณ์ใดๆ กรุณาติดต่อ Admin</div>
<?php else: ?>
    <!-- Desktop Table View -->
    <table class="table table-hover device-table-desktop">
        <thead>
            <tr>
                <th>#</th>
                <th>ชื่ออุปกรณ์</th>
                <th>ตำแหน่งติดตั้ง</th>
                <th>สถานะปัจจุบัน</th>
                <th>จัดการ</th>
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
                            <?php echo $row['status'] === 'online' ? '<i class="bi bi-check-circle-fill"></i> ออนไลน์' : '<i class="bi bi-x-circle-fill"></i> ออฟไลน์'; ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <a href="playlist_preview.php?device_id=<?php echo $row['device_id']; ?>" class="btn btn-sm btn-info" title="ดูเพลยลิสต์">
                                <i class="bi bi-play-circle"></i> ดูเพลยลิสต์
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Mobile/Tablet Card View -->
    <div class="device-cards-mobile">
        <?php 
        // รีเซ็ตตัวชี้ของ result set กลับไปตำแหน่งเริ่มต้น
        $devices_result->data_seek(0);
        $i = 1; 
        while($row = $devices_result->fetch_assoc()): 
        ?>
            <div class="device-card">
                <div class="device-card-header">
                    <div class="device-card-number"><?php echo $i++; ?></div>
                    <span class="badge bg-<?php echo $row['status'] === 'online' ? 'success' : 'secondary'; ?> device-card-status">
                        <?php echo $row['status'] === 'online' ? '<i class="bi bi-check-circle-fill"></i> ออนไลน์' : '<i class="bi bi-x-circle-fill"></i> ออฟไลน์'; ?>
                    </span>
                </div>
                
                <div class="device-card-body">
                    <div class="device-card-row">
                        <div class="device-card-label">
                            <i class="bi bi-display"></i> อุปกรณ์:
                        </div>
                        <div class="device-card-value">
                            <strong><?php echo htmlspecialchars($row['device_name']); ?></strong>
                        </div>
                    </div>
                    
                    <div class="device-card-row">
                        <div class="device-card-label">
                            <i class="bi bi-geo-alt"></i> ตำแหน่ง:
                        </div>
                        <div class="device-card-value">
                            <?php echo htmlspecialchars($row['location']); ?>
                        </div>
                    </div>
                    
                    <div class="device-card-row">
                        <div class="device-card-label">
                            <i class="bi bi-collection-play"></i> Content:
                        </div>
                        <div class="device-card-value">
                            <span class="badge bg-primary"><?php echo $row['total_content']; ?> รายการ</span>
                        </div>
                    </div>
                </div>
                
                <div class="device-card-actions">
                    <a href="playlist_preview.php?device_id=<?php echo $row['device_id']; ?>" class="btn btn-info">
                        <i class="bi bi-play-circle"></i> ดูเพลยลิสต์
                    </a>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
<?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/sidebar-menu.js"></script>
    <div class="footer-content-area">
                <h6>&copy; จัดทำโดย นายฐิติพงศ์ ภาสวร โครงการทดลองจ้างงานบุคคลออทิสติก รุ่นที่13</h6>
            </div>
</body>
</html>
<?php $conn->close(); ?>
