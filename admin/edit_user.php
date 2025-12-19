<?php
// admin/edit_user.php - แก้ไขข้อมูลสมาชิก
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

// --- รับ ID ของผู้ใช้ที่ต้องการแก้ไข ---
$edit_user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$error = '';

if ($edit_user_id === 0) {
    header("Location: users.php");
    exit();
}

// --- Process Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    
    $username = trim($_POST['username']);
    $fullname = trim($_POST['fullname']);
    $work_status = $_POST['work_status'];
    $position = trim($_POST['position']);
    $agency = trim($_POST['agency']);
    $role = $_POST['role'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($username) || empty($fullname)) {
        $error = 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน';
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $error = 'รหัสผ่านใหม่และยืนยันรหัสผ่านไม่ตรงกัน';
    } elseif (!empty($new_password) && strlen($new_password) < 6) {
        $error = 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร';
    } else {
        // ตรวจสอบว่า username ซ้ำกับคนอื่นหรือไม่
        $check_sql = "SELECT user_id FROM users WHERE username = ? AND user_id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $username, $edit_user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = 'ชื่อผู้ใช้นี้ถูกใช้งานแล้ว กรุณาเลือกชื่อผู้ใช้อื่น';
            $check_stmt->close();
        } else {
            $check_stmt->close();
            
            // Update ข้อมูล
            if (!empty($new_password)) {
                // อัปเดตพร้อมเปลี่ยนรหัสผ่าน
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_sql = "UPDATE users SET username = ?, password = ?, fullname = ?, work_status = ?, 
                              position = ?, agency = ?, role = ? WHERE user_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("sssssssi", $username, $hashed_password, $fullname, $work_status, 
                                        $position, $agency, $role, $edit_user_id);
            } else {
                // อัปเดตโดยไม่เปลี่ยนรหัสผ่าน
                $update_sql = "UPDATE users SET username = ?, fullname = ?, work_status = ?, 
                              position = ?, agency = ?, role = ? WHERE user_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ssssssi", $username, $fullname, $work_status, 
                                        $position, $agency, $role, $edit_user_id);
            }
            
            if ($update_stmt->execute()) {
                $message = '<div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> แก้ไขข้อมูลสมาชิกสำเร็จ!</div>';
            } else {
                $error = 'เกิดข้อผิดพลาด: ' . $update_stmt->error;
            }
            $update_stmt->close();
        }
    }
    
    if (!empty($error)) {
        $message = '<div class="alert alert-danger"><i class="bi bi-x-circle-fill"></i> ' . $error . '</div>';
    }
}

// --- ดึงข้อมูลสมาชิกที่ต้องการแก้ไข ---
$user_sql = "SELECT * FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $edit_user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();

if ($user_result->num_rows === 0) {
    die('<div class="alert alert-danger">ไม่พบข้อมูลสมาชิกที่ต้องการแก้ไข</div>');
}

$user = $user_result->fetch_assoc();
$user_stmt->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขสมาชิก - Digital Signage</title>
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
                <li class="nav-item"><a class="nav-link" href="user_roles.php"><i class="bi bi-key"></i> จัดการสิทธิ์</a></li>
                <li class="nav-item"><a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a></li>
            </ul>
        </div>

        <div class="content-area" id="contentArea">
            <h1 class="mb-4 page-title"><i class="bi bi-person-gear"></i> แก้ไขข้อมูลสมาชิก</h1>
            
            <?php echo $message; ?>

            <div class="card shadow border-0">
                <div class="card-header card-header-custom">
                    <i class="bi bi-person-badge me-2"></i> แก้ไขข้อมูล: <?php echo htmlspecialchars($user['username']); ?>
                </div>
                <div class="card-body">
                    <form method="POST" action="edit_user.php?id=<?php echo $edit_user_id; ?>">
                        
                        <h5 class="border-bottom pb-2 mb-3 text-info">
                            <i class="bi bi-shield-lock"></i> ข้อมูลการเข้าสู่ระบบ
                        </h5>
                        
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="username" class="form-label fw-bold">
                                    <i class="bi bi-person-circle"></i> ชื่อผู้ใช้ (Username) <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                            
                            <div class="col-12 col-md-6">
                                <label for="role" class="form-label fw-bold">
                                    <i class="bi bi-award"></i> สิทธิ์การใช้งาน <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User (ผู้ใช้ทั่วไป)</option>
                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin (ผู้ดูแลระบบ)</option>
                                </select>
                            </div>
                        </div>

                        <div class="alert alert-info mt-3">
                            <i class="bi bi-info-circle"></i> <strong>เปลี่ยนรหัสผ่าน:</strong> หากไม่ต้องการเปลี่ยนรหัสผ่าน ให้เว้นว่างไว้
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="new_password" class="form-label fw-bold">
                                    <i class="bi bi-key"></i> รหัสผ่านใหม่
                                </label>
                                <input type="password" class="form-control" id="new_password" name="new_password" 
                                       placeholder="เว้นว่างหากไม่ต้องการเปลี่ยน" minlength="6">
                                <small class="form-text text-muted">อย่างน้อย 6 ตัวอักษร</small>
                            </div>
                            
                            <div class="col-12 col-md-6">
                                <label for="confirm_password" class="form-label fw-bold">
                                    <i class="bi bi-key-fill"></i> ยืนยันรหัสผ่านใหม่
                                </label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       placeholder="กรอกรหัสผ่านอีกครั้ง" minlength="6">
                            </div>
                        </div>

                        <h5 class="border-bottom pb-2 mb-3 mt-5 text-info">
                            <i class="bi bi-person-vcard"></i> ข้อมูลส่วนตัว
                        </h5>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="fullname" class="form-label fw-bold">
                                    <i class="bi bi-person"></i> ชื่อ-นามสกุล <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="fullname" name="fullname" 
                                       value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="work_status" class="form-label fw-bold">
                                    <i class="bi bi-briefcase"></i> สถานะปฏิบัติงาน
                                </label>
                                <select class="form-select" id="work_status" name="work_status">
                                    <option value="ข้าราชการ" <?php echo $user['work_status'] === 'ข้าราชการ' ? 'selected' : ''; ?>>ข้าราชการ</option>
                                    <option value="พนักงานราชการ" <?php echo $user['work_status'] === 'พนักงานราชการ' ? 'selected' : ''; ?>>พนักงานราชการ</option>
                                    <option value="พนักงานกระทรวงสาธารณสุข" <?php echo $user['work_status'] === 'พนักงานกระทรวงสาธารณสุข' ? 'selected' : ''; ?>>พนักงานกระทรวงสาธารณสุข</option>
                                    <option value="ลูกจ้างประจำ" <?php echo $user['work_status'] === 'ลูกจ้างประจำ' ? 'selected' : ''; ?>>ลูกจ้างประจำ</option>
                                    <option value="ลูกจ้างชั่วคราว" <?php echo $user['work_status'] === 'ลูกจ้างชั่วคราว' ? 'selected' : ''; ?>>ลูกจ้างชั่วคราว</option>
                                    <option value="พนักงานโครงการจ้างงาน" <?php echo $user['work_status'] === 'พนักงานโครงการจ้างงาน' ? 'selected' : ''; ?>>พนักงานโครงการจ้างงาน</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-12 col-md-6">
                                <label for="position" class="form-label fw-bold">
                                    <i class="bi bi-bookmark"></i> ตำแหน่ง
                                </label>
                                <input type="text" class="form-control" id="position" name="position" 
                                       value="<?php echo htmlspecialchars($user['position']); ?>">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="agency" class="form-label fw-bold">
                                    <i class="bi bi-building"></i> หน่วยงาน
                                </label>
                                <input type="text" class="form-control" id="agency" name="agency" 
                                       value="<?php echo htmlspecialchars($user['agency']); ?>">
                            </div>
                        </div>

                        <div class="alert alert-secondary mt-4">
                            <i class="bi bi-calendar-check"></i> <strong>สร้างเมื่อ:</strong> 
                            <?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?>
                        </div>

                        <div class="d-flex flex-column flex-md-row justify-content-end gap-2 pt-4 mt-4 border-top">
                            <button type="submit" name="update_user" class="btn btn-success btn-lg">
                                <i class="bi bi-save"></i> บันทึกการแก้ไข
                            </button>
                            <a href="users.php" class="btn btn-secondary btn-lg">
                                <i class="bi bi-arrow-left-circle"></i> กลับ
                            </a>
                        </div>
                    </form>
                </div>
            </div>
             <div class="footer-content-area">
                <h6>&copy; จัดทำโดย นายฐิติพงศ์ ภาสวร โครงการทดลองจ้างงานบุคคลออทิสติก รุ่นที่13</h6>
            </div> 
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/responsive_sidebar.js"></script>
    <script>
        // ตรวจสอบรหัสผ่านตรงกันหรือไม่
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            // ตรวจสอบเฉพาะเมื่อกรอกรหัสผ่านใหม่
            if (password && password !== confirmPassword) {
                e.preventDefault();
                alert('รหัสผ่านใหม่และยืนยันรหัสผ่านไม่ตรงกัน');
                document.getElementById('confirm_password').focus();
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>

