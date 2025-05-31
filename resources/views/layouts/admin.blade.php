<!-- Products Menu -->
<li class="nav-item has-treeview {{ request()->is('admin/products*') || request()->is('admin/categories*') ? 'menu-open' : '' }}">
    <a href="#" class="nav-link {{ request()->is('admin/products*') || request()->is('admin/categories*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-box"></i>
        <p>
            Products
            <i class="right fas fa-angle-left"></i>
        </p>
    </a>
    <ul class="nav nav-treeview">
        <li class="nav-item">
            <a href="{{ route('admin.products.index') }}" class="nav-link {{ request()->is('admin/products') ? 'active' : '' }}">
                <i class="far fa-circle nav-icon"></i>
                <p>All Products</p>
            </a>
        </li>
        @can('products.create')
        <li class="nav-item">
            <a href="{{ route('admin.products.create') }}" class="nav-link {{ request()->is('admin/products/create') ? 'active' : '' }}">
                <i class="far fa-circle nav-icon"></i>
                <p>Add New</p>
            </a>
        </li>
        @endcan
        @can('products.sync')
        <li class="nav-item">
            <a href="{{ route('admin.products.sync') }}" class="nav-link {{ request()->is('admin/products/sync') ? 'active' : '' }}">
                <i class="fas fa-sync nav-icon"></i>
                <p>Pancake Sync</p>
            </a>
        </li>
        @endcan
        @can('categories.view')
        <li class="nav-item">
            <a href="{{ route('admin.categories.index') }}" class="nav-link {{ request()->is('admin/categories*') ? 'active' : '' }}">
                <i class="fas fa-tags nav-icon"></i>
                <p>Categories</p>
            </a>
        </li>
        @endcan
    </ul>
</li>
