document.addEventListener("DOMContentLoaded", () => {
    const sidebar = document.getElementById("sidebar");
    const menuButton = document.getElementById("mobileMenuButton");
    const openButton = document.getElementById("openSidebar");
    const closeButton = document.getElementById("closeSidebar");
    const overlay = document.getElementById("sidebarOverlay");

    if (!sidebar) return;

    // Open sidebar
    function openSidebar() {
        sidebar.classList.remove("-translate-x-full");

        if (overlay) {
            overlay.classList.remove("hidden");
        }
    }

    // Close sidebar
    function closeSidebar() {
        sidebar.classList.add("-translate-x-full");

        if (overlay) {
            overlay.classList.add("hidden");
        }
    }

    // Existing mobile menu button
    menuButton?.addEventListener("click", () => {
        sidebar.classList.toggle("-translate-x-full");

        if (overlay) {
            overlay.classList.toggle("hidden");
        }
    });

    // Open button
    openButton?.addEventListener("click", openSidebar);

    // Close button
    closeButton?.addEventListener("click", closeSidebar);

    // Close when clicking outside sidebar
    overlay?.addEventListener("click", closeSidebar);

    // Close sidebar after selecting a page on mobile
    sidebar.querySelectorAll("a").forEach((link) => {
        link.addEventListener("click", () => {
            if (window.innerWidth < 1024) {
                closeSidebar();
            }
        });
    });
});