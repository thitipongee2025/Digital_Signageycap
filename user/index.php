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
            <h1 class="mb-2 text-primary"><i class="bi bi-house-door"></i> Dashboard</h1>
            <p class="text-muted mb-4">ยินดีต้อนรับกลับมา, <?php echo htmlspecialchars($logged_in_user['fullname']); ?>!</p>
            
            <?php echo $message; ?>

            <div class="dashboard-grid">
                <a href="my_content.php" class="dashboard-card content">
                    <i class="bi bi-film"></i>
                    <h3>Content ของฉัน</h3>
                    <p>ดูและจัดการไฟล์ที่คุณอัพโหลด</p>
                </a>

                <a href="upload.php" class="dashboard-card upload">
                    <i class="bi bi-cloud-arrow-up"></i>
                    <h3>อัพโหลด Content ใหม่</h3>
                    <p>เพิ่มไฟล์วิดีโอหรือภาพ</p>
                </a>

                <a href="device_status.php" class="dashboard-card device">
                    <i class="bi bi-display"></i>
                    <h3>สถานะอุปกรณ์</h3>
                    <p>ดูอุปกรณ์ที่คุณมีสิทธิ์ใช้งาน</p>
                </a>
            </div>  
        </div>
     
    </div>
    <div class="footer-content-area">
    <h6>&copy; จัดทำโดย นายฐิติพงศ์ ภาสวร โครงการทดลองจ้างงานบุคคลออทิสติก รุ่นที่13</h6>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/sidebar-menu.js"></script>
</body>
</html>
<?php $conn->close(); ?>
