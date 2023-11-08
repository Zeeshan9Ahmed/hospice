<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title') | Hospice Connect</title>
{{--  <link rel="icon" href="{{ asset('admin/app_icon.png') }}" />--}}
  <!-- Google Font: Source Sans Pro -->
  <!-- <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback"> -->

  <!-- Font Awesome -->
  <link rel="stylesheet" href="{{asset('admin/plugins/fontawesome-free/css/all.min.css')}}">
  <!-- Ionicons -->
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
  <!-- Tempusdominus Bootstrap 4 -->
  <link rel="stylesheet" href="{{asset('admin/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css')}}">
  <!-- iCheck -->
  <link rel="stylesheet" href="{{asset('admin/plugins/icheck-bootstrap/icheck-bootstrap.min.css')}}">
  <!-- JQVMap -->
  <link rel="stylesheet" href="{{asset('admin/plugins/jqvmap/jqvmap.min.css')}}">
  <!-- Theme style -->
  <link rel="stylesheet" href="{{asset('admin/dist/css/adminlte.min.css')}}">
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="{{asset('admin/plugins/overlayScrollbars/css/OverlayScrollbars.min.css')}}">
  <!-- Daterange picker -->
  <link rel="stylesheet" href="{{asset('admin/plugins/daterangepicker/daterangepicker.css')}}">
  <!-- summernote -->
  <link rel="stylesheet" href="{{asset('admin/plugins/summernote/summernote-bs4.min.css')}}">
  @yield('style')
  <style>
    /* .nav-pills .nav-link:not(.active):hover {
    color: #f0f2f5;
}
*/
.active {
  background-color: #474c51 !important;
  color: white !important;
}
@font-face {
  font-family: "Serto Jerusalem";
  src: url('admin/font.otf');
  /*src: url('fonts/fira/eot/FiraSans-Regular.eot') format('embedded-opentype'),
       url('fonts/fira/woff2/FiraSans-Regular.woff2') format('woff2'),
       url('fonts/fira/woff/FiraSans-Regular.woff') format('woff'),
       url('fonts/fira/woff2/FiraSans-Regular.ttf') format('truetype');*/
}


body {
  font-family: 'Calibri Light' !important;
}
  </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
  <div class="wrapper">

  <!-- Preloader -->
{{--  <div class="preloader flex-column justify-content-center align-items-center">--}}
{{--    <img class="animation__shake" src="{{asset('admin/app_icon.png')}}" alt="Syriac Hymnal" height="60" width="60">--}}
{{--  </div>--}}

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
     <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
{{--      <li class="nav-item d-none d-sm-inline-block">--}}
{{--        <a href="{{url('/dashboard')}}" class="nav-link"></a>--}}
{{--      </li>--}}

    </ul>

  </nav>
  <!-- /.navbar -->

  <!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4" >
    <!-- Brand Logo -->
    <a href="{{route('dashboard')}}" class="brand-link">
{{--      <img src="{{asset('admin/app_icon.png')}}" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">--}}
      <span class="brand-text font-weight-light pl-4">Hospice Connect</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">



      <!-- Sidebar Menu -->
      <nav class="mt-3">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

            <li class="nav-item {{ Request::is('dashboard') ? 'nav_active' : '' }}">
              <a href="{{ route('dashboard') }}" class="nav-link">
                <i class="nav-icon fas fa-list"></i>
                  <p>
                    Dashboard
                  </p>
              </a>
            </li>

            <li class="nav-item has-treeview {{request()->is('users/hospices*') || request()->is("users/nurses")?"menu-open":""}}">
                <a href="#" class="nav-link {{request()->is('users/hospices*') || request()->is("users/nurses")?"active":""}}">
                    <i class="nav-icon fas fa-user"></i>
                    <p>
                        Users
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    <li class="nav-item">
                        <a href="{{route('admin.users.hospices')}}" class="nav-link {{request()->is('users/hospices')?"active":""}}">
                            <p class="p-5">Hospices</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{route('admin.users.nurses')}}" class="nav-link {{request()->is('users/nurses')?"active":""}}">
                            <p class="p-5">Nurses</p>
                        </a>
                    </li>

                </ul>
            </li>

            <li class="nav-item">
                <a href="{{ route('admin.contents') }}" class="nav-link {{request()->is('contents')?"active":""}}">
                    <i class="nav-icon fas fa-copy"></i>
                    <p>
                        Contents
                    </p>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('admin.feedbacks') }}" class="nav-link {{ Request::is('feedbacks') ? 'active' : '' }}">
                    <i class="nav-icon fas fa-pen"></i>
                    <p>
                        Feedbacks
                    </p>
                </a>
            </li>

            <li class="nav-item">
              <a href="{{ route('admin.logout')}}" class="nav-link">
                  <i class="nav-icon fas fa-trash"></i>
                  <p>
                      Logout
                  </p>
              </a>
            </li>

        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>

  <script type="text/javascript">
      document.addEventListener("DOMContentLoaded", function () {
          document.querySelectorAll('.sidebar .nav-link').forEach(function (element) {

              element.addEventListener('click', function (e) {

                  let nextEl = element.nextElementSibling;
                  let parentEl = element.parentElement;

                  if (nextEl) {
                      e.preventDefault();
                      let mycollapse = new bootstrap.Collapse(nextEl);

                      if (nextEl.classList.contains('show')) {
                          mycollapse.hide();
                      } else {
                          mycollapse.show();
                          // find other submenus with class=show
                          var opened_submenu = parentEl.parentElement.querySelector('.submenu.show');
                          // if it exists, then close all of them
                          if (opened_submenu) {
                              new bootstrap.Collapse(opened_submenu);
                          }
                      }
                  }
              }); // addEventListener
          }) // forEach
      });
  </script>
