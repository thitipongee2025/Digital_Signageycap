<?php
// admin/add_user.php - เพิ่มสมาชิกใหม่
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

// --- Process Form Submission ---
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    
    // รับค่าจากฟอร์ม
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $fullname = trim($_POST['fullname']);
    $work_status = $_POST['work_status'];
    $position = trim($_POST['position']);
    $agency = trim($_POST['agency']);
    $role = $_POST['role'];
    
    // Validation
    if (empty($username) || empty($password) || empty($fullname)) {
        $error = 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน';
    } elseif ($password !== $confirm_password) {
        $error = 'รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน';
    } elseif (strlen($password) < 6) {
        $error = 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร';
    } else {
        // ตรวจสอบว่า username ซ้ำหรือไม่
        $check_sql = "SELECT user_id FROM users WHERE username = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = 'ชื่อผู้ใช้นี้มีในระบบแล้ว กรุณาเลือกชื่อผู้ใช้อื่น';
            $check_stmt->close();
        } else {
            $check_stmt->close();
            
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert ข้อมูล
            $insert_sql = "INSERT INTO users (username, password, fullname, work_status, position, agency, role, created_at) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sssssss", $username, $hashed_password, $fullname, $work_status, $position, $agency, $role);
            
            if ($insert_stmt->execute()) {
                $message = '<div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> เพิ่มสมาชิกสำเร็จ!</div>';
                // Clear form
                $username = $fullname = $work_status = $position = $agency = '';
            } else {
                $error = 'เกิดข้อผิดพลาด: ' . $insert_stmt->error;
            }
            $insert_stmt->close();
        }
    }
    
    if (!empty($error)) {
        $message = '<div class="alert alert-danger"><i class="bi bi-x-circle-fill"></i> ' . $error . '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มสมาชิก - Digital Signage</title>
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
                <li class="nav-item"><a class="nav-link" href="devices.php"><i class="bi bi-tv"></i> จัดการอุปกรณ์</a></li>
                <li class="nav-item"><a class="nav-link" href="users.php"><i class="bi bi-people"></i> จัดการสมาชิก</a></li>
                <li class="nav-item"><a class="nav-link active" href="add_user.php"><i class="bi bi-people"></i> ลงทะเบียนสมาชิก</a></li>
                <li class="nav-item"><a class="nav-link" href="user_roles.php"><i class="bi bi-key"></i> จัดการสิทธิ์</a></li>
                <li class="nav-item"><a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a></li>
            </ul>
        </div>

        <div class="content-area" id="contentArea">
            <h1 class="mb-4 page-title"><i class="bi bi-person-plus-fill"></i> เพิ่มสมาชิกใหม่</h1>
            
            <?php echo $message; ?>

            <div class="card shadow border-0">
                <div class="card-header card-header-custom">
                    <i class="bi bi-person-badge me-2"></i> ข้อมูลสมาชิก
                </div>
                <div class="card-body">
                    <form method="POST" action="add_user.php">
                        
                        <h5 class="border-bottom pb-2 mb-3 text-info">
                            <i class="bi bi-shield-lock"></i> ข้อมูลการเข้าสู่ระบบ
                        </h5>
                        
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label for="username" class="form-label fw-bold">
                                    <i class="bi bi-person-circle"></i> ชื่อผู้ใช้ (Username) <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" 
                                       placeholder="ตัวอย่าง: john.doe" required>
                                <small class="form-text text-muted">ใช้สำหรับเข้าสู่ระบบ</small>
                            </div>
                            
                            <div class="col-12 col-md-6">
                                <label for="role" class="form-label fw-bold">
                                    <i class="bi bi-award"></i> สิทธิ์การใช้งาน <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="user" selected>User (ผู้ใช้ทั่วไป)</option>
                                    <option value="admin">Admin (ผู้ดูแลระบบ)</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-12 col-md-6">
                                <label for="password" class="form-label fw-bold">
                                    <i class="bi bi-key"></i> รหัสผ่าน <span class="text-danger">*</span>
                                </label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="อย่างน้อย 6 ตัวอักษร" minlength="6" required>
                            </div>
                            
                            <div class="col-12 col-md-6">
                                <label for="confirm_password" class="form-label fw-bold">
                                    <i class="bi bi-key-fill"></i> ยืนยันรหัสผ่าน <span class="text-danger">*</span>
                                </label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       placeholder="กรอกรหัสผ่านอีกครั้ง" minlength="6" required>
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
                                       value="<?php echo isset($fullname) ? htmlspecialchars($fullname) : ''; ?>" 
                                       placeholder="ตัวอย่าง: นายสมชาย ใจดี" required>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="work_status" class="form-label fw-bold">
                                    <i class="bi bi-briefcase"></i> สถานะปฏิบัติงาน
                                </label>
                                <select class="form-select" id="work_status" name="work_status">
                                    <option value="ข้าราชการ">ข้าราชการ</option>
                                    <option value="พนักงานราชการ">พนักงานราชการ</option>
                                    <option value="พนักงานกระทรวงสาธารณสุข">พนักงานกระทรวงสาธารณสุข</option>
                                    <option value="ลูกจ้างประจำ">ลูกจ้างประจำ</option>
                                    <option value="ลูกจ้างชั่วคราว">ลูกจ้างชั่วคราว</option>
                                    <option value="พนักงานโครงการจ้างงาน">พนักงานโครงการจ้างงาน</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-12 col-md-6">
                                <label for="position" class="form-label fw-bold">
                                    <i class="bi bi-bookmark"></i> ตำแหน่ง
                                </label>
                                <input type="text" class="form-control" id="position" name="position" 
                                       value="<?php echo isset($position) ? htmlspecialchars($position) : ''; ?>" 
                                       placeholder="ตัวอย่าง: นักวิชาการคอมพิวเตอร์">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="agency" class="form-label fw-bold">
                                    <i class="bi bi-building"></i> หน่วยงาน
                                </label>
                                <input type="text" class="form-control" id="agency" name="agency" 
                                       value="<?php echo isset($agency) ? htmlspecialchars($agency) : ''; ?>" 
                                       placeholder="ตัวอย่าง: กองเทคโนโลยีสารสนเทศ">
                            </div>
                        </div>

                        <div class="d-flex flex-column flex-md-row justify-content-end gap-2 pt-4 mt-4 border-top">
                            <button type="submit" name="add_user" class="btn btn-success btn-lg">
                                <i class="bi bi-person-plus"></i> เพิ่มสมาชิก
                            </button>
                            <a href="users.php" class="btn btn-secondary btn-lg">
                                <i class="bi bi-x-circle"></i> ยกเลิก
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
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน');
                document.getElementById('confirm_password').focus();
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>