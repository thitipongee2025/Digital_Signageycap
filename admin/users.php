<?php
// admin/users.php - จัดการสมาชิก
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

// --- Process Form Submissions (Delete User) ---
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user']) && is_numeric($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    
    $conn->query("DELETE FROM user_permissions WHERE user_id = $user_id");

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
                <li class="nav-item"><a class="nav-link active" href="users.php"><i class="bi bi-people"></i> จัดการสมาชิก</a></li>
                <li class="nav-item"><a class="nav-link" href="add_user.php"><i class="bi bi-people"></i> ลงทะเบียนสมาชิก</a></li>
                <li class="nav-item"><a class="nav-link" href="user_roles.php"><i class="bi bi-key"></i> จัดการสิทธิ์</a></li>
                <li class="nav-item"><a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a></li>
            </ul>
        </div>

        <div class="content-area" id="contentArea">
            <h1 class="mb-4 page-title">👥 จัดการสมาชิก</h1>
            <?php echo $message; ?>

            <!-- ปุ่มเพิ่มสมาชิก -->
            <div class="action-buttons mb-3">
                <a href="add_user.php" class="btn btn-primary">
                    <i class="bi bi-person-plus"></i> เพิ่มสมาชิกใหม่
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-striped shadow-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>ชื่อผู้ใช้</th>
                            <th class="hide-mobile">ชื่อ-นามสกุล</th>
                            <th class="hide-tablet">สถานะปฏิบัติงาน</th>
                            <th class="hide-tablet">ตำแหน่ง/หน่วยงาน</th>
                            <th>สิทธิ์</th>
                            <th class="hide-mobile">วันที่สร้าง</th>
                            <th>การดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while($row = $users_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $i++; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($row['username']); ?>
                                    <small class="text-muted d-block d-md-none">
                                        <?php echo htmlspecialchars($row['fullname']); ?>
                                    </small>
                                </td>
                                <td class="hide-mobile"><?php echo htmlspecialchars($row['fullname']); ?></td>
                                <td class="hide-tablet"><?php echo htmlspecialchars($row['work_status']); ?></td>
                                <td class="hide-tablet">
                                    <?php 
                                    $pos_agency = htmlspecialchars($row['position']);
                                    if (!empty($row['agency'])) {
                                        $pos_agency .= ' / ' . htmlspecialchars($row['agency']);
                                    }
                                    echo $pos_agency; 
                                    ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $row['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                        <?php echo ucfirst($row['role']); ?>
                                    </span>
                                </td>
                                <td class="hide-mobile"><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons-group">
                                        <!-- ปุ่มแก้ไข -->
                                        <a href="edit_user.php?id=<?php echo $row['user_id']; ?>" 
                                           class="btn btn-sm btn-warning" 
                                           title="แก้ไข">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        
                                        <!-- ปุ่มลบ (ไม่สามารถลบตัวเองได้) -->
                                        <?php if ($row['user_id'] !== $_SESSION['user_id']): ?>
                                            <form method="POST" action="users.php" class="d-inline" onsubmit="return confirm('แน่ใจว่าต้องการลบสมาชิก <?php echo htmlspecialchars($row['username']); ?>?');">
                                                <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                                                <button type="submit" name="delete_user" class="btn btn-sm btn-danger" title="ลบ">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-secondary" disabled title="ไม่สามารถลบตัวเองได้">
                                                <i class="bi bi-lock"></i>
                                            </button>
                                        <?php endif; ?>
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
