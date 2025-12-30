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
                <li class="nav-item"><a class="nav-link active" href="contents.php"><i class="bi bi-folder2-open"></i> จัดการ Content</a></li>
                <li class="nav-item"><a class="nav-link" href="devices.php"><i class="bi bi-tv"></i> จัดการอุปกรณ์</a></li>
                <li class="nav-item"><a class="nav-link" href="users.php"><i class="bi bi-people"></i> จัดการสมาชิก</a></li>
                <li class="nav-item"><a class="nav-link" href="add_user.php"><i class="bi bi-people"></i> ลงทะเบียนสมาชิก</a></li>
                <li class="nav-item"><a class="nav-link" href="user_roles.php"><i class="bi bi-key"></i> จัดการสิทธิ์</a></li>
                <li class="nav-item"><a class="nav-link" href="account_status.php"><i class="bi bi-person-lock"></i> สถานะบัญชี</a></li>
                <li class="nav-item"><a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a></li>
            </ul>
        </div>

        <div class="content-area" id="contentArea">
            <h1 class="mb-4 page-title">📂 จัดการ Content (วิดีโอ/ภาพ)</h1>

            <div class="action-buttons mb-3">
                <a href="add_content.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> อัพโหลด Content ใหม่
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-striped shadow-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>ชื่อไฟล์</th>
                            <th>ประเภท</th>
                            <th class="hide-mobile">อัพโหลดโดย</th>
                            <th class="hide-mobile">วันที่เริ่มแสดง</th>
                            <th class="hide-mobile">วันที่สิ้นสุด</th>
                            <th class="hide-tablet">แสดงบนอุปกรณ์</th>
                            <th>การดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php $i = 1; while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td>
                                        <div class="filename-cell">
                                            <?php echo htmlspecialchars($row['filename']); ?>
                                        </div>
                                        <div class="mobile-info">
                                            <small class="text-muted d-block d-md-none">
                                                โดย: <?php echo htmlspecialchars($row['username']); ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $row['content_type'] == 'video' ? 'danger' : 'success'; ?>">
                                            <?php echo ucfirst($row['content_type']); ?>
                                        </span>
                                    </td>
                                    <td class="hide-mobile"><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td class="hide-mobile"><?php echo formatDateTime($row['start_date']); ?></td>
                                    <td class="hide-mobile"><?php echo formatDateTime($row['end_date']); ?></td>
                                    <td class="hide-tablet"><?php echo getDevicesForContent($conn, $row['content_id']); ?></td>
                                    <td>
                                        <div class="action-buttons-group">
                                            <a href="edit_content.php?id=<?php echo $row['content_id']; ?>" 
                                               class="btn btn-sm btn-info" 
                                               title="แก้ไข">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="delete_content.php?id=<?php echo $row['content_id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบไฟล์นี้?')" 
                                               title="ลบ">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
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

