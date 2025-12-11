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
    
    // แก้ไข SQL: ตัด LEFT JOIN กับ user_roles ออก เพื่อแก้ไข Fatal Error ชั่วคราว
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
        
        // กำหนดตำแหน่งเป็นค่าเริ่มต้น "ผู้ดูแลระบบ" 
        $logged_in_user['position'] = htmlspecialchars($user_data['position']);
        
        // สร้างอักษรย่อสำหรับแสดงผลในวงกลม
        $initials = '';
        if (!empty($user_data['fullname'])) {
            $parts = explode(' ', trim($user_data['fullname']));
            $initials = mb_substr($parts[0], 0, 1, 'UTF-8'); 
        }
        $logged_in_user['profile_initial'] = empty($initials) ? 'U' : $initials;
    }
    $user_stmt->close();
}
// --- [สิ้นสุดส่วนดึงข้อมูล] ---


// ดึงข้อมูลสรุปสำหรับ Admin Dashboard
$total_users = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$total_devices = $conn->query("SELECT COUNT(*) FROM devices")->fetch_row()[0];
// นับ Content ที่อยู่ในช่วงเวลาแสดงผลปัจจุบัน
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
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f4f7f6;
        }
        .sidebar {
            position: fixed;
            height: 100vh;
            width: 250px;
            background-image: linear-gradient( 0deg , #006622ff, #00998cff );
            color: white;
            padding-top: 20px;
        }
        .sidebar a {
            color: #ffffffff;
            padding: 10px 15px;
            text-decoration: none;
            display: block;
            transition: all 0.3s;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: #009999ff;
            color: white;
            border-left: 4px solid #1abc9c;
        }
        .content-area {
            margin-left: 250px;
            width: calc(100% - 250px);
            min-height: 100vh;
            padding: 40px;
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

        /* --- [CSS ที่แก้ไขสำหรับ Profile] --- */
        .user-profile {
            padding: 15px 10px; /* ลด padding แนวข้าง */
            text-align: center;
            margin-bottom: 5px; 
            background-image: linear-gradient(0deg , #060041ff, #685abdff);
            margin: 0 10px; 
            border-radius: 8px; 
            border: 1px solid #3c546c; /* เพิ่มเส้นขอบบางๆ ให้ดูเป็นกรอบ */
        }
        .profile-initial {
            width: 60px; 
            height: 60px;
            background-color: #1abc9c; /* สีเขียวเด่น */
            color: white;
            border-radius: 50%;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            font-size: 1.8rem; 
            font-weight: 700;
            margin-bottom: 8px; /* ลด margin-bottom */
            border: 3px solid #f4f7f6; 
            box-shadow: 0 0 0 2px #1abc9c; /* เงารอบวงกลม */
        }
        .profile-name {
            font-weight: 700;
            margin: 0;
            font-size: 1.1rem;
            color: #ecf0f1; 
            white-space: nowrap; 
            overflow: hidden; 
            text-overflow: ellipsis; 
        }
        .profile-position {
            font-size: 0.85rem;
            color: #ffffffff;
            margin-top: 2px;
        }
        /* --- [สิ้นสุด CSS ที่แก้ไข] --- */
        
        /* สไตล์สำหรับเส้นแบ่ง */
        .sidebar-divider {
            border: 0;
            height: 1px;
            background-color: #ebfddcff; 
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <div class="sidebar">
            <h6 class="text-center mb-2">📺ระบบจอประชาสัมพันธ์</h6>
            <hr class="sidebar-divider">
    <div class="user-profile">
                <div class="profile-initial"><?php echo $logged_in_user['profile_initial']; ?></div>
                <p class="profile-name" title="<?php echo $logged_in_user['fullname']; ?>"><?php echo $logged_in_user['fullname']; ?></p>
                <p class="profile-position" title="<?php echo $logged_in_user['position']; ?>"><?php echo $logged_in_user['position']; ?></p>
            </div>
            <hr class="sidebar-divider">
            
            <ul class="nav flex-column">
               <li class="nav-item"><a class="nav-link active" href="index.php">📊 Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="contents.php">📂 จัดการ Content</a></li>
                <li class="nav-item"><a class="nav-link" href="devices.php">💻 จัดการอุปกรณ์</a></li>
                <li class="nav-item"><a class="nav-link" href="users.php">👥 จัดการสมาชิก</a></li>
                <li class="nav-item"><a class="nav-link" href="user_roles.php">🔑 จัดการสิทธิ์</a></li>
                <li class="nav-item"><a class="nav-link" href="../logout.php">🚪 ออกจากระบบ</a></li>
            </ul>
        </div>

        <div class="content-area">
            <h1 class="mb-4 text-primary"><i class="bi bi-speedometer2"></i> ระบบจัดการจอประชาสัมพันธ์</h1>
            
            <div class="container-fluid p-0">
                <div class="row g-4 mb-5">
                    
                    <div class="col-md-4">
                        <div class="card stat-card border-primary-custom shadow">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        <h5 class="card-title">อุปกรณ์ทั้งหมด</h5>
                                        <p class="card-text fs-1 mb-0"><?php echo $total_devices; ?></p>
                                    </div>
                                    <div class="col-4 text-end"><i class="bi bi-tv card-icon text-primary-custom"></i></div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="devices.php" class="text-decoration-none text-primary-custom">จัดการอุปกรณ์ &rarr;</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card stat-card border-success-custom shadow">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        <h5 class="card-title">สมาชิกทั้งหมด</h5>
                                        <p class="card-text fs-1 mb-0"><?php echo $total_users; ?></p>
                                    </div>
                                    <div class="col-4 text-end"><i class="bi bi-people-fill card-icon text-success-custom"></i></div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="users.php" class="text-decoration-none text-success-custom">จัดการสมาชิก &rarr;</a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card stat-card border-warning-custom shadow">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        <h5 class="card-title">Content ที่แสดงผล</h5>
                                        <p class="card-text fs-1 mb-0"><?php echo $active_content; ?></p>
                                    </div>
                                    <div class="col-4 text-end"><i class="bi bi-play-circle-fill card-icon text-warning-custom"></i></div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="contents.php" class="text-decoration-none text-warning-custom">จัดการ Content &rarr;</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header card-header-custom">
                                Content ที่อัพโหลดล่าสุด
                            </div>
                            <div class="card-body">
                                <p>ตาราง Content ล่าสุดจะแสดงที่นี่...</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
           
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>