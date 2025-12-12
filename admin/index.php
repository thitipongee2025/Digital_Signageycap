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

// ดึงข้อมูลสรุปสำหรับ Admin Dashboard
$total_users = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$total_devices = $conn->query("SELECT COUNT(*) FROM devices")->fetch_row()[0];
$active_content_sql = "
    SELECT COUNT(*) 
    FROM contents 
    WHERE 
        (start_date IS NULL OR start_date <= NOW()) 
        AND (end_date IS NULL OR end_date >= NOW())
";
$active_content = $conn->query($active_content_sql)->fetch_row()[0];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Digital Signage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/responsive_admin.css" rel="stylesheet">
   <style>
        /* Inline Styles สำหรับหน้านี้โดยเฉพาะ */
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f4f7f6;
        }
        .card-header-custom {
            background-color: #1abc9c;
            color: white;
            font-weight: 600;
        }
        .stat-card {
            border-left: 4px solid;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .card-icon {
            font-size: 3rem;
            opacity: 0.6;
        }
        .border-primary-custom { border-left-color: #007bff !important; }
        .border-success-custom { border-left-color: #28a745 !important; }
        .border-warning-custom { border-left-color: #ffc107 !important; }
        .text-primary-custom { color: #007bff !important; }
        .text-success-custom { color: #28a745 !important; }
        .text-warning-custom { color: #ffc107 !important; }
    </style>
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
                <li class="nav-item"><a class="nav-link active" href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="contents.php"><i class="bi bi-folder2-open"></i> จัดการ Content</a></li>
                <li class="nav-item"><a class="nav-link" href="devices.php"><i class="bi bi-tv"></i> จัดการอุปกรณ์</a></li>
                <li class="nav-item"><a class="nav-link" href="users.php"><i class="bi bi-people"></i> จัดการสมาชิก</a></li>
                <li class="nav-item"><a class="nav-link" href="add_user.php"><i class="bi bi-people"></i> ลงทะเบียนสมาชิก</a></li>
                <li class="nav-item"><a class="nav-link" href="user_roles.php"><i class="bi bi-key"></i> จัดการสิทธิ์</a></li>
                <li class="nav-item"><a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a></li>
            </ul>
        </div>


        <!-- Main Content Area -->
        <div class="content-area">
            <h1 class="mb-4 text-primary">
                <i class="bi bi-speedometer2"></i> ระบบจัดการจอประชาสัมพันธ์
            </h1>
            
            <div class="container-fluid p-0">
                <!-- Statistics Cards Row -->
                <div class="row g-4 mb-5">
                    <!-- Card 1: Devices -->
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="card stat-card border-primary-custom shadow">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        <h5 class="card-title">อุปกรณ์ทั้งหมด</h5>
                                        <p class="card-text fs-1 mb-0"><?php echo $total_devices; ?></p>
                                    </div>
                                    <div class="col-4 text-end">
                                        <i class="bi bi-tv card-icon text-primary-custom"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="devices.php" class="text-decoration-none text-primary-custom">
                                    จัดการอุปกรณ์ &rarr;
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Users -->
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="card stat-card border-success-custom shadow">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        <h5 class="card-title">สมาชิกทั้งหมด</h5>
                                        <p class="card-text fs-1 mb-0"><?php echo $total_users; ?></p>
                                    </div>
                                    <div class="col-4 text-end">
                                        <i class="bi bi-people-fill card-icon text-success-custom"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="users.php" class="text-decoration-none text-success-custom">
                                    จัดการสมาชิก &rarr;
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Active Content -->
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="card stat-card border-warning-custom shadow">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        <h5 class="card-title">Content ที่แสดงผล</h5>
                                        <p class="card-text fs-1 mb-0"><?php echo $active_content; ?></p>
                                    </div>
                                    <div class="col-4 text-end">
                                        <i class="bi bi-play-circle-fill card-icon text-warning-custom"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="contents.php" class="text-decoration-none text-warning-custom">
                                    จัดการ Content &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Content Table -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header card-header-custom">
                                <i class="bi bi-clock-history me-2"></i>
                                Content ที่อัพโหลดล่าสุด
                            </div>
                            <div class="card-body">
                                <p class="text-muted">ตาราง Content ล่าสุดจะแสดงที่นี่...</p>
                            </div>
                           
                        </div>
                    </div>
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