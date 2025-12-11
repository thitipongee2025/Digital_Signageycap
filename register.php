<?php
include 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $fullname = $_POST['fullname'];
    $work_status = $_POST['work_status'];
    $position = $_POST['position'];
    $agency = $_POST['agency'];
    $role = $_POST['role']; // รับค่าสถานะผู้ใช้งาน (Admin/User)

    if ($password !== $confirm_password) {
        $message = '<div class="alert alert-danger">รหัสผ่านที่กรอกไม่ตรงกัน</div>';
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // ตรวจสอบชื่อผู้ใช้ซ้ำ
        $check_sql = "SELECT user_id FROM users WHERE username = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $message = '<div class="alert alert-danger">ชื่อผู้ใช้ **' . htmlspecialchars($username) . '** มีอยู่ในระบบแล้ว</div>';
        } else {
            // เพิ่มสมาชิกใหม่พร้อมข้อมูลเพิ่มเติม
            $sql = "INSERT INTO users (username, password, fullname, work_status, position, agency, role) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssss", $username, $hashed_password, $fullname, $work_status, $position, $agency, $role);
            
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ</div>';
            } else {
                $message = '<div class="alert alert-danger">เกิดข้อผิดพลาดในการสมัครสมาชิก: ' . $stmt->error . '</div>';
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}

// ตัวเลือกสถานะผู้ปฏิบัติงาน
$work_status_options = [
    'ข้าราชการ',
    'พนักงานราชการ',
    'พนักงานกระทรวงสาธารณสุข',
    'ลูกจ้างชั่วคราว',
    'ลูกจ้างประจำ',
    'พนักงานโครงการจ้างงาน'
];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - Digital Signage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body { background: #f4f7f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .auth-card { max-width: 600px; }
    </style>
</head>
<body>
    <div class="card auth-card my-4">
        <div class="card-body">
            <h3 class="card-title text-center mb-4">📝 ลงทะเบียนสมาชิก</h3>
            <?php echo $message; ?>
            <form action="register.php" method="POST">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="fullname" class="form-label">ชื่อ-นามสกุล</label>
                        <input type="text" class="form-control" id="fullname" name="fullname" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="work_status" class="form-label">สถานะผู้ปฏิบัติงาน</label>
                        <select class="form-select" id="work_status" name="work_status" required>
                            <?php foreach ($work_status_options as $status): ?>
                                <option value="<?php echo $status; ?>"><?php echo $status; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="position" class="form-label">ตำแหน่ง</label>
                        <input type="text" class="form-control" id="position" name="position" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="agency" class="form-label">หน่วยงาน</label>
                        <input type="text" class="form-control" id="agency" name="agency" required>
                    </div>
                </div>

                <hr>

                <div class="mb-3">
                    <label for="username" class="form-label">ชื่อผู้ใช้ (Username)</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">รหัสผ่าน</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="confirm_password" class="form-label">ยืนยันรหัสผ่านอีกครั้ง</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="role" class="form-label">สถานะผู้ใช้งาน (Role)</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="user">User (ผู้ใช้งานทั่วไป)</option>
                        <option value="admin">Admin (ผู้ดูแลระบบ)</option>
                    </select>
                    <small class="form-text text-danger">การเลือก Admin ควรได้รับอนุมัติจากผู้ดูแลสูงสุด</small>
                </div>

                <button type="submit" name="register" class="btn btn-success w-100 mt-3">สมัครสมาชิก</button>
            </form>
            <p class="mt-3 text-center">มีบัญชีอยู่แล้ว? <a href="index.php">เข้าสู่ระบบ</a></p>
        </div>
    </div>
    <script src="assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>