@include('layouts.partials.it-admin.app-head')

<body data-sidebar="dark">

    <!-- Loader -->
    @include('layouts.partials.app-preloader')

    <!-- Begin page -->
    <div id="layout-wrapper">

        @include('layouts.partials.it-admin.app-header')

        <!-- ========== Left Sidebar Start ========== -->
        <div class="vertical-menu">

            <div data-simplebar class="h-100">

                <!--- Sidemenu -->
                @include('layouts.partials.it-admin.app-sidebar')
                <!-- Sidebar -->
            </div>
        </div>
        <!-- Left Sidebar End -->

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">

            <div class="page-content">
                <div class="container-fluid">

                    @yield('content')

                </div> <!-- container-fluid -->
            </div>
            <!-- End Page-content -->

            @include('layouts.partials.it-admin.app-footer')
        </div>
        <!-- end main content-->

    </div>
    <!-- END layout-wrapper -->

    <!-- Right Sidebar -->
    {{-- @include('layouts.partials.app-rightbar') --}}
    <!-- /Right-bar -->

    <!-- Right bar overlay-->
    <div class="rightbar-overlay"></div>

    <!-- JAVASCRIPT -->
    @include('layouts.partials.app-plugin')
</body>

<!-- Mirrored from themesbrand.com/skote/layouts/layouts-preloader.html by HTTrack Website Copier/3.x [XR&CO'2014], Tue, 15 Nov 2022 07:57:46 GMT -->

</html>
