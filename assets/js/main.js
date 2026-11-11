/**
 * Smart CampusConnect - Unified Campus Management Portal
 * Core Client-Side Interactions Script
 * BCA 5th Semester Major Project 1
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Mobile Sidebar Toggle
    const sidebarToggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('appSidebar');

    if (sidebarToggleBtn && sidebar) {
        let backdrop = document.createElement('div');
        backdrop.className = 'sidebar-backdrop d-none';
        document.body.appendChild(backdrop);

        function toggleSidebar() {
            sidebar.classList.toggle('show');
            backdrop.classList.toggle('d-none');
        }

        sidebarToggleBtn.addEventListener('click', toggleSidebar);
        backdrop.addEventListener('click', toggleSidebar);
    }

    // 2. Initialize Bootstrap Tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // 3. Client-Side Instant Table Search Filter
    // Targets any input with [data-table-search="targetTableId"]
    const searchInputs = document.querySelectorAll('[data-table-search]');
    searchInputs.forEach(input => {
        const targetId = input.getAttribute('data-table-search');
        const table = document.getElementById(targetId);
        if (!table) return;

        input.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            const rows = table.querySelectorAll('tbody tr');

            rows.forEach(row => {
                // If it's a "no records" row, ignore
                if (row.classList.contains('no-records-row')) return;

                const text = row.textContent.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });

    // 4. Password Visibility Toggle
    const togglePasswordBtns = document.querySelectorAll('.toggle-password-btn');
    togglePasswordBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const targetInputId = this.getAttribute('data-target');
            const targetInput = document.getElementById(targetInputId);
            if (!targetInput) return;

            const icon = this.querySelector('i');
            if (targetInput.type === 'password') {
                targetInput.type = 'text';
                if (icon) {
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                }
            } else {
                targetInput.type = 'password';
                if (icon) {
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            }
        });
    });

    // 5. Flash Alert Auto-Dismiss after 5 seconds
    const autoAlerts = document.querySelectorAll('.alert-dismissible');
    autoAlerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 6000);
    });
});
