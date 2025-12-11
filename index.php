<?php
include 'config.php';

// หาก Login แล้ว ให้ Redirect ไปยัง Dashboard ที่เหมาะสม
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/index.php");
    } else {
        header("Location: user/index.php");
    }
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT user_id, password, role, fullname FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // ตรวจสอบรหัสผ่านที่เข้ารหัส (hashing)
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $user['role'];
            $_SESSION['fullname'] = $user['fullname'];
            
            if ($user['role'] === 'admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: user/index.php");
            }
            exit();
        } else {
            $message = '<div class="alert alert-danger">ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง</div>';
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - Digital Signage CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS สำหรับพื้นหลัง: ต้องใช้ภาพ Background Image URL จริงๆ */
        /* เนื่องจากผมไม่สามารถเข้าถึงไฟล์ภาพของคุณได้ กรุณาแทนที่ URL ใน 'url(...)' ด้วย Path/URL ของภาพพื้นหลัง Sunset ของคุณ */
        body { 
            background-image: url('https://picsum.photos/seed/abstractblue/1920/1080'); /* ภาพพื้นหลัง (ตัวอย่าง) */
            background-size: cover; 
            background-position: center; 
            background-attachment: fixed; /* ตรึงพื้นหลัง */
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh;
            margin: 0;
            font-family: 'Sarabun', sans-serif;
        }
        
        /* Card Login Style */
        .auth-card { 
            max-width: 500px; 
            padding: 40px; 
            /* กำหนดพื้นหลังสีขาวและขอบมนเล็กน้อยตามภาพ */
            background: rgba(255, 255, 255, 0.95); /* เกือบโปร่งใสเล็กน้อย */
            border-radius: 12px; 
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.2); 
            width: 100%;
            transition: transform 0.3s ease;
        }
        .auth-card:hover {
            transform: translateY(-5px); /* เลื่อนขึ้นเล็กน้อยเมื่อเมาส์ชี้ */
        }
        .auth-card h2 {
            text-align: center;
            color: #007bff;
            font-weight: 700;
            margin-bottom: 40px;
        }

        
        /* Heading Style */
        .card-title-custom {
            font-size: 1.5rem;
            font-weight: 500;
            margin-bottom: 30px;
        }

        /* Input Group Icon */
        .input-group-text {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
            background-color: #e9ecef;
            border-right: none;
        }
        
        /* Input Field (ลบ input-group-text ออกเพราะภาพไม่มี icon) */
        .form-control {
            height: 45px;
        }

        /* Input field (ตามภาพ) - หากคุณต้องการใช้ icon ให้ใช้โค้ดเดิมที่เคยให้ไว้ */
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
            font-size: 0.95em;
        }
        
        /* Link Text */
        .register-link {
            font-size: 0.95rem;
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="card auth-card text-center">
        <div class="card-body">
            <h2 class="card-title-custom text-primary">เข้าสู่ระบบ Digital Signage CMS</h2>
            <?php echo $message; ?>
            <form action="index.php" method="POST">
                
                <div class="mb-3 text-start">
                    <label for="username" class="form-label">ชื่อผู้ใช้ (Username)</label>
                    <div class="input-group">
                        <span class="input-group-text" id="basic-addon1"><i class="bi bi-lock-fill"></i></span>
                    <input type="text" class="form-control" id="username" name="username" value="INTERN01" required>
                </div>
                </div>
                <div class="mb-4 text-start">
                    <label for="password" class="form-label">รหัสผ่าน (Password)</label>
                    <div class="input-group">
                        <span class="input-group-text" id="basic-addon1"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" class="form-control" id="password" name="password" value="********" required>
                    </div>
                </div>
                
                <button type="submit" name="login" class="btn btn-primary w-100 btn-lg mb-3">เข้าสู่ระบบ</button>
            </form>
            
            <p class="mt-3 text-center register-link">ยังไม่มีบัญชี? <a href="register.php" class="text-primary">ลงทะเบียนที่นี่</a></p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>