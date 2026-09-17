<artifact identifier="digital-signage-readme" type="application/vnd.ant.code" language="markdown" title="README.md - Digital Signage System">
# 📺 Digital Signage System - ระบบจัดการจอประชาสัมพันธ์

> ระบบจัดการจอประชาสัมพันธ์ดิจิทัล สำหรับองค์กรและหน่วยงาน พัฒนาโดย นายฐิติพงศ์ ภาสวร โครงการทดลองจ้างงานบุคคลออทิสติก รุ่นที่13

## 📋 สารบัญ

1. [เกี่ยวกับระบบ](#เกี่ยวกับระบบ)
2. [ฟีเจอร์หลัก](#ฟีเจอร์หลัก)
3. [ความต้องการของระบบ](#ความต้องการของระบบ)
4. [การติดตั้ง](#การติดตั้ง)
5. [การกำหนดค่า](#การกำหนดค่า)
6. [โครงสร้างไฟล์](#โครงสร้างไฟล์)
7. [คู่มือการใช้งาน](#คู่มือการใช้งาน)
8. [การแก้ไขปัญหา](#การแก้ไขปัญหา)
9. [FAQ](#faq)
10. [การสนับสนุน](#การสนับสนุน)

---

## 📖 เกี่ยวกับระบบ

Digital Signage System เป็นระบบจัดการจอประชาสัมพันธ์ดิจิทัลแบบ Web-based ที่ออกแบบมาเพื่อให้การจัดการเนื้อหา (Content Management) และการแสดงผลบนหน้าจอหลายจุดเป็นไปอย่างสะดวกและมีประสิทธิภาพ

### วัตถุประสงค์
- จัดการเนื้อหาประชาสัมพันธ์แบบรวมศูนย์
- รองรับการแสดงผลหลายจอพร้อมกัน
- ควบคุมสิทธิ์การเข้าถึงตามระดับผู้ใช้งาน
- รองรับการทำงานบนอุปกรณ์มือถือ (Responsive Design)

### เทคโนโลยีที่ใช้
- **Backend:** PHP 7.4+
- **Database:** MySQL/MariaDB
- **Frontend:** HTML5, CSS3, JavaScript
- **Framework:** Bootstrap 5.3
- **Icons:** Bootstrap Icons

---

## ✨ ฟีเจอร์หลัก

### 🔐 ระบบผู้ใช้งาน
- **2 ระดับผู้ใช้:** Admin และ User
- ระบบล็อกอิน/ลงทะเบียนที่ปลอดภัย (Password Hashing)
- จัดการโปรไฟล์ผู้ใช้และข้อมูลส่วนตัว

### 📁 จัดการ Content
- **อัพโหลดไฟล์ได้หลายประเภท:**
  - รูปภาพ: JPG, JPEG, PNG, GIF
  - วิดีโอ: MP4, WebM, OGG
- กำหนดระยะเวลาแสดงผล
- ตั้งวันเวลาเริ่มต้นและสิ้นสุดการแสดง
- เลือกอุปกรณ์ที่จะแสดงผลได้แบบเฉพาะเจาะจง

### 🖥️ จัดการอุปกรณ์
- เพิ่ม/ลบ/แก้ไข อุปกรณ์แสดงผล
- ติดตามสถานะ Online/Offline แบบ Real-time
- จัดการ Playlist สำหรับแต่ละอุปกรณ์
- ระบบ Heartbeat เพื่อตรวจสอบสถานะการทำงาน

### 👥 จัดการสิทธิ์
- กำหนดสิทธิ์การเข้าถึงอุปกรณ์สำหรับแต่ละ User
- ระบบป้องกันการแก้ไข/ลบ Content โดยไม่มีสิทธิ์

### 📊 Dashboard
- **Admin Dashboard:**
  - ภาพรวมจำนวนอุปกรณ์
  - สถิติผู้ใช้งาน
  - Content ที่กำลังแสดงผล
- **User Dashboard:**
  - Content ของตัวเอง
  - สถานะอุปกรณ์ที่มีสิทธิ์
  - การอัพโหลดใหม่

### 🎬 ระบบ Playlist
- เล่น Content แบบวนลูป (Loop)
- รองรับทั้งภาพและวิดีโอ
- ปรับแสดงผลอัตโนมัติตามขนาดเนื้อหา (Landscape/Portrait)
- โหมดเต็มจอ (Fullscreen)

### 📱 Responsive Design
- รองรับทุกขนาดหน้าจอ
- Mobile-First Design
- Sidebar พับได้บนมือถือ
- ตารางปรับเป็น Card View บนจอเล็ก

---

## 💻 ความต้องการของระบบ

### Server Requirements
- **Web Server:** Apache 2.4+ / Nginx
- **PHP:** 7.4 หรือสูงกว่า
- **Database:** MySQL 5.7+ / MariaDB 10.2+
- **PHP Extensions:**
  - mysqli
  - pdo_mysql
  - gd (สำหรับการจัดการรูปภาพ)
  - mbstring (สำหรับภาษาไทย)

### Client Requirements
- **Browser:** Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
- **Resolution:** 1024x768 ขึ้นไป (แนะนำ 1920x1080)
- **Internet Connection:** ขั้นต่ำ 2 Mbps

### Recommended Specifications
- **RAM:** 2GB ขึ้นไป
- **Storage:** 10GB สำหรับไฟล์ Content
- **CPU:** Dual-core 2.0GHz ขึ้นไป

---

## 🚀 การติดตั้ง

### 1. ติดตั้ง XAMPP/WAMP
1. ดาวน์โหลด [XAMPP](https://www.apachefriends.org/) หรือ [WAMP](https://www.wampserver.com/)
2. ติดตั้งตามขั้นตอนปกติ
3. เริ่มต้น Apache และ MySQL/MariaDB

### 2. สร้างฐานข้อมูล
```sql
-- เข้า phpMyAdmin (http://localhost/phpmyadmin)
-- สร้างฐานข้อมูลใหม่ชื่อ: digital_signage_db
CREATE DATABASE digital_signage_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. นำเข้าโครงสร้างฐานข้อมูล
```sql
USE digital_signage_db;

-- ตาราง users
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `work_status` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `agency` varchar(100) DEFAULT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตาราง devices
CREATE TABLE `devices` (
  `device_id` int(11) NOT NULL AUTO_INCREMENT,
  `device_name` varchar(100) NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `status` enum('online','offline') DEFAULT 'offline',
  `last_active` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตาราง contents
CREATE TABLE `contents` (
  `content_id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `filepath` varchar(255) NOT NULL,
  `content_type` enum('image','video') NOT NULL,
  `duration_seconds` int(11) DEFAULT 10,
  `upload_by` int(11) DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`content_id`),
  KEY `upload_by` (`upload_by`),
  CONSTRAINT `contents_ibfk_1` FOREIGN KEY (`upload_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตาราง device_content
CREATE TABLE `device_content` (
  `dc_id` int(11) NOT NULL AUTO_INCREMENT,
  `device_id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  PRIMARY KEY (`dc_id`),
  KEY `device_id` (`device_id`),
  KEY `content_id` (`content_id`),
  CONSTRAINT `device_content_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`device_id`) ON DELETE CASCADE,
  CONSTRAINT `device_content_ibfk_2` FOREIGN KEY (`content_id`) REFERENCES `contents` (`content_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตาราง user_permissions
CREATE TABLE `user_permissions` (
  `up_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `can_upload` tinyint(1) DEFAULT 1,
  `can_delete` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`up_id`),
  UNIQUE KEY `user_device_unique` (`user_id`, `device_id`),
  KEY `user_id` (`user_id`),
  KEY `device_id` (`device_id`),
  CONSTRAINT `user_permissions_ibfk_1` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `user_permissions_ibfk_2` FOREIGN KEY (`device_id`) 
    REFERENCES `devices` (`device_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- สร้าง Admin User เริ่มต้น (username: admin, password: admin123)
INSERT INTO `users` (`username`, `password`, `fullname`, `role`) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ผู้ดูแลระบบ', 'admin');
```

### 4. วาง Source Code
1. คัดลอกโฟลเดอร์โปรเจค
2. วางไว้ที่ `C:\xampp\htdocs\Digital_Signageycap` (สำหรับ XAMPP)
3. หรือ `C:\wamp64\www\Digital_Signageycap` (สำหรับ WAMP)

### 5. ตรวจสอบสิทธิ์โฟลเดอร์
```bash
# สร้างโฟลเดอร์สำหรับอัพโหลดไฟล์
mkdir assets/uploads
# กำหนดสิทธิ์ให้สามารถเขียนไฟล์ได้
chmod 777 assets/uploads  # Linux/Mac
# หรือคลิกขวา > Properties > Security (Windows)
```

---

## ⚙️ การกำหนดค่า

### ไฟล์ `config.php`
แก้ไขการเชื่อมต่อฐานข้อมูลตามค่าจริงของคุณ:
```php
<?php
session_start();

// ข้อมูลการเชื่อมต่อฐานข้อมูล
$servername = "localhost";
$username = "root";           // เปลี่ยนเป็น username ของคุณ
$password = "";               // เปลี่ยนเป็น password ของคุณ (XAMPP ค่าเริ่มต้นคือเว้นว่าง)
$dbname = "digital_signage_db";
$port = 3306;

// สร้างการเชื่อมต่อ
$conn = @new mysqli($servername, $username, $password, $dbname, $port);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

// กำหนด UTF-8
$conn->set_charset("utf8mb4");

// ฟังก์ชันตรวจสอบสิทธิ์
function checkAdminLogin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../index.php");
        exit();
    }
}

function checkUserLogin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
        header("Location: ../index.php");
        exit();
    }
}
?>
```

### การตั้งค่า PHP (php.ini)
```ini
; เพิ่มขนาดไฟล์สูงสุดที่อัพโหลดได้
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
memory_limit = 256M

; เปิดใช้งาน Extension ที่จำเป็น
extension=mysqli
extension=mbstring
extension=gd
```

---

## 📂 โครงสร้างไฟล์
```
Digital_Signageycap/
│
├── admin/                          # ส่วนของ Admin
│   ├── index.php                   # Dashboard
│   ├── contents.php                # จัดการ Content
│   ├── add_content.php             # เพิ่ม Content
│   ├── edit_content.php            # แก้ไข Content
│   ├── delete_content.php          # ลบ Content
│   ├── devices.php                 # จัดการอุปกรณ์
│   ├── device_playlist.php         # Playlist ของอุปกรณ์
│   ├── users.php                   # จัดการสมาชิก
│   ├── add_user.php                # เพิ่มสมาชิก
│   ├── edit_user.php               # แก้ไขสมาชิก
│   ├── user_roles.php              # จัดการสิทธิ์
│   └── update_device_heartbeat.php # อัพเดทสถานะอุปกรณ์
│
├── user/                           # ส่วนของ User
│   ├── index.php                   # Dashboard
│   ├── my_content.php              # Content ของฉัน
│   ├── upload.php                  # อัพโหลด Content
│   ├── edit_content.php            # แก้ไข Content
│   ├── delete_content.php          # ลบ Content
│   ├── device_status.php           # สถานะอุปกรณ์
│   ├── playlist_preview.php        # ดู Playlist
│   └── update_device_heartbeat.php # อัพเดทสถานะอุปกรณ์
│
├── assets/                         # ไฟล์ Static
│   ├── css/
│   │   ├── admin.css               # สไตล์ Admin
│   │   ├── user-panel.css          # สไตล์ User
│   │   ├── user-upload.css         # สไตล์หน้าอัพโหลด
│   │   ├── login.css               # สไตล์หน้า Login
│   │   ├── register.css            # สไตล์หน้าลงทะเบียน
│   │   └── playlist_preview.css    # สไตล์ Playlist
│   ├── js/
│   │   ├── script.js               # JavaScript หลัก
│   │   ├── responsive_sidebar.js   # JavaScript Sidebar
│   │   ├── sidebar-menu.js         # JavaScript เมนู
│   │   └── user-upload.js          # JavaScript อัพโหลด
│   └── uploads/                    # โฟลเดอร์เก็บไฟล์ที่อัพโหลด
│
├── config.php                      # การตั้งค่าระบบ
├── index.php                       # หน้า Login
├── register.php                    # หน้าลงทะเบียน
├── logout.php                      # ออกจากระบบ
└── README.md                       # ไฟล์นี้
```

---

## 📖 คู่มือการใช้งาน

### สำหรับ Admin

#### 1. เข้าสู่ระบบ
1. เปิดเว็บเบราว์เซอร์ไปที่: `http://localhost/Digital_Signageycap`
2. กรอก Username: `admin`
3. กรอก Password: `admin123` (หรือตามที่คุณตั้งไว้)
4. คลิก "เข้าสู่ระบบ"

#### 2. จัดการอุปกรณ์
1. คลิกเมนู "จัดการอุปกรณ์"
2. **เพิ่มอุปกรณ์ใหม่:**
   - กรอกชื่ออุปกรณ์ (เช่น "จอชั้น 1")
   - กรอกตำแหน่งติดตั้ง (เช่น "ห้องโถง")
   - คลิก "บันทึกอุปกรณ์"
3. **ดู Playlist:**
   - คลิกปุ่ม "ดู Playlist" ที่อุปกรณ์ที่ต้องการ
   - ระบบจะแสดง Content ที่กำลังเล่นบนอุปกรณ์นั้น
4. **ลบอุปกรณ์:**
   - คลิกปุ่ม "ลบ" (ระวัง: จะลบ Content ที่เกี่ยวข้องด้วย)

#### 3. จัดการ Content
1. คลิกเมนู "จัดการ Content"
2. **อัพโหลด Content ใหม่:**
   - คลิก "อัพโหลด Content ใหม่"
   - เลือกไฟล์ (รูปภาพหรือวิดีโอ)
   - กำหนดเวลาเล่น (วินาที)
   - เลือกวันเวลาเริ่มต้น-สิ้นสุด (ถ้าต้องการ)
   - เลือกอุปกรณ์ที่จะแสดงผล
   - คลิก "อัพโหลดและบันทึก"
3. **แก้ไข Content:**
   - คลิกปุ่ม "แก้ไข" ที่ Content ที่ต้องการ
   - แก้ไขรายละเอียดตามต้องการ
   - คลิก "บันทึกการแก้ไข"
4. **ลบ Content:**
   - คลิกปุ่ม "ลบ"
   - ยืนยันการลบ

#### 4. จัดการสมาชิก
1. คลิกเมนู "จัดการสมาชิก"
2. **เพิ่มสมาชิกใหม่:**
   - คลิก "เพิ่มสมาชิกใหม่"
   - กรอกข้อมูลที่จำเป็น
   - เลือกสิทธิ์ (Admin/User)
   - คลิก "เพิ่มสมาชิก"
3. **แก้ไขสมาชิก:**
   - คลิกปุ่ม "แก้ไข"
   - แก้ไขข้อมูลตามต้องการ
   - คลิก "บันทึกการแก้ไข"
4. **ลบสมาชิก:**
   - คลิกปุ่ม "ลบ"
   - ยืนยันการลบ

#### 5. จัดการสิทธิ์
1. คลิกเมนู "จัดการสิทธิ์"
2. เลือก User ที่ต้องการกำหนดสิทธิ์
3. เลือกอุปกรณ์ที่ User นั้นสามารถจัดการได้ (ติ๊กหลายอุปกรณ์ได้)
4. คลิก "บันทึกสิทธิ์"

### สำหรับ User

#### 1. เข้าสู่ระบบ
1. ใช้ Username และ Password ที่ได้รับจาก Admin
2. คลิก "เข้าสู่ระบบ"

#### 2. อัพโหลด Content
1. คลิกเมนู "อัพโหลด Content"
2. เลือกไฟล์ (รูปภาพหรือวิดีโอ)
3. กำหนดเวลาเล่น
4. เลือกวันเวลาเริ่มต้น-สิ้นสุด (ถ้าต้องการ)
5. เลือกอุปกรณ์:
   - **ทุกอุปกรณ์:** เลือก "เล่นบนทุกอุปกรณ์ที่ได้รับสิทธิ์"
   - **เฉพาะอุปกรณ์:** กด Ctrl (Windows) หรือ Cmd (Mac) + คลิกเลือกอุปกรณ์
6. คลิก "อัพโหลดและบันทึก"

#### 3. จัดการ Content ของตัวเอง
1. คลิกเมนู "Content ของฉัน"
2. ดูรายการ Content ที่เคยอัพโหลด
3. **แก้ไข:** คลิกปุ่ม "แก้ไข" แล้วแก้ไขรายละเอียด
4. **ลบ:** คลิกปุ่ม "ลบ" แล้วยืนยัน

#### 4. ตรวจสอบสถานะอุปกรณ์
1. คลิกเมนู "สถานะอุปกรณ์"
2. ดูสถานะ Online/Offline ของแต่ละอุปกรณ์
3. คลิก "ดูเพลยลิสต์" เพื่อดู Content ที่กำลังเล่น

### การแสดงผลบนจอจริง

#### วิธีที่ 1: ใช้หน้า Playlist (แนะนำ)
1. เปิดเบราว์เซอร์บนเครื่อง/อุปกรณ์ที่เชื่อมต่อกับจอ
2. ไปที่: `http://localhost/Digital_Signageycap/admin/device_playlist.php?device_id=[ID]`
   - แทน `[ID]` ด้วย ID ของอุปกรณ์ (ดูได้จากหน้าจัดการอุปกรณ์)
   - ตัวอย่าง: `device_playlist.php?device_id=1`
3. คลิกปุ่ม "เต็มจอ" เพื่อแสดงผลแบบเต็มหน้าจอ
4. Content จะเล่นอัตโนมัติแบบวนลูป

#### วิธีที่ 2: ตั้งค่าให้เปิดอัตโนมัติ
1. **Windows:**
   - กด Win + R
   - พิมพ์ `shell:startup`
   - สร้าง Shortcut ของ Chrome ที่เปิด URL: `chrome.exe --kiosk "URL_ของ_Playlist"`
2. **Linux:**
   - แก้ไขไฟล์ autostart
   - เพิ่มคำสั่ง: `chromium-browser --kiosk "URL_ของ_Playlist"`

---

## 🔧 การแก้ไขปัญหา

### ปัญหา: ไม่สามารถเชื่อมต่อฐานข้อมูลได้
**อาการ:** หน้าจอขึ้นข้อความ "Connection Failed"

**วิธีแก้:**
1. ตรวจสอบว่า MySQL/MariaDB เปิดอยู่ใน XAMPP/WAMP Control Panel
2. ตรวจสอบ Port ใน `config.php` (ค่าเริ่มต้นคือ 3306)
3. ตรวจสอบ Username/Password ใน `config.php`
4. ทดสอบเชื่อมต่อผ่าน phpMyAdmin: `http://localhost/phpmyadmin`

### ปัญหา: อัพโหลดไฟล์ไม่ได้
**อาการ:** ขึ้นข้อความ "ไม่สามารถสร้างโฟลเดอร์อัพโหลดได้"

**วิธีแก้:**
1. สร้างโฟลเดอร์ `assets/uploads` ด้วยตนเอง
2. กำหนดสิทธิ์ให้เขียนไฟล์ได้:
   - **Windows:** คลิกขวา > Properties > Security > Edit > Full Control
   - **Linux/Mac:** `chmod 777 assets/uploads`
3. ตรวจสอบขนาดไฟล์ใน `php.ini`:
```ini
   upload_max_filesize = 50M
   post_max_size = 50M
```

### ปัญหา: ภาษาไทยแสดงเป็นอักขระแปลกๆ
**อาการ:** ตัวอักษรไทยแสดงผิด เป็นเครื่องหมายคำถาม

**วิธีแก้:**
1. ตรวจสอบ Charset ของฐานข้อมูล:
```sql
   ALTER DATABASE digital_signage_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```
2. ตรวจสอบใน `config.php`:
```php
   $conn->set_charset("utf8mb4");
```
3. บันทึกไฟล์ PHP เป็น UTF-8 (ไม่ใช่ UTF-8 with BOM)

### ปัญหา: Sidebar ไม่ทำงานบนมือถือ
**อาการ:** คลิกปุ่มเมนูแล้ว Sidebar ไม่ขึ้น

**วิธีแก้:**
1. ตรวจสอบว่าไฟล์ JavaScript โหลดครบหรือไม่:
   - Admin: `responsive_sidebar.js`
   - User: `sidebar-menu.js`
2. ตรวจสอบ Console ของเบราว์เซอร์ (F12) เพื่อดู Error
3. Clear Cache ของเบราว์เซอร์
4. ทดสอบบนเบราว์เซอร์อื่น

### ปัญหา: วิดีโอไม่เล่นใน Playlist
**อาการ:** เห็นแต่หน้าจอดำ หรือวิดีโอค้าง

**วิธีแก้:**
1. ตรวจสอบรูปแบบวิดีโอ (รองรับเฉพาะ MP4, WebM, OGG)
2. ตรวจสอบขนาดไฟล์ (แนะนำไม่เกิน 50MB)
3. ลองแปลงวิดีโอเป็น MP4 (H.264 codec)
4. ตรวจสอบ Path ของไฟล์ใน `assets/uploads`

### ปัญหา: สถานะอุปกรณ์แสดง Offline ตลอด
**อาการ:** แม้เปิด Playlist แล้ว สถานะยังเป็น Offline

**วิธีแก้:**
1. ตรวจสอบว่าไฟล์ `update_device_heartbeat.php` มีอยู่:
   - Admin: `admin/update_device_heartbeat.php`
   - User: `user/update_device_heartbeat.php`
2. ตรวจสอบว่า JavaScript ใน `device_playlist.php` และ `playlist_preview.php` ทำงานปกติ
3. ตรวจสอบ Console ของเบราว์เซอร์หา Error
4. ตรวจสอบว่าตาราง `devices` มีคอลัมน์ `last_active`

### ปัญหา: เข้าสู่ระบบไม่ได้
**อาการ:** กรอก Username/Password แล้วขึ้น "ไม่ถูกต้อง"

**วิธีแก้:**
1. ตรวจสอบว่า Username สะกดถูกต้อง (case-sensitive)
2. รีเซ็ตรหัสผ่านผ่าน phpMyAdmin:
```sql
   -- สร้างรหัสผ่านใหม่ (password: newpass123)
   UPDATE users 
   SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
   WHERE username = 'admin';
```
3. ตรวจสอบว่าตาราง `users` มีข้อมูล

### ปัญหา: หน้าเว็บไม่แสดง CSS
**อาการ:** หน้าเว็บแสดงแต่ Text ธรรมดา ไม่มีสี ไม่มี Layout

**วิธีแก้:**
1. ตรวจสอบว่า Bootstrap โหลดได้:
```html
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
```
2. ตรวจสอบการเชื่อมต่ออินเทอร์เน็ต (CDN ต้องการอินเทอร์เน็ต)
3. ตรวจสอบ Path ของไฟล์ CSS:
   - Admin: `../assets/css/admin.css`
   - User: `../assets/css/user-panel.css`
4. Clear Cache: Ctrl + F5 (Windows) หรือ Cmd + Shift + R (Mac)

### ปัญหา: Session หมดอายุเร็วเกินไป
**อาการ:** ต้องล็อกอินใหม่บ่อยๆ

**วิธีแก้:**
1. แก้ไขใน `php.ini`:
```ini
   session.gc_maxlifetime = 86400  ; 24 ชั่วโมง
   session.cookie_lifetime = 86400
```
2. รีสตาร์ท Apache/XAMPP
3. หรือเพิ่มใน `config.php`:
```php
   ini_set('session.gc_maxlifetime', 86400);
   session_set_cookie_params(86400);
```

### ปัญหา: Permission Denied เมื่อลบไฟล์
**อาการ:** ลบ Content ไม่ได้ ขึ้นข้อความ Permission Denied

**วิธีแก้:**
1. ตรวจสอบสิทธิ์โฟลเดอร์ `assets/uploads`:
```bash
   chmod 777 assets/uploads  # Linux/Mac
```
2. ตรวจสอบว่าไฟล์ไม่ถูกเปิดใช้งานอยู่
3. ปิดโปรแกรมที่อาจใช้ไฟล์อยู่ (เช่น Media Player)

---

## ❓ FAQ (คำถามที่พบบ่อย)

### Q1: ระบบรองรับไฟล์ประเภทอะไรบ้าง?
**A:** 
- **รูปภาพ:** JPG, JPEG, PNG, GIF
- **วิดีโอ:** MP4, WebM, OGG
- **ขนาดไฟล์สูงสุด:** 50MB (ปรับได้ใน php.ini)

### Q2: จะเพิ่มขนาดไฟล์สูงสุดได้อย่างไร?
**A:** แก้ไขใน `php.ini`:
```ini
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 600
memory_limit = 512M
```
จากนั้นรีสตาร์ท Apache

### Q3: Content แสดงผลไม่เต็มจอ ทำอย่างไร?
**A:** ระบบจะปรับขนาดอัตโนมัติตามสัดส่วนของไฟล์:
- **Landscape (แนวนอน):** แสดง 96% ของหน้าจอ
- **Portrait (แนวตั้ง):** แสดงตามสัดส่วนจริง

หากต้องการแก้ไข ปรับใน `assets/css/playlist_preview.css`:
```css
.content-item.landscape .content-image,
.content-item.landscape .content-video {
    width: 100%;  /* เปลี่ยนจาก 96% */
    height: 100%;
}
```

### Q4: จะตั้งค่าให้ Content เล่นเฉพาะช่วงเวลาได้ไหม?
**A:** ได้! ตอนอัพโหลดหรือแก้ไข Content:
1. กำหนด "วันที่เริ่มแสดงผล" (เช่น 2025-01-01 08:00)
2. กำหนด "วันที่สิ้นสุด" (เช่น 2025-01-31 17:00)
3. Content จะแสดงเฉพาะช่วงเวลาที่กำหนด

### Q5: จะสร้าง Playlist ที่แตกต่างกันสำหรับแต่ละจอได้ไหม?
**A:** ได้! มี 2 วิธี:
1. **ตอนอัพโหลด:** เลือกอุปกรณ์เฉพาะที่ต้องการแสดงผล
2. **แก้ไขภายหลัง:** ไปที่จัดการ Content > แก้ไข > เลือกอุปกรณ์ใหม่

### Q6: User ทั่วไปจะลบ Content ของคนอื่นได้ไหม?
**A:** ไม่ได้! ระบบมีการป้องกัน:
- User ลบได้เฉพาะ Content ที่ตนเองอัพโหลด
- User จะเห็นเฉพาะ Content ในอุปกรณ์ที่ได้รับสิทธิ์
- Admin เท่านั้นที่ลบ Content ของทุกคนได้

### Q7: จะเพิ่มลำดับการแสดง Content ได้อย่างไร?
**A:** ปัจจุบันระบบจัดลำดับตาม:
1. `display_order` (จากฐานข้อมูล)
2. `content_id` จากมากไปน้อย (Content ใหม่แสดงก่อน)

หากต้องการปรับลำดับด้วยตนเอง:
```sql
UPDATE device_content 
SET display_order = 1 
WHERE content_id = 10 AND device_id = 1;
```

### Q8: รองรับการแสดงผลบนทีวี Smart TV ไหม?
**A:** รองรับ! เพียงแค่:
1. เปิดเบราว์เซอร์บน Smart TV
2. ไปที่ URL ของ Playlist
3. กดเข้าโหมดเต็มจอ

**แนะนำ:** ใช้ Mini PC หรือ Android Box เชื่อมต่อทีวีจะทำงานได้ดีกว่า

### Q9: จะสำรองข้อมูลอย่างไร?
**A:** 
1. **ฐานข้อมูล:** Export ผ่าน phpMyAdmin
   - เข้า phpMyAdmin
   - เลือกฐานข้อมูล `digital_signage_db`
   - คลิก Export > Go
2. **ไฟล์ Content:** คัดลอกโฟลเดอร์ `assets/uploads`
3. **ไฟล์โค้ด:** คัดลอกทั้งโฟลเดอร์โปรเจค

**แนะนำ:** สำรองข้อมูลทุกสัปดาห์

### Q10: ทำให้ Content เล่นแบบสุ่มได้ไหม?
**A:** ปัจจุบันเล่นตามลำดับ แต่สามารถแก้ไขได้:

เปิดไฟล์ `admin/device_playlist.php` หรือ `user/playlist_preview.php`:
```javascript
// เปลี่ยนจาก
function nextContent() {
    currentIndex = (currentIndex + 1) % items.length;
    clearTimeout(timeout);
    showContent(currentIndex);
}

// เป็น (แบบสุ่ม)
function nextContent() {
    currentIndex = Math.floor(Math.random() * items.length);
    clearTimeout(timeout);
    showContent(currentIndex);
}
```

### Q11: จะติดตั้งบนเซิร์ฟเวอร์จริง (Production) อย่างไร?
**A:**
1. **เช่า Hosting** ที่รองรับ PHP + MySQL (เช่น Hostinger, GoDaddy)
2. **อัพโหลดไฟล์:**
   - ใช้ FTP/SFTP อัพโหลดโฟลเดอร์โปรเจค
   - วางไว้ที่ `public_html` หรือ `www`
3. **สร้างฐานข้อมูล:**
   - ผ่าน cPanel หรือ phpMyAdmin
   - Import ไฟล์ SQL
4. **แก้ไข config.php:**
```php
   $servername = "localhost";  // หรือ IP ของ Database Server
   $username = "your_db_user";
   $password = "your_db_password";
   $dbname = "your_db_name";
```
5. **ตั้งค่า SSL (HTTPS):** ผ่าน cPanel หรือ Let's Encrypt

### Q12: มีค่าใช้จ่ายในการใช้งานไหม?
**A:** ระบบนี้เป็น **Open Source** และ **ฟรี** สามารถใช้งานได้โดยไม่มีค่าใช้จ่าย
- ไม่มีค่า License
- ไม่มีค่าบำรุงรักษา
- ไม่มีข้อจำกัดจำนวนผู้ใช้งานหรืออุปกรณ์

**ค่าใช้จ่ายที่อาจเกิดขึ้น (ตามความต้องการ):**
- Server/Hosting: ฟรี (Local) หรือ 100-500 บาท/เดือน (Cloud)
- โดเมน: 300-500 บาท/ปี (ถ้าต้องการ)
- SSL Certificate: ฟรี (Let's Encrypt) หรือ 500-2,000 บาท/ปี

---

## 🛠️ การพัฒนาเพิ่มเติม

### ฟีเจอร์ที่สามารถเพิ่มได้

#### 1. Schedule Playlist
เพิ่มการกำหนดตารางเวลาที่ละเอียดกว่า:
```sql
CREATE TABLE playlist_schedules (
  schedule_id INT PRIMARY KEY AUTO_INCREMENT,
  device_id INT,
  content_id INT,
  day_of_week VARCHAR(10),  -- monday, tuesday, etc.
  start_time TIME,
  end_time TIME,
  FOREIGN KEY (device_id) REFERENCES devices(device_id),
  FOREIGN KEY (content_id) REFERENCES contents(content_id)
);
```

#### 2. Analytics / รายงาน
เพิ่มการบันทึกสถิติการแสดงผล:
```sql
CREATE TABLE content_logs (
  log_id INT PRIMARY KEY AUTO_INCREMENT,
  content_id INT,
  device_id INT,
  played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  duration INT,
  FOREIGN KEY (content_id) REFERENCES contents(content_id),
  FOREIGN KEY (device_id) REFERENCES devices(device_id)
);
```

#### 3. Template / Layout
เพิ่มการแบ่งหน้าจอเป็นโซน (Split Screen):
- Zone บน: News Ticker
- Zone กลาง: Content หลัก
- Zone ล่าง: Clock/Weather

#### 4. Emergency Alert
เพิ่มการแจ้งเตือนฉุกเฉิน:
- Override Content ปกติ
- แสดงข้อความเตือนทันที
- กลับมาแสดง Content ปกติหลังหมดเวลา

#### 5. Remote Control
เพิ่มการควบคุมทางไกล:
- เล่น/หยุด Content ระยะไกล
- รีสตาร์ทอุปกรณ์
- ตรวจสอบ Screenshot

#### 6. API Integration
เพิ่มการดึงข้อมูลจากภายนอก:
- ข้อมูลสภาพอากาศ
- ราคาหุ้น
- RSS Feed

### แนวทางการพัฒนา

#### แก้ไขไฟล์ CSS
```css
/* assets/css/custom.css */
/* เพิ่มสีธีม */
:root {
  --primary-color: #1abc9c;
  --secondary-color: #3498db;
  --danger-color: #e74c3c;
}

.sidebar {
  background: var(--primary-color);
}
```

#### แก้ไขไฟล์ JavaScript
```javascript
// assets/js/custom.js
// เพิ่มฟังก์ชันใหม่
function customFunction() {
  console.log('Custom function called');
  // Your code here
}
```

#### เพิ่ม API Endpoint
```php
// api/get_content.php
<?php
header('Content-Type: application/json');
include '../config.php';

$device_id = $_GET['device_id'] ?? 0;

$sql = "SELECT * FROM device_content WHERE device_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $device_id);
$stmt->execute();
$result = $stmt->get_result();

echo json_encode($result->fetch_all(MYSQLI_ASSOC));
?>
```

---

## 🔒 ความปลอดภัย

### แนวปฏิบัติที่แนะนำ

#### 1. เปลี่ยนรหัสผ่าน Default
```sql
-- เปลี่ยนรหัสผ่าน Admin ทันทีหลังติดตั้ง
UPDATE users 
SET password = '$2y$10$YOUR_NEW_HASHED_PASSWORD' 
WHERE username = 'admin';
```

#### 2. ป้องกัน SQL Injection
ระบบใช้ Prepared Statements แล้ว:
```php
// ✅ ปลอดภัย
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);

// ❌ อันตราย (ห้ามใช้)
$sql = "SELECT * FROM users WHERE username = '$username'";
```

#### 3. ป้องกัน XSS (Cross-Site Scripting)
ใช้ `htmlspecialchars()` เสมอ:
```php
// ✅ ปลอดภัย
echo htmlspecialchars($user_input);

// ❌ อันตราย
echo $user_input;
```

#### 4. ตรวจสอบไฟล์อัพโหลด
```php
// ตรวจสอบประเภทไฟล์
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'webm', 'ogg'];
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

if (!in_array($ext, $allowed)) {
    die('Invalid file type');
}

// ตรวจสอบขนาดไฟล์
$max_size = 50 * 1024 * 1024; // 50MB
if ($_FILES['file']['size'] > $max_size) {
    die('File too large');
}
```

#### 5. ใช้ HTTPS
เมื่อติดตั้งบน Production:
```apache
# .htaccess
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

#### 6. สำรองข้อมูลเป็นประจำ
```bash
# สำรองฐานข้อมูล (Linux/Mac)
mysqldump -u root -p digital_signage_db > backup_$(date +%Y%m%d).sql

# สำรองไฟล์
tar -czf uploads_backup_$(date +%Y%m%d).tar.gz assets/uploads/
```

#### 7. จำกัดการเข้าถึง Admin
```apache
# .htaccess ในโฟลเดอร์ admin
# จำกัดให้เข้าได้เฉพาะ IP ที่กำหนด
Order Deny,Allow
Deny from all
Allow from 192.168.1.0/24
```

---

## 📊 Performance Optimization

### เพิ่มประสิทธิภาพระบบ

#### 1. Optimize Images
- ใช้ TinyPNG หรือ ImageOptim ลดขนาดไฟล์
- แปลงเป็น WebP (สำหรับเบราว์เซอร์ที่รองรับ)
- Resize ภาพให้เหมาะสมกับความละเอียดจอ

#### 2. Enable Caching
```apache
# .htaccess
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType image/jpg "access plus 1 year"
  ExpiresByType image/jpeg "access plus 1 year"
  ExpiresByType image/png "access plus 1 year"
  ExpiresByType image/gif "access plus 1 year"
  ExpiresByType video/mp4 "access plus 1 year"
</IfModule>
```

#### 3. Compress Output
```php
// ที่ต้นไฟล์ PHP
ob_start('ob_gzhandler');
```

#### 4. Optimize Database
```sql
-- เพิ่ม Index
CREATE INDEX idx_device_content ON device_content(device_id, content_id);
CREATE INDEX idx_user_permissions ON user_permissions(user_id, device_id);
CREATE INDEX idx_content_dates ON contents(start_date, end_date);

-- ลบข้อมูลเก่า
DELETE FROM content_logs WHERE played_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

#### 5. Use Content Delivery Network (CDN)
แทนที่ Bootstrap/jQuery จาก CDN:
```html
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
```

---

## 📞 การสนับสนุน

### ติดต่อผู้พัฒนา
- **ชื่อ:** นายฐิติพงศ์ ภาสวร
- **โครงการ:** พนักงานโครงการทดลองจ้างงานบุคคลออทิสติก รุ่นที่ 13
- หรือ ติดต่อที่
- **หน่วยงาน:** งานเทคโนโลยีสารสนเทศ โรงพยาบาลยุวประสาทไวทโยปถัมภ์

### การรายงานปัญหา (Bug Report)
หากพบปัญหาการใช้งาน กรุณารายงานพร้อมข้อมูล:
1. **เวอร์ชัน PHP:** (ตรวจสอบด้วย `php -v`)
2. **เวอร์ชัน MySQL:** (ตรวจสอบใน phpMyAdmin)
3. **เบราว์เซอร์:** (Chrome/Firefox/Safari และเวอร์ชัน)
4. **อาการปัญหา:** อธิบายอย่างละเอียด
5. **ขั้นตอนทำซ้ำ:** วิธีทำให้เกิดปัญหาซ้ำ
6. **Screenshot/Error Message:** ถ้ามี

### การขอความช่วยเหลือ
- ตรวจสอบ FAQ ก่อน
- ค้นหาในส่วน [การแก้ไขปัญหา](#การแก้ไขปัญหา)
- ตรวจสอบ Console Log (F12)

---

## 📝 License

ระบบนี้พัฒนาขึ้นเพื่อการศึกษาและใช้งานในองค์กร สามารถนำไปใช้งานและดัดแปลงได้อย่างอิสระ โดยไม่คิดค่าใช้จ่าย

### ข้อกำหนดการใช้งาน
- ✅ ใช้งานได้ฟรี ไม่จำกัดจำนวนผู้ใช้หรืออุปกรณ์
- ✅ ดัดแปลงและพัฒนาต่อยอดได้
- ✅ ใช้ในเชิงพาณิชย์ได้
- ⚠️ แจ้งให้ทราบหากนำไปใช้งานในโครงการใหญ่
- ⚠️ ไม่รับประกันความเสียหายใดๆ ที่อาจเกิดขึ้น

---

## 🙏 กิตติกรรมประกาศ

### เทคโนโลยีที่ใช้
- [Bootstrap 5](https://getbootstrap.com/) - CSS Framework
- [Bootstrap Icons](https://icons.getbootstrap.com/) - Icon Library
- [Google Fonts - Sarabun](https://fonts.google.com/) - Thai Font

### แรงบันดาลใจ
ระบบนี้พัฒนาขึ้นเพื่อแก้ปัญหาการจัดการจอประชาสัมพันธ์ในองค์กร โดยมุ่งเน้นความง่ายในการใช้งานและความยืดหยุ่นในการปรับแต่ง

---

## 📚 เอกสารเพิ่มเติม

### คู่มือการใช้งานเพิ่มเติม
1. [คู่มือสำหรับ Admin](docs/admin-guide.md) - (สร้างไฟล์แยก)
2. [คู่มือสำหรับ User](docs/user-guide.md) - (สร้างไฟล์แยก)
3. [API Documentation](docs/api-docs.md) - (ถ้ามีการพัฒนา API)

### วิดีโอสอนการใช้งาน
- การติดตั้งระบบ
- การอัพโหลด Content
- การตั้งค่าอุปกรณ์
- (แนะนำให้สร้างวิดีโอและแชร์ลิงก์)

---

## 🔄 Change Log

### Version 1.0.0 (2025-01-01)
- ✨ เปิดตัวระบบครั้งแรก
- ✨ ระบบจัดการผู้ใช้งาน (Admin/User)
- ✨ อัพโหลดและจัดการ Content (Image/Video)
- ✨ จัดการอุปกรณ์และ Playlist
- ✨ ระบบสิทธิ์การเข้าถึง
- ✨ Responsive Design สำหรับทุกอุปกรณ์
- ✨ ระบบ Heartbeat ตรวจสอบสถานะอุปกรณ์

### Version 1.1.0 (Coming Soon)
- 🔜 เพิ่ม Schedule Playlist
- 🔜 เพิ่มระบบ Analytics
- 🔜 เพิ่ม Template/Layout
- 🔜 เพิ่ม Emergency Alert
- 🔜 เพิ่ม Mobile App

---

## 📖 บทส่งท้าย

ขอขอบคุณที่ใช้งานระบบ Digital Signage นี้ หวังว่าจะเป็นประโยชน์สำหรับองค์กรของคุณ หากมีข้อเสนอแนะหรือพบปัญหาการใช้งาน ยินดีรับฟังและปรับปรุงแก้ไขให้ดียิ่งขึ้น

**Happy Digital Signage! 📺✨**

---

*จัดทำโดย: นายฐิติพงศ์ ภาสวร*  
*โครงการทดลองจ้างงานบุคคลออทิสติก รุ่นที่ 13*  
*© 2025 Digital Signage System*

---
</artifact>
