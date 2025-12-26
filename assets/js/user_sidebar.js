// assets/js/user_sidebar.js
document.addEventListener("DOMContentLoaded", function() {
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('mobileMenuToggle');
    const overlay = document.getElementById('sidebarOverlay');
    const closeBtn = document.getElementById('mobileCloseBtn');
    
    // ตรวจสอบว่ามี elements ที่จำเป็นหรือไม่
    if (!sidebar || !toggleBtn || !overlay) {
        console.warn('Sidebar elements not found');
        return;
    }

    // ฟังก์ชันเปิดเมนู
    function openMenu() {
        sidebar.classList.add('show');
        overlay.classList.add('show');
        toggleBtn.classList.add('hide'); // ซ่อนปุ่ม toggle
        document.body.style.overflow = 'hidden'; // ป้องกันการ scroll
    }

    // ฟังก์ชันปิดเมนู
    function closeMenu() {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
        toggleBtn.classList.remove('hide'); // แสดงปุ่ม toggle
        document.body.style.overflow = ''; // คืนค่าการ scroll
    }

    // ฟังก์ชัน Toggle (เปิด/ปิด)
    function toggleMenu() {
        if (sidebar.classList.contains('show')) {
            closeMenu();
        } else {
            openMenu();
        }
    }

    // Event: คลิกปุ่ม Toggle
    if (toggleBtn) {
        toggleBtn.addEventListener('click', openMenu);
    }

    // Event: คลิก Overlay เพื่อปิดเมนู
    if (overlay) {
        overlay.addEventListener('click', closeMenu);
    }

    // Event: คลิกปุ่มปิด (X)
    if (closeBtn) {
        closeBtn.addEventListener('click', closeMenu);
    }

    // Event: คลิกลิงก์ในเมนู (บนมือถือ) ให้ปิดเมนูอัตโนมัติ
    const navLinks = sidebar.querySelectorAll('a, .nav-link');
    navLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 720) {
                closeMenu();
            }
        });
    });

    // Event: ปิดเมนูอัตโนมัติเมื่อขยายจอ
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth > 720) {
                closeMenu();
            } else {
                // ถ้าจอเล็กและ sidebar ไม่เปิด ให้แสดงปุ่ม toggle
                if (!sidebar.classList.contains('show')) {
                    toggleBtn.classList.remove('hide');
                }
            }
        }, 250);
    });

    // Event: กด ESC เพื่อปิดเมนู
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('show')) {
            closeMenu();
        }
    });
});