document.addEventListener('DOMContentLoaded', function() {
    console.log("Digital Signage System JavaScript Loaded.");

    // Function for smooth confirmation on delete buttons
    document.querySelectorAll('.btn-delete-confirm').forEach(button => {
        button.addEventListener('click', function(e) {
            const confirmMessage = this.getAttribute('data-confirm') || 'คุณแน่ใจหรือไม่?';
            if (!confirm(confirmMessage)) {
                e.preventDefault();
            }
        });
    });

    // Optional: Add active class to sidebar based on current page
    const currentPath = window.location.pathname.split('/').pop();
    document.querySelectorAll('.sidebar a').forEach(link => {
        if (link.getAttribute('href').split('/').pop() === currentPath) {
            link.classList.add('active');
        }
    });
});