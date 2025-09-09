<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Optimanage">
    <meta name="author" content="Optimanage">
    <meta name="keywords" content="OptiManage">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link rel="shortcut icon" href="{{ asset('img/icons/icon-48x48.png') }}" />

    <title>{{env('APP_NAME')}}</title>

    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    <style>
        /* Container Styles */
        .my-custom-container-class {
            font-family: 'Arial', sans-serif;
        }

        /* Popup Styles */
        .my-custom-popup-class {
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            background-color: #f8f9fa;
        }

        /* Header Styles */
        .my-custom-header-class {
            background-color: #f1f3f5;
            border-bottom: 1px solid #e9ecef;
        }

        /* Title Styles */
        .my-custom-title-class {
            color: #333;
            font-weight: 600;
            font-size: 1.2rem;
        }

        /* Close Button Styles */
        .my-custom-close-button-class {
            color: #6c757d;
            transition: color 0.2s ease;
        }
        .my-custom-close-button-class:hover {
            color: #dc3545;
        }

        /* Input Styles */
        .my-custom-input-class {
            border: 1px solid #ced4da;
            border-radius: 6px;
            padding: 10px;
            transition: border-color 0.2s ease;
        }
        .my-custom-input-class:focus {
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
        }

        /* Button Styles */
        .my-custom-confirm-button-class {
            background-color: #28a745;
            border-color: #28a745;
            border-radius: 6px;
            padding: 10px 20px;
            transition: all 0.2s ease;
        }
        .my-custom-confirm-button-class:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }

        .my-custom-cancel-button-class {
            background-color: #dc3545;
            border-color: #dc3545;
            border-radius: 6px;
            padding: 10px 20px;
            transition: all 0.2s ease;
        }
        .my-custom-cancel-button-class:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }

        /* Icon Styles */
        .my-custom-icon-class {
            margin: 20px 0;
            transform: scale(1.5);
        }

        /* Actions Container */
        .my-custom-actions-class {
            background-color: #f8f9fa;
            padding: 15px;
            border-top: 1px solid #e9ecef;
        }

        .alert {
            position: relative;
            padding: 0.75rem 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
            border-radius: 0.25rem;
            transition: all 0.3s ease;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .alert-warning {
            color: #856404;
            background-color: #fff3cd;
            border-color: #ffeeba;
        }

        .alert-info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }

        /* Optional: Hover and focus effects */
        .alert:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        /* Bootstrap Table Responsive Overrides */
        @media (max-width: 767.98px) {
            .table-striped {
                width: 100%;
            }

            .table-striped thead {
                display: none;
            }

            .table-striped tbody tr {
                display: block;
                margin-bottom: 1rem;
                background-color: #fff;
                border: 1px solid rgba(0,0,0,0.125);
                border-radius: 0.25rem;
            }

            .table-striped tbody tr:nth-of-type(odd) {
                background-color: rgba(0,0,0,0.02);
            }

            .table-striped tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.75rem;
                border-bottom: 1px solid #dee2e6;
                text-align: right;
            }

            .table-striped tbody td:before {
                content: attr(data-label);
                font-weight: 600;
                margin-right: auto;
                text-align: left;
            }

            .table-striped tbody td:last-child {
                border-bottom: none;
            }

            /* Style for action buttons */
            .table-striped tbody td:last-child {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }

            .table-striped tbody td:last-child button {
                width: 100%;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/all.min.css" integrity="sha512-5Hs3dF2AEPkpNAR7UiOHba+lRSJNeM2ECkwxUIxC1Q/FLycGTbNapWXB4tP889k5T5Ju8fs4b1P5z/iB4nMfSQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>
<div class="wrapper">
    @include('layouts.header')

    <div class="main">
        <nav class="navbar navbar-expand navbar-light navbar-bg">
            <a class="sidebar-toggle js-sidebar-toggle">
                <i class="hamburger align-self-center"></i>
            </a>

            <div class="navbar-collapse collapse">
                <ul class="navbar-nav navbar-align">
                    <li class="nav-item dropdown">
                        <a class="nav-icon dropdown-toggle d-inline-block d-sm-none" href="#" data-bs-toggle="dropdown">
                            <i class="align-middle" data-feather="settings"></i>
                        </a>

                        <a class="nav-link dropdown-toggle d-none d-sm-inline-block" href="#" data-bs-toggle="dropdown">
                            <img src="{{ auth()->user()->profile_image ? asset('storage/' . auth()->user()->profile_image) : 'https://placehold.co/128' }}" class="avatar img-fluid rounded me-1" alt="" /> <span class="text-dark">{{auth()->user()->name}}</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="{{route('profile')}}"><i class="align-middle me-1" data-feather="user"></i> Profile</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="align-middle me-1" data-feather="log-out"></i> Log out
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>

        <main class="content">
            <div class="container-fluid p-0">


                @yield('content')

            </div>
        </main>

        @include('layouts.footer')
    </div>
</div>

<script src="{{asset('js/app.js')}}"></script>
<!-- Include jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Include DataTables JS -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<!-- Include DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
<!-- Include SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
@stack('scripts')

</body>

</html>
