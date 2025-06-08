$(document).ready(function () {
    // Function to set the correct initial state of the sidebar menu
    function initializeMenu() {
        const $treeviewItems = $('.nav-sidebar .nav-item.has-treeview');

        // First, ensure all submenus are closed
        $treeviewItems.removeClass('menu-open');
        $treeviewItems.find('.nav-treeview').css('display', 'none');

        // Then, find and open the active submenu
        const $activeSubmenu = $('.nav-sidebar .nav-link.active').parents('.nav-item.has-treeview').first();
        if ($activeSubmenu.length) {
            $activeSubmenu.addClass('menu-open');
            $activeSubmenu.find('.nav-treeview').css('display', 'block');
        }
    }

    // Initialize the menu on page load
    initializeMenu();

    // Add event listener for the pushmenu widget
    $('[data-widget="pushmenu"]').on('click', function() {
        // Use a small timeout to allow the collapse animation to start
        setTimeout(function() {
            if ($('body').hasClass('sidebar-collapse')) {
                // When collapsing, close all submenus.
                $('.nav-sidebar .nav-item.menu-open').removeClass('menu-open').find('.nav-treeview').css('display', 'none');
            }
        }, 300); // This delay should match the CSS transition time
    });
});
