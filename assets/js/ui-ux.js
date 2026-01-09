document.addEventListener('DOMContentLoaded', function () {
    // Sidebar Toggle Logic
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    const mobileBreakpoint = 1024;
    let lastWidth = window.innerWidth;

    // Initial check on load
    if (sidebar) {
        if (window.innerWidth <= mobileBreakpoint) {
            sidebar.classList.add('closed');
        }
    }

    // Handle Resize with boundary detection
    window.addEventListener('resize', function () {
        if (!sidebar) return;

        const currentWidth = window.innerWidth;
        const wasMobile = lastWidth <= mobileBreakpoint;
        const isMobile = currentWidth <= mobileBreakpoint;

        if (wasMobile !== isMobile) {
            if (isMobile) {
                // Desktop -> Mobile: Auto-close
                sidebar.classList.add('closed');
                sidebar.style.transform = '';
            } else {
                // Mobile -> Desktop: Auto-open
                sidebar.classList.remove('closed');
                sidebar.style.transform = '';
            }
        }
        lastWidth = currentWidth;
    });

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('closed');
            sidebar.style.transform = ''; // Ensure CSS classes take precedence
        });
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function (event) {
        if (window.innerWidth <= mobileBreakpoint && sidebar && !sidebar.classList.contains('closed')) {
            if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
                sidebar.classList.add('closed');
            }
        }
    });

    // Handle Active Link in Sidebar
    const currentPath = window.location.pathname;
    const sidebarLinks = document.querySelectorAll('.sidebar ul li a');
    sidebarLinks.forEach(link => {
        if (link.getAttribute('href') && currentPath.includes(link.getAttribute('href'))) {
            link.classList.add('active');
        }
    });
});
