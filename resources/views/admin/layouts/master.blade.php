<!-- meta tags and other links -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel</title>

    <!-- Favicons -->
    <link href="assets/img/favicon.png" rel="icon">
    <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.0.3/src/light/style.css">

    <!-- Vendor CSS Files -->
    <link href="{{ asset('assets/admin/vendors/css/bootstrap.min.css')}}" rel="stylesheet">
    <link href="{{ asset('assets/admin/vendors/css/remixicon.css')}}" rel="stylesheet">
    <link href="{{ asset('assets/admin/vendors/css/style.css')}}" rel="stylesheet">
    <link href="{{ asset('assets/admin/vendors/css/quill.snow.css')}}" rel="stylesheet">
    <link href="{{ asset('assets/admin/vendors/css/choices.min.css')}}" rel="stylesheet">
    <link href="{{ asset('assets/admin/vendors/css/flatpickr.min.css')}}" rel="stylesheet">

    <!-- Template Main CSS File -->
    <link href="{{ asset('assets/admin/vendors/css/main.css')}}" rel="stylesheet">


    @stack('style')
</head>
<body>

@yield('content')



    <!-- Back to Top -->
    <a href="#" class="back-to-top">
        <i class="bi bi-arrow-up"></i>
    </a>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Vendor JS Files -->
    <script src="{{ asset('assets/admin/js/bootstrap.bundle.min.js')}}"></script>
    <script src="{{ asset('assets/admin/js/apexcharts.min.js')}}"></script>
    <script src="{{ asset('assets/admin/js/simple-datatables.js')}}"></script>
    <script src="{{ asset('assets/admin/js/quill.js')}}"></script>
    <script src="{{ asset('assets/admin/js/tinymce.min.js')}}"></script>
    <script src="{{ asset('assets/admin/js/choices.min.js')}}"></script>
    <script src="{{ asset('assets/admin/js/flatpickr.min.js')}}"></script>
    <script src="{{ asset('assets/admin/js/validate.js')}}"></script>

    <!-- Template Main JS Files -->
    <script src="{{ asset('assets/admin/js/theme.js')}}"></script>
    <script src="{{ asset('assets/admin/js/main.js')}}"></script>

    <script src="{{ asset('assets/admin/js/apps-sidebar-toggle.js')}}"></script>
@include('partials.notify')


@stack('script')
</body>
</html>
