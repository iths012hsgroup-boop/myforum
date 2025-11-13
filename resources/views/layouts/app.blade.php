<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Aplikasi Admin HS Group - @yield('title')</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('assets/admin/css/all.min.css') }}" media="all">
    <!-- overlayScrollbars / AdminLTE -->
    <link rel="stylesheet" href="{{ asset('assets/admin/css/adminlte.min.css') }}">
    <!-- CSS Custom -->
    <link rel="stylesheet" href="{{ asset('assets/admin/css/app.css') }}">
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">

    <link rel="stylesheet" href="{{ asset('assets/admin/css/dropify.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/summernote.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/bootstrap-datepicker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/tempusdominus-bootstrap-4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/dashboard.css') }}">

    <link rel="shortcut icon" type="image/jpg" href="{{ asset('favicon.png') }}" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css">
    <link rel="stylesheet" href="{{ asset('assets/admin/css/choices.min.css') }}">

    <!-- Google Font: Source Sans Pro -->
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">

    {{-- agar @push('styles') dari halaman ikut dimuat --}}
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        {{-- KIRI: menu, jam, pengumuman --}}
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
            <li class="nav-item d-sm-inline-block">
                <a href="javascript:void(0)" class="nav-link">
                    <b><div class="time"><?= date('d-m-Y H:i:s'); ?></div></b>
                </a>
            </li>
            <li class="nav-item d-sm-inline-block">
                <a href="javascript:void(0)" class="nav-link">
                    <b>
                        <div class="pengumuman">
                            PENGUMUMAN:
                            {!! $pengumumanHtml ?? '' !!}
                        </div>
                    </b>
                </a>
            </li>
        </ul>

        {{-- KANAN: dropdown user (Ubah Password + Keluar) --}}
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button">
                    <i class="fas fa-user-circle mr-1"></i>
                    {{ Auth::user()->nama_staff ?? Auth::user()->id_admin }}
                </a>
                <div class="dropdown-menu dropdown-menu-right">

                    {{-- Ubah password (pakai hak akses yang sama dengan sidebar) --}}
                    @if (in_array('HSF007', (array) $allowed))
                        <a href="{{ route('password.update') }}" class="dropdown-item">
                            <i class="fas fa-unlock-alt mr-2"></i> Ubah Password
                        </a>
                        <div class="dropdown-divider"></div>
                    @endif

                    {{-- Keluar --}}
                    <a href="{{ route('logout') }}"
                    class="dropdown-item"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="fas fa-sign-out-alt mr-2"></i> Keluar
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="GET" class="d-none">
                        @csrf
                    </form>
                </div>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <!-- Brand Logo -->
            <a href="{{ route('dashboard.index') }}" class="brand-link">
                <img src="{{ asset('assets/admin/img/favicon.png') }}" alt="HS Logo" class="brand-image img-circle elevation-3" style="opacity:.8">
                <span class="brand-text font-weight-light">HS Forum</span>
            </a>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Sidebar Menu -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                        @if (in_array('HSF001', (array) $allowed))
                        <li class="nav-item">
                            <a href="{{ route('hsforum.index') }}" class="nav-link">
                                <i class="nav-icon fas fa-heading"></i>
                                <p>HS Forum</p>
                            </a>
                        </li>
                        @endif

                        @if (in_array('HSF002', (array) $allowed))
                        <li class="nav-item">
                            <a href="{{ route('auditorforum.new') }}" class="nav-link">
                                <i class="nav-icon fas fa-heading"></i>
                                <p>HS Forum (Auditor &amp; Admin)</p>
                            </a>
                        </li>
                        @endif

                        @if (in_array('HSF014', (array) $allowed))
                        <li class="nav-item">
                            <a href="{{ route('olddata.olddataclosedcases') }}" class="nav-link">
                                <i class="nav-icon fas fa-heading"></i>
                                <p>HS Forum (Data Lama)</p>
                            </a>
                        </li>
                        @endif

                        @if (in_array('HSF016', (array) $allowed))
                        <li class="nav-item">
                            <a href="{{ route('opforum.indexop') }}" class="nav-link">
                                <i class="nav-icon fas fa-heading"></i>
                                <p>HS Forum (OP)</p>
                            </a>
                        </li>
                        @endif

                        @if (in_array('HSF019', (array) $allowed))
                        <li class="nav-item">
                            <a href="{{ route('hrdmanagement.grafik') }}"
                            class="nav-link {{ request()->routeIs('hrdmanagement.index','hrdmanagement.grafik','hrdmanagement.reportingabsensi') ? 'active' : '' }}">
                                <i class="nav-icon fa fa-address-card"></i>
                                <p>HRD Management</p>
                            </a>
                        </li>
                        @endif

                        {{-- REPORTING --}}
                        @if (in_array('HSF020', (array) $allowed))
                            <li class="nav-item has-treeview">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon fa-solid fa-book"></i>
                                    <p>
                                        Reporting
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>

                                <ul class="nav nav-treeview">
                                    {{-- Data Periode Lama (HSF011) --}}
                                    @if (in_array('HSF011', (array) $allowed))
                                        <li class="nav-item">
                                            <a href="{{ route('oldperiode.auditorclosedcasesreport') }}" class="nav-link">
                                                <i class="nav-icon fas fa-archive"></i>
                                                <p>Data Periode Lama</p>
                                            </a>
                                        </li>
                                    @endif

                                    {{-- Reporting Periode (HSF012) --}}
                                    @if (in_array('HSF012', (array) $allowed))
                                        <li class="nav-item">
                                            <a href="{{ route('reporting.index') }}" class="nav-link">
                                                <i class="nav-icon fa-solid fa-book-open"></i>
                                                <p>Reporting Periode</p>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif

                        {{-- CONFIGURE --}}
                        @if (in_array('HSF021', (array) $allowed))
                            <li class="nav-item has-treeview">
                                <a href="#" class="nav-link">
                                    <i class="nav-icon fa-solid fa-gear"></i>
                                    <p>
                                        Configure
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>

                                <ul class="nav nav-treeview">
                                    {{-- Daftar Situs (HSF003) --}}
                                    @if (in_array('HSF003', (array) $allowed))
                                        <li class="nav-item">
                                            <a href="{{ route('daftarsitus.index') }}" class="nav-link">
                                                <i class="nav-icon fas fa-list-alt"></i>
                                                <p>Daftar Situs</p>
                                            </a>
                                        </li>
                                    @endif

                                    {{-- Daftar Jabatan (HSF004) --}}
                                    @if (in_array('HSF004', (array) $allowed))
                                        <li class="nav-item">
                                            <a href="{{ route('daftarjabatan.index') }}" class="nav-link">
                                                <i class="nav-icon fas fa-users-cog"></i>
                                                <p>Daftar Jabatan</p>
                                            </a>
                                        </li>
                                    @endif

                                    {{-- Daftar Staff (HSF005) --}}
                                    @if (in_array('HSF005', (array) $allowed))
                                        <li class="nav-item">
                                            <a href="{{ route('daftaruser.index') }}" class="nav-link">
                                                <i class="nav-icon fas fa-users"></i>
                                                <p>Daftar Staff</p>
                                            </a>
                                        </li>
                                    @endif

                                    {{-- Topik (HSF018) --}}
                                    @if (in_array('HSF018', (array) $allowed))
                                        <li class="nav-item">
                                            <a href="{{ route('topik.index') }}" class="nav-link">
                                                <i class="nav-icon fas fa-tags"></i>
                                                <p>Topik</p>
                                            </a>
                                        </li>
                                    @endif

                                    {{-- Migrasi User (HSF009) --}}
                                    @if (in_array('HSF009', (array) $allowed))
                                        <li class="nav-item">
                                            <a href="{{ route('migrasidb.index') }}" class="nav-link">
                                                <i class="nav-icon fa-solid fa-arrow-right-arrow-left"></i>
                                                <p>Migrasi User</p>
                                            </a>
                                        </li>
                                    @endif

                                    {{-- Reset Password (HSF010) --}}
                                    @if (in_array('HSF010', (array) $allowed))
                                        <li class="nav-item">
                                            <a href="{{ route('resetpassword.resetpwd') }}" class="nav-link">
                                                <i class="nav-icon fa-solid fa-lock-open"></i>
                                                <p>Reset Password</p>
                                            </a>
                                        </li>
                                    @endif

                                    {{-- Setting Pengumuman (HSF006) --}}
                                    @if (in_array('HSF006', (array) $allowed))
                                        <li class="nav-item">
                                            <a href="{{ route('setting.index') }}" class="nav-link">
                                                <i class="nav-icon fa-solid fa-rss"></i>
                                                <p>Setting Pengumuman</p>
                                            </a>
                                        </li>
                                    @endif

                                    {{-- Settings Periode (HSF013) --}}
                                    @if (in_array('HSF013', (array) $allowed))
                                        <li class="nav-item">
                                            <a href="{{ route('periodessetting.setting') }}" class="nav-link">
                                                <i class="nav-icon fa-solid fa-gear"></i>
                                                <p>Settings Periode</p>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif

                    </ul>
                </nav>
                <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->
        </aside>

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>@yield('title')</h1>
                        </div>
                        <div class="col-sm-6">
                            @yield('breadcrumb')
                        </div>
                    </div>
                </div>
            </section>

            <!-- Main content -->
            <section class="content">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if (session('danger'))
                    <div class="alert alert-danger">{{ session('danger') }}</div>
                @endif
                @if (session('danger-with-link'))
                    <div class="alert alert-danger">{!! session('danger-with-link') !!}</div>
                @endif
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @yield('content')
            </section>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->

        <footer class="main-footer">
            <div class="float-right d-sm-block">
                <b>Version</b> 2.0.0
            </div>
            <strong>Copyright &copy; {{ date('Y') }} <a href="#">HS Group</a>.</strong> All rights reserved.
        </footer>
        <aside class="control-sidebar control-sidebar-dark"></aside>
    </div>

    <!-- Scripts -->
    <script src="{{ asset('assets/admin/js/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/adminlte.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/dropify.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/summernote.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/tempusdominus-bootstrap-4.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/select2.full.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <script src="{{ asset('assets/admin/js/sweetalert2@9.js') }}"></script>
    <script src="{{ asset('assets/admin/js/chart.umd.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/choices.min.js') }}"></script>




    @stack('scripts')

    <script>
        // add active class & keep opened
        var url = window.location;
        $('ul.nav-sidebar a').filter(function() {
            if (this.href) return this.href == url || url.href.indexOf(this.href) == 0;
        }).addClass('active');

        $('ul.nav-treeview a').filter(function() {
            if (this.href) return this.href == url || url.href.indexOf(this.href) == 0;
        }).parentsUntil(".nav-sidebar > .nav-treeview").addClass('menu-open').prev('a').addClass('active');

        // jam berjalan
        window.setTimeout("waktu()", 1000);
        function waktu() {
            var waktu = new Date();
            setTimeout("waktu()", 1000);
            var jam   = (waktu.getHours()<10? '0':'') + waktu.getHours();
            var menit = (waktu.getMinutes()<10? '0':'') + waktu.getMinutes();
            var detik = (waktu.getSeconds()<10? '0':'') + waktu.getSeconds();
            $('.time').html("<?= date('d-m-Y'); ?> " + jam + ':' + menit + ':' + detik)
        }
    </script>
</body>
</html>
