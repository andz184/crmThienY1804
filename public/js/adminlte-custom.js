$(document).ready(function () {
    // Find all menu items that have a submenu
    const $treeviewItems = $('.nav-sidebar .nav-item.has-treeview');

    // First, ensure all submenus are closed by removing the 'menu-open' class
    $treeviewItems.removeClass('menu-open');

    // Then, find the specific submenu that contains the currently active link
    const $activeSubmenu = $treeviewItems.find('.nav-link.active').closest('.nav-item.has-treeview');

    // If an active submenu is found, add the 'menu-open' class to expand it
    if ($activeSubmenu.length) {
        $activeSubmenu.addClass('menu-open');
    }
});
