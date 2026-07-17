@php
$role = session('user_role');
$authUserRoleId = auth()->user()->role_id;
@endphp

<!doctype html>
<html lang="en">
<!-- Mirrored from themesbrand.com/skote/layouts/layouts-preloader.html by HTTrack Website Copier/3.x [XR&CO'2014], Tue, 15 Nov 2022 07:57:46 GMT -->

<head>

    <meta charset="utf-8" />
    <title>@yield('title') | {{ config('setting.app_name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Premium Multipurpose Admin & Dashboard Template" name="description" />
    <meta content="Themesbrand" name="author" />
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ config('setting.logo') }}">
    <link href="{{ asset('libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    @yield('css')

    <!-- Bootstrap Css -->
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="{{ asset('css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="{{ asset('css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .bg-dark-success {
            background-color: #495749;
        }

        .bg-dark-primary {
            background-color: #2B3142;
        }

        .page-content{padding:calc(30px + 0px) calc(24px * .75) 60px calc(24px * .75)}
        .vertical-menu {
            top: 0%;
        }

        #sidebar-menu {
            padding-bottom: 5%;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
    </style>

</head>
