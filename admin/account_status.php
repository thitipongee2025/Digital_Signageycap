<?php
// admin/account_status.php - จัดการสถานะบัญชีผู้ใช้งาน
include '../config.php';
checkAdminLogin();

// --- ดึงข้อมูลผู้ใช้งานที่ล็อกอิน ---
$logged_in_user = [
    'fullname' => 'ไม่ระบุชื่อ',
    'position' => 'ไม่ระบุตำแหน่ง',
    'profile_initial' => 'A'
];

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    $user_sql = "SELECT u.fullname, u.position FROM users u WHERE u.user_id = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    
    if ($user_data = $user_result->fetch_assoc()) {
        $logged_in_user['fullname'] = htmlspecialchars($user_data['fullname']);
        $logged_in_user['position'] = htmlspecialchars($user_data['position']);
        $parts = explode(' ', trim($user_data['fullname']));
        $logged_in_user['profile_initial'] = mb_substr($parts[0], 0, 1, 'UTF-8');
    }
    $user_stmt->close();
}

// --- Process Form Submissions ---
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $target_user_id = (int)$_POST['user_id'];
    $new_status = $_POST['account_status'];
    
    // ตรวจสอบว่าไม่ใช่การแก้ไขตัวเอง
    if ($target_user_id === $_SESSION['user_id']) {
        $message = '<div class="alert alert-danger"><i class="bi bi-x-circle-fill"></i> ไม่สามารถแก้ไขสถานะบัญชีของตัวเองได้</div>';
    } else {
        $update_sql = "UPDATE users SET account_status = ? WHERE user_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $new_status, $target_user_id);
        
        if ($update_stmt->execute()) {
            $status_text = ($new_status === 'active') ? 'เปิดการใช้งาน' : 'ระงับการใช้งาน';
            $message = '<div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> อัพเดทสถานะเป็น "' . $status_text . '" สำเร็จ</div>';
        } else {
            $message = '<div class="alert alert-danger"><i class="bi bi-x-circle-fill"></i> เกิดข้อผิดพลาด: ' . $update_stmt->error . '</div>';
        }
        $update_stmt->close();
    }
}

// --- Fetch Users ---
$users_sql = "SELECT user_id, username, fullname, role, account_status, created_at FROM users ORDER BY account_status DESC, fullname ASC";
$users_result = $conn->query($users_sql);
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสถานะบัญชี - Digital Signage</title>
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
                <p class="profile-name" title="<?php echo $logged_in_user['fullname']; ?>">
                    <?php echo $logged_in_user['fullname']; ?></p>
                <p class="profile-position"><?php echo $logged_in_user['position']; ?></p>
            </div>
            <hr class="sidebar-divider">

            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i>
                        Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="contents.php"><i class="bi bi-folder2-open"></i> จัดการ
                        Content</a></li>
                <li class="nav-item"><a class="nav-link" href="devices.php"><i class="bi bi-tv"></i> จัดการอุปกรณ์</a>
                </li>
                <li class="nav-item"><a class="nav-link" href="users.php"><i class="bi bi-people"></i> จัดการสมาชิก</a>
                </li>
                <li class="nav-item"><a class="nav-link" href="add_user.php"><i class="bi bi-people"></i>
                        ลงทะเบียนสมาชิก</a></li>
                <li class="nav-item"><a class="nav-link" href="user_roles.php"><i class="bi bi-key"></i>
                        จัดการสิทธิ์</a></li>
                <li class="nav-item"><a class="nav-link active" href="account_status.php"><i
                            class="bi bi-person-lock"></i> สถานะบัญชี</a></li>
                <li class="nav-item"><a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i>
                        ออกจากระบบ</a></li>
            </ul>
        </div>

        <div class="content-area" id="contentArea">
            <h1 class="mb-4 page-title"><i class="bi bi-person-lock"></i> จัดการสถานะบัญชีผู้ใช้งาน</h1>

            <?php echo $message; ?>

            <div class="alert alert-info mb-4">
                <i class="bi bi-info-circle-fill"></i>
                <strong>คำแนะนำ:</strong>
                <ul class="mb-0 mt-2">
                    <li><strong>เปิดการใช้งาน:</strong> ผู้ใช้สามารถเข้าสู่ระบบและใช้งานได้ตามปกติ</li>
                    <li><strong>ระงับการใช้งาน:</strong> ผู้ใช้ไม่สามารถใช้งานระบบได้ทั้งหมด
                        (แต่ยังสามารถเข้าสู่ระบบได้)</li>
                </ul>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-striped shadow-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>ชื่อผู้ใช้</th>
                            <th class="hide-mobile">ชื่อ-นามสกุล</th>
                            <th>สิทธิ์</th>
                            <th>สถานะบัญชี</th>
                            <th class="hide-mobile">วันที่สร้าง</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while($row = $users_result->fetch_assoc()): ?>
                        <tr class="<?php echo $row['account_status'] === 'suspended' ? 'table-secondary' : ''; ?>">
                            <td><?php echo $i++; ?></td>
                            <td>
                                <?php echo htmlspecialchars($row['username']); ?>
                                <?php if ($row['user_id'] === $_SESSION['user_id']): ?>
                                <span class="badge bg-info text-dark">คุณ</span>
                                <?php endif; ?>
                                <small class="text-muted d-block d-md-none">
                                    <?php echo htmlspecialchars($row['fullname']); ?>
                                </small>
                            </td>
                            <td class="hide-mobile"><?php echo htmlspecialchars($row['fullname']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $row['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                    <?php echo ucfirst($row['role']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($row['account_status'] === 'active'): ?>
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle-fill"></i> เปิดใช้งาน
                                </span>
                                <?php else: ?>
                                <span class="badge bg-danger">
                                    <i class="bi bi-ban"></i> ระงับการใช้งาน
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="hide-mobile"><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                            <td>
                                <?php if ($row['user_id'] !== $_SESSION['user_id']): ?>
                                <form method="POST" action="account_status.php" class="d-inline">
                                    <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">

                                    <?php if ($row['account_status'] === 'active'): ?>
                                    <input type="hidden" name="account_status" value="suspended">
                                    <button type="submit" name="update_status" class="btn btn-sm btn-warning"
                                        onclick="return confirm('ต้องการระงับการใช้งานบัญชี <?php echo htmlspecialchars($row['username']); ?> หรือไม่?');">
                                        <i class="bi bi-ban"></i> ระงับ
                                    </button>
                                    <?php else: ?>
                                    <input type="hidden" name="account_status" value="active">
                                    <button type="submit" name="update_status" class="btn btn-sm btn-success"
                                        onclick="return confirm('ต้องการเปิดการใช้งานบัญชี <?php echo htmlspecialchars($row['username']); ?> หรือไม่?');">
                                        <i class="bi bi-check-circle"></i> เปิดใช้งาน
                                    </button>
                                    <?php endif; ?>
                                </form>
                                <?php else: ?>
                                <button class="btn btn-sm btn-secondary" disabled>
                                    <i class="bi bi-lock"></i> ตัวคุณเอง
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="card mt-4 shadow-sm">
                <div class="card-header card-header-custom">
                    <i class="bi bi-info-circle me-2"></i> ข้อมูลเพิ่มเติม
                </div>
                <div class="card-body">
                    <h6 class="fw-bold">เมื่อระงับการใช้งานบัญชี:</h6>
                    <ul>
                        <li>ผู้ใช้ยังสามารถเข้าสู่ระบบได้ แต่จะถูกนำไปยังหน้าแจ้งเตือนการระงับบัญชี</li>
                        <li>ไม่สามารถเข้าถึง Dashboard และฟีเจอร์ต่างๆ ได้</li>
                        <li>Content ที่อัพโหลดไว้ยังคงแสดงผลบนหน้าจอตามปกติ</li>
                        <li>สามารถเปิดการใช้งานกลับมาได้ทันที</li>
                    </ul>

                    <h6 class="fw-bold mt-3">กรณีใช้งาน:</h6>
                    <ul>
                        <li><strong>พนักงานลาออก:</strong> ระงับบัญชีชั่วคราว</li>
                        <li><strong>พนักงานโอนย้าย:</strong> ระงับบัญชีจนกว่าจะมีการยืนยันตำแหน่งใหม่</li>
                        <li><strong>พนักงานกลับมาปฏิบัติงาน:</strong> เปิดการใช้งานบัญชีเดิม</li>
                    </ul>
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