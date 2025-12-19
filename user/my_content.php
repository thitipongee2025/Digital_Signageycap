<?php
include '../config.php';
// ตรวจสอบ Login และสิทธิ์ User
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../index.php");
    exit();
}

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

// ดึงรายการอุปกรณ์ที่ User นี้มีสิทธิ์เข้าใช้งาน
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

// ดึง Content ทั้งหมดที่ User นี้อัพโหลดไปในอุปกรณ์ที่เขามีสิทธิ์
$content_result = null;
if (!empty($allowed_device_ids)) {
    $placeholders = implode(',', array_fill(0, count($allowed_device_ids), '?'));
    $content_sql = "
        SELECT 
            c.content_id, 
            c.filename, 
            c.content_type,
            c.start_date,
            c.end_date,
            GROUP_CONCAT(d.device_name SEPARATOR ', ') AS assigned_devices
        FROM 
            contents c
        JOIN 
            device_content dc ON c.content_id = dc.content_id
        JOIN 
            devices d ON dc.device_id = d.device_id
        WHERE 
            c.upload_by = ? 
            AND dc.device_id IN ($placeholders)
        GROUP BY 
            c.content_id
        ORDER BY 
            c.content_id DESC
    ";
    $content_stmt = $conn->prepare($content_sql);
    $params = array_merge([$user_id], $allowed_device_ids);
    $types = str_repeat('i', count($params));
    $content_stmt->bind_param($types, ...$params);
    $content_stmt->execute();
    $content_result = $content_stmt->get_result();
    $content_stmt->close();
}
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
        <hr class="sidebar-divider">
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
            <h1 class="mb-4 text-primary"><i class="bi bi-film"></i> Content ของฉัน</h1>
            <?php echo $message; ?>
            <div class="mb-4">
                <a href="upload.php" class="btn btn-success"><i class="bi bi-plus-circle"></i> อัพโหลด Content ใหม่</a>
            </div>

            <div class="card shadow">
                <div class="card-header card-header-custom">
                    รายการ Content ที่คุณอัพโหลดในอุปกรณ์ของคุณ
                </div>
                <div class="card-body">
                    <?php if (empty($allowed_device_ids)): ?>
                        <div class="alert alert-warning">คุณยังไม่ได้รับสิทธิ์ให้เข้าถึงอุปกรณ์ใดๆ กรุณาติดต่อ Admin</div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>ชื่อไฟล์</th>
                                    <th>ประเภท</th>
                                    <th>แสดงบนอุปกรณ์</th>
                                    <th>เริ่ม</th>
                                    <th>สิ้นสุด</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($allowed_device_ids) && $content_result && $content_result->num_rows > 0): ?>
                                    <?php $i = 1; while($row = $content_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $i++; ?></td>
                                            <td><?php echo htmlspecialchars($row['filename']); ?></td>
                                            <td><span class="badge bg-info text-dark"><?php echo ucfirst($row['content_type']); ?></span></td>
                                            <td><?php echo htmlspecialchars($row['assigned_devices']); ?></td>
                                            <td><?php echo $row['start_date'] ? date('d/m/Y H:i', strtotime($row['start_date'])) : '-'; ?></td>
                                            <td><?php echo $row['end_date'] ? date('d/m/Y H:i', strtotime($row['end_date'])) : '-'; ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="edit_content.php?id=<?php echo $row['content_id']; ?>" class="btn btn-sm btn-primary" title="แก้ไข">
                                                        <i class="bi bi-pencil"></i> แก้ไข
                                                    </a>
                                                    <a href="delete_content.php?id=<?php echo $row['content_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบไฟล์นี้?')" title="ลบ">
                                                        <i class="bi bi-trash"></i> ลบ
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">ไม่พบ Content ที่คุณอัพโหลดในอุปกรณ์ที่ได้รับสิทธิ์</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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