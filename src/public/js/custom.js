document.addEventListener("DOMContentLoaded", function () {
    const body = document.body;
    const menuToggle = document.querySelector(".menu-toggle");
    const sidebarOverlay = document.querySelector(".sidebar-overlay");

    if (menuToggle) {
        menuToggle.addEventListener("click", function () {
            body.classList.toggle("sidebar-open");
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener("click", function () {
            body.classList.remove("sidebar-open");
        });
    }

    const currentUrl = window.location.href;
    const navLinks = document.querySelectorAll(".nav-item");

    navLinks.forEach(link => {
        if (currentUrl.includes(link.getAttribute("href"))) {
            navLinks.forEach(item => item.classList.remove("active"));
            link.classList.add("active");
        }
    });
});