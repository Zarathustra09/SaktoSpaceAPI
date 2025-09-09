<nav id="sidebar" class="sidebar js-sidebar">
    <div class="sidebar-content js-simplebar">
        <a class="sidebar-brand" href="#">
            <span class="align-middle">{{ env('APP_NAME') }}</span>
        </a>
        <ul class="sidebar-nav">
            <li class="sidebar-header"> Resource Management </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="#">
                    <i class="align-middle" data-feather="layers"></i>
                    <span class="align-middle">Categories</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="#">
                    <i class="align-middle" data-feather="archive"></i>
                    <span class="align-middle">Products</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="#">
                    <i class="align-middle" data-feather="credit-card"></i>
                    <span class="align-middle">Payments</span>
                </a>
            </li>
            <li class="sidebar-header"> User Management </li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="#">
                    <i class="align-middle" data-feather="users"></i>
                    <span class="align-middle">Users</span>
                </a>
            </li>
        </ul>
    </div>
</nav>

<style>
    .sidebar-dropdown {
        padding-left: 1.5rem;
    }
</style>
