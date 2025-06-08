document.addEventListener('DOMContentLoaded', function() {
    // Get the sidebar toggle button
    const sidebarToggleBtn = document.querySelector('[data-widget="pushmenu"]');

    // Function to close all submenus
    function closeAllSubmenus() {
        // Find all menu items with submenus
        const menuItems = document.querySelectorAll('.nav-sidebar .has-treeview, .nav-sidebar .menu-open');

        menuItems.forEach(function(menuItem) {
            // Remove menu-open class
            menuItem.classList.remove('menu-open');
            menuItem.classList.remove('menu-is-opening');

            // Find the submenu
            const submenu = menuItem.querySelector('.nav-treeview');
            if (submenu) {
                // Hide submenu
                submenu.style.display = 'none';

                // Reset any nested submenus as well
                const nestedMenuItems = submenu.querySelectorAll('.has-treeview, .menu-open');
                nestedMenuItems.forEach(function(nestedItem) {
                    nestedItem.classList.remove('menu-open');
                    nestedItem.classList.remove('menu-is-opening');
                    const nestedSubmenu = nestedItem.querySelector('.nav-treeview');
                    if (nestedSubmenu) {
                        nestedSubmenu.style.display = 'none';
                    }
                });
            }
        });
    }

    // Add click event listener to sidebar toggle button
    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener('click', function() {
            // Close all submenus when toggling the menu
            setTimeout(closeAllSubmenus, 0);
        });
    }

    // Watch for sidebar collapse class changes
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.target.classList.contains('sidebar-collapse')) {
                closeAllSubmenus();
            }
        });
    });

    // Start observing the body element for class changes
    const body = document.querySelector('body');
    observer.observe(body, {
        attributes: true,
        attributeFilter: ['class']
    });

    // Handle window resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            const body = document.querySelector('body');
            if (body.classList.contains('sidebar-collapse')) {
                closeAllSubmenus();
            }
        }, 250);
    });

    // Close all submenus on page load
    const allSubmenus = document.querySelectorAll('.nav-treeview');
    allSubmenus.forEach(function(submenu) {
        submenu.style.display = 'none';
        const parentItem = submenu.closest('.nav-item');
        if (parentItem) {
            parentItem.classList.remove('menu-is-opening', 'menu-open');
        }
    });

    // Only open submenu if it contains the active item
    const activeLink = document.querySelector('.nav-treeview .nav-link.active');
    if (activeLink) {
        const activeSubmenu = activeLink.closest('.nav-treeview');
        if (activeSubmenu) {
            activeSubmenu.style.display = 'block';
            const parentItem = activeSubmenu.closest('.nav-item');
            if (parentItem) {
                parentItem.classList.add('menu-open');
            }
        }
    }

    // Handle menu click events
    document.querySelectorAll('.nav-sidebar .has-treeview > .nav-link, .nav-sidebar .nav-item > .nav-link').forEach(function(link) {
        link.addEventListener('click', function(e) {
            const parentItem = this.closest('.nav-item');
            const submenu = parentItem.querySelector('.nav-treeview');

            if (submenu) {
                e.preventDefault();
                e.stopPropagation();

                const isOpen = parentItem.classList.contains('menu-open');

                // Close all other submenus at the same level
                const siblings = parentItem.parentElement.querySelectorAll('.nav-item.menu-open');
                siblings.forEach(function(sibling) {
                    if (sibling !== parentItem) {
                        sibling.classList.remove('menu-is-opening', 'menu-open');
                        const siblingSubmenu = sibling.querySelector('.nav-treeview');
                        if (siblingSubmenu) {
                            siblingSubmenu.style.display = 'none';
                        }
                    }
                });

                // Toggle current submenu
                if (isOpen) {
                    parentItem.classList.remove('menu-is-opening', 'menu-open');
                    submenu.style.display = 'none';
                } else {
                    parentItem.classList.add('menu-open');
                    submenu.style.display = 'block';
                }
            }
        });
    });
});
