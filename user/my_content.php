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
    <title>User Dashboard - Digital Signage</title>
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
            background-color: #343a40;
            color: white;
            padding-top: 20px;
        }
        .sidebar a {
            color: #bdc3c7;
            padding: 10px 15px;
            text-decoration: none;
            display: block;
            transition: all 0.3s;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: #34495e;
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
        
        .user-profile {
            padding: 15px 10px; 
            text-align: center;
            margin-bottom: 5px; 
            background-color: #2c3e50; 
            margin: 0 10px; 
            border-radius: 8px; 
            border: 1px solid #3c546c; 
        }
        .profile-initial {
            width: 60px; 
            height: 60px;
            background-color: #1abc9c; 
            color: white;
            border-radius: 50%;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            font-size: 1.8rem; 
            font-weight: 700;
            margin-bottom: 8px; 
            border: 3px solid #f4f7f6; 
            box-shadow: 0 0 0 2px #1abc9c; 
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
            color: #bdc3c7;
            margin-top: 2px;
            font-style: italic;
        }
        
        .sidebar-divider {
            border: 0;
            height: 1px;
            background-color: #495057; 
            margin: 15px 10px 20px 10px; 
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .dashboard-card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
            text-decoration: none;
            color: #333;
            cursor: pointer;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
            color: #333;
        }

        .dashboard-card i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #1abc9c;
        }

        .dashboard-card h3 {
            font-size: 1.3rem;
            margin-bottom: 10px;
            color: #333;
        }

        .dashboard-card p {
            color: #666;
            font-size: 0.95rem;
            margin: 0;
        }

        .dashboard-card.content {
            border-left: 4px solid #1abc9c;
        }

        .dashboard-card.upload {
            border-left: 4px solid #28a745;
        }

        .dashboard-card.device {
            border-left: 4px solid #007bff;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <div class="sidebar">
            <h4 class="text-center mb-2">🧑‍💻 User Panel</h4>
            
            <div class="user-profile">
                <div class="profile-initial"><?php echo $logged_in_user['profile_initial']; ?></div>
                <p class="profile-name" title="<?php echo $logged_in_user['fullname']; ?>"><?php echo $logged_in_user['fullname']; ?></p>
                <p class="profile-position"><?php echo $logged_in_user['position']; ?></p>
            </div>
            <hr class="sidebar-divider">
            
           <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link active" href="index.php">🏠 Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="my_content.php">📊 Content ของฉัน</a></li>
                <li class="nav-item"><a class="nav-link" href="upload.php">⬆️ อัพโหลด Content ใหม่</a></li>
                <li class="nav-item"><a class="nav-link" href="device_status.php">💻 สถานะอุปกรณ์ที่ได้รับสิทธิ์</a></li>
                <li class="nav-item"><a class="nav-link" href="../logout.php">🚪 ออกจากระบบ</a></li>
            </ul>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>