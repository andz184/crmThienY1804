<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-dark">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="bx bx-menu"></i></a>
        </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <!-- Search -->
        <li class="nav-item d-none d-md-block">
            <a class="nav-link" href="#">
                <i class="bx bx-search"></i>
            </a>
        </li>

        <!-- Notifications -->
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#" aria-expanded="false">
                <i class="bx bx-bell"></i>
                <span class="badge badge-primary badge-pill">5</span>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <h6 class="dropdown-header">Notifications</h6>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">
                    <i class="bx bx-envelope mr-2"></i> 4 new messages
                </a>
                <a href="#" class="dropdown-item">
                    <i class="bx bx-user mr-2"></i> 3 friend requests
                </a>
                <a href="#" class="dropdown-item">
                    <i class="bx bx-report mr-2"></i> 2 new reports
                </a>
            </div>
        </li>

        <!-- User -->
        <li class="nav-item dropdown user-menu">
            <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                <img src="{{ asset('vendor/adminlte/dist/img/user2-160x160.jpg') }}" class="user-image img-circle" alt="User Image">
            </a>
            <ul class="dropdown-menu dropdown-menu-right">
                <li class="user-header">
                    <img src="{{ asset('vendor/adminlte/dist/img/user2-160x160.jpg') }}" class="img-circle" alt="User Image">
                    <p>
                        {{ Auth::user()->name ?? 'Administrator' }}
                        <small>Member since {{ Auth::user()->created_at->format('M. Y') ?? 'N/A' }}</small>
                    </p>
                </li>
                <li class="user-footer">
                    <a href="#" class="btn btn-default btn-flat">Profile</a>
                    <a href="#" class="btn btn-default btn-flat float-right" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        Sign out
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </li>
            </ul>
        </li>
    </ul>
</nav>
<!-- /.navbar -->
