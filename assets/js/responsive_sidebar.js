// responsive_sidebar.js - JavaScript สำหรับจัดการ Responsive Sidebar

document.addEventListener('DOMContentLoaded', function() {
    // ดึง elements
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const mobileCloseBtn = document.getElementById('mobileCloseBtn');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const contentArea = document.getElementById('contentArea');

    // ฟังก์ชันเปิด sidebar
    function openSidebar() {
        if (sidebar) sidebar.classList.add('active');
        if (sidebarOverlay) sidebarOverlay.classList.add('active');
        document.body.style.overflow = 'hidden'; // ป้องกันการ scroll ของ body
    }

    // ฟังก์ชันปิด sidebar
    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('active');
        if (sidebarOverlay) sidebarOverlay.classList.remove('active');
        document.body.style.overflow = ''; // คืนค่าการ scroll
    }

    // Event: เปิด sidebar เมื่อคลิกปุ่ม mobile menu
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            openSidebar();
        });
    }

    // Event: ปิด sidebar เมื่อคลิกปุ่มปิด
    if (mobileCloseBtn) {
        mobileCloseBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            closeSidebar();
        });
    }

    // Event: ปิด sidebar เมื่อคลิกที่ overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            closeSidebar();
        });
    }

    // Event: ปิด sidebar เมื่อคลิกลิงก์ใน sidebar (สำหรับ mobile)
    if (sidebar) {
        const sidebarLinks = sidebar.querySelectorAll('.nav-link');
        sidebarLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                // ตรวจสอบว่าอยู่ในโหมด mobile หรือไม่
                if (window.innerWidth <= 768) {
                    closeSidebar();
                }
            });
        });
    }

    // Event: ปิด sidebar เมื่อกด Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeSidebar();
        }
    });

    // Event: ปรับ layout เมื่อ resize หน้าจอ
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            // ถ้าหน้าจอใหญ่กว่า 768px ให้ปิด sidebar overlay
            if (window.innerWidth > 768) {
                closeSidebar();
                if (sidebar) sidebar.classList.remove('active');
            }
        }, 250);
    });

    // เพิ่ม smooth scroll สำหรับ anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href !== '') {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });

    // ตรวจสอบและแสดงข้อความเตือนสำหรับหน้าจอเล็กมาก
    function checkScreenSize() {
        if (window.innerWidth < 320) {
            console.warn('หน้าจอมีขนาดเล็กเกินไป อาจมีปัญหาในการแสดงผล');
        }
    }

    checkScreenSize();
    window.addEventListener('resize', checkScreenSize);
});