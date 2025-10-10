<nav id="sidebar" class="sidebar js-sidebar">
    <div class="sidebar-content js-simplebar">
        <a class="sidebar-brand" href="#">
            <span class="align-middle">{{ env('APP_NAME') }}</span>
        </a>
        <ul class="sidebar-nav">

        <li class="sidebar-item {{ request()->routeIs('home') ? 'active' : '' }}">
            <a class="sidebar-link" href="{{ route('home') }}">
                <i class="align-middle" data-feather="home"></i>
                <span class="align-middle">Dashboard</span>
            </a>
        </li>
            <li class="sidebar-header"> Resource Management </li>
            <li class="sidebar-item {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                <a class="sidebar-link" href="{{ route('categories.index') }}">
                    <i class="align-middle" data-feather="layers"></i>
                    <span class="align-middle">Categories</span>
                </a>
            </li>
            <li class="sidebar-item {{ request()->routeIs('products.*') ? 'active' : '' }}">
                <a class="sidebar-link" href="{{ route('products.index') }}">
                    <i class="align-middle" data-feather="archive"></i>
                    <span class="align-middle">Products</span>
                </a>
            </li>
            <li class="sidebar-item {{ request()->routeIs('payments.*') ? 'active' : '' }}">
                <a class="sidebar-link" href="{{ route('payments.index') }}">
                    <i class="align-middle" data-feather="credit-card"></i>
                    <span class="align-middle">Payments</span>
                </a>
            </li>
            <li class="sidebar-header"> User Management </li>
            <li class="sidebar-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <a class="sidebar-link" href="{{route('users.index')}}">
                    <i class="align-middle" data-feather="users"></i>
                    <span class="align-middle">Users</span>
                </a>
            </li>

         <li class="sidebar-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
             <a class="sidebar-link" href="{{ route('admin.users.index') }}">
                 <i class="align-middle" data-feather="shield"></i>
                 <span class="align-middle">Role Management</span>
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
